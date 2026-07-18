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

    public function __construct(public Server $server) {}

    public function handle(SshService $sshService, CheckEngine $engine, AlertDispatcher $alertDispatcher): void
    {
        $ssh = $sshService->connect($this->server);

        if (! $ssh) {
            $this->server->update(['status' => 'offline']);
            return;
        }

        $this->server->update([
            'status' => 'online',
            'last_checked_at' => now(),
        ]);

        foreach ($this->server->checks()->where('is_active', true)->get() as $check) {
            $value = match ($check->type) {
                'cpu' => $sshService->getCpuUsage($ssh),
                'ram' => $sshService->getRamUsage($ssh),
                'disk' => $sshService->getDiskUsage($ssh),
                'uptime' => $sshService->getUptime($ssh),
                default => 0,
            };

            $status = $engine->evaluate($check->type, $value, $check->warning_threshold, $check->critical_threshold);

            CheckResult::create([
                'server_check_id' => $check->id,
                'value' => $value,
                'status' => $status,
            ]);

            if ($status === 'critical') {
                $alert = Alert::create([
                    'server_id' => $this->server->id,
                    'rule_triggered' => "{$check->type} exceeded critical threshold",
                    'message' => "{$check->type} is at {$value}% on {$this->server->name}",
                ]);

                $alertDispatcher->dispatch($alert);
            }
        }
    }
}
