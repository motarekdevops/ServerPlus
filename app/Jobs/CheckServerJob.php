<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\CheckResult;
use App\Models\Server;
use App\Services\AlertDispatcher;
use App\Services\CheckEngine;
use App\Services\SshService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $alertCooldownMinutes = 30;

    public function __construct(public Server $server) {}

    public function handle(SshService $sshService, CheckEngine $engine, AlertDispatcher $alertDispatcher): void
    {
        try {
            $ssh = $sshService->connect($this->server);
        } catch (\Throwable $e) {
            $wasOnline = $this->server->status !== 'offline';

            $this->server->update([
                'status' => 'offline',
                'last_error' => $e->getMessage(),
            ]);

            if ($wasOnline) {
                for ($i = 0; $i < 3; $i++) {
                    $alert = Alert::create([
                        'server_id' => $this->server->id,
                        'rule_triggered' => 'Server Is Down',
                        'message' => "🔴 {$this->server->name} ({$this->server->host}) is unreachable. Error: {$e->getMessage()}",
                    ]);

                    $alertDispatcher->dispatch($alert);
                }
            }

            return;
        }

        $this->server->update([
            'status' => 'online',
            'last_error' => null,
            'last_checked_at' => now(),
        ]);

        foreach ($this->server->checks()->where('is_active', true)->get() as $check) {
            try {
                if ($check->type === 'ssl') {
                    $this->handleSslCheck($check, $sshService, $ssh, $alertDispatcher);
                    continue;
                }

                $value = match ($check->type) {
                    'cpu' => $sshService->getCpuUsage($ssh),
                    'ram' => $sshService->getRamUsage($ssh),
                    'disk' => $sshService->getDiskUsage($ssh),
                    'uptime' => $sshService->getUptime($ssh),
                    'updates' => $sshService->getAvailableUpdates($ssh),
                    default => 0,
                };
            } catch (\Throwable $e) {
                continue;
            }

            $status = $engine->evaluate($check->type, $value, $check->warning_threshold, $check->critical_threshold);

            CheckResult::create([
                'server_check_id' => $check->id,
                'value' => $value,
                'status' => $status,
            ]);

            if ($status === 'critical') {
                $this->maybeAlert($check, $alertDispatcher, "{$check->type} exceeded critical threshold", "{$check->type} is at {$value}% on {$this->server->name}");
            } else {
                $this->resetCooldownIfNeeded($check);
            }
        }
    }

    protected function handleSslCheck($check, SshService $sshService, $ssh, AlertDispatcher $alertDispatcher): void
    {
        if (empty($check->domain)) {
            return;
        }

        try {
            $details = $sshService->getSslCertificateDetails($ssh, $check->domain);
        } catch (\Throwable $e) {
            return;
        }

        $daysRemaining = $details['days_remaining'];

        $check->update([
            'ssl_issued_at' => $details['issued_at'],
            'ssl_expires_at' => $details['expires_at'],
        ]);

        // Custom per-domain threshold takes priority; falls back to warning_threshold.
        $alertThreshold = $check->alert_days_before ?: $check->warning_threshold;

        $status = $this->evaluateSsl($daysRemaining, $alertThreshold, $check->critical_threshold);

        CheckResult::create([
            'server_check_id' => $check->id,
            'value' => $daysRemaining,
            'status' => $status,
        ]);

        if ($status === 'critical') {
            $message = $daysRemaining < 0
                ? "🔴 SSL certificate for {$check->domain} on {$this->server->name} has EXPIRED {$this->abs($daysRemaining)} days ago. Sites may show security warnings and lose visitor trust."
                : "🔴 SSL certificate for {$check->domain} on {$this->server->name} expires in {$daysRemaining} days — past the safe renewal window. Renew immediately to avoid browser security warnings.";

            $this->maybeAlert($check, $alertDispatcher, 'SSL certificate critical', $message);
        } elseif ($status === 'warning') {
            $message = "⚠️ SSL certificate for {$check->domain} on {$this->server->name} expires in {$daysRemaining} days.";
            $this->maybeAlert($check, $alertDispatcher, 'SSL certificate expiring soon', $message);
        } else {
            $this->resetCooldownIfNeeded($check);
        }
    }

    protected function abs(int $value): int
    {
        return abs($value);
    }

    /**
     * SSL logic is inverted compared to CPU/RAM/Disk: fewer days remaining = worse.
     */
    protected function evaluateSsl(int $daysRemaining, float $warningThreshold, float $criticalThreshold): string
    {
        if ($daysRemaining <= $criticalThreshold) {
            return 'critical';
        }

        if ($daysRemaining <= $warningThreshold) {
            return 'warning';
        }

        return 'ok';
    }

    protected function maybeAlert($check, AlertDispatcher $alertDispatcher, string $rule, string $message): void
    {
        $onCooldown = $check->last_alerted_at
            && $check->last_alerted_at->diffInMinutes(now()) < $this->alertCooldownMinutes;

        if ($onCooldown) {
            return;
        }

        $alert = Alert::create([
            'server_id' => $this->server->id,
            'rule_triggered' => $rule,
            'message' => $message,
        ]);

        $alertDispatcher->dispatch($alert);

        $check->update(['last_alerted_at' => now()]);
    }

    protected function resetCooldownIfNeeded($check): void
    {
        if ($check->last_alerted_at !== null) {
            $check->update(['last_alerted_at' => null]);
        }
    }
}
