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

    // Minimum minutes between repeated alerts for the same check
    protected int $alertCooldownMinutes = 30;

    public function __construct(public Server $server) {}

    public function handle(SshService $sshService, CheckEngine $engine, AlertDispatcher $alertDispatcher): void
    {
        try {
            $ssh = $sshService->connect($this->server);
        } catch (\Throwable $e) {
            $this->server->update([
                'status' => 'offline',
                'last_error' => $e->getMessage(),
            ]);
            return;
        }

        $this->server->update([
            'status' => 'online',
            'last_error' => null,
            'last_checked_at' => now(),
        ]);

        foreach ($this->server->checks()->where('is_active', true)->get() as $check) {
            try {
                $value = match ($check->type) {
                    'cpu' => $sshService->getCpuUsage($ssh),
                    'ram' => $sshService->getRamUsage($ssh),
                    'disk' => $sshService->getDiskUsage($ssh),
                    'uptime' => $sshService->getUptime($ssh),
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
                $onCooldown = $check->last_alerted_at
                    && $check->last_alerted_at->diffInMinutes(now()) < $this->alertCooldownMinutes;

                if (! $onCooldown) {
                    $alert = Alert::create([
                        'server_id' => $this->server->id,
                        'rule_triggered' => "{$check->type} exceeded critical threshold",
                        'message' => "{$check->type} is at {$value}% on {$this->server->name}",
                    ]);

                    $alertDispatcher->dispatch($alert);

                    $check->update(['last_alerted_at' => now()]);
                }
            } else {
                // Reset cooldown once the check recovers, so a new incident alerts immediately
                if ($check->last_alerted_at !== null) {
                    $check->update(['last_alerted_at' => null]);
                }
            }
        }
    }
}
