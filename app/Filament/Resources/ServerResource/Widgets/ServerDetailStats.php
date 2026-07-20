<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerDetailStats extends BaseWidget
{
    public ?Server $record = null;

    protected function getStats(): array
    {
        $server = $this->record;

        $latest = fn (string $type) => $server->checks()
            ->where('type', $type)
            ->first()
            ?->results()
            ->latest()
            ->first();

        $cpu = $latest('cpu');
        $ram = $latest('ram');
        $disk = $latest('disk');

        return [
            Stat::make('Status', ucfirst($server->status))
                ->icon($server->status === 'online' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color($server->status === 'online' ? 'success' : 'danger'),

            Stat::make('CPU Usage', $cpu ? number_format($cpu->value, 1) . '%' : '—')
                ->color($this->colorFor($cpu?->status))
                ->icon('heroicon-o-cpu-chip'),

            Stat::make('RAM Usage', $ram ? number_format($ram->value, 1) . '%' : '—')
                ->color($this->colorFor($ram?->status))
                ->icon('heroicon-o-circle-stack'),

            Stat::make('Disk Usage', $disk ? number_format($disk->value, 1) . '%' : '—')
                ->color($this->colorFor($disk?->status))
                ->icon('heroicon-o-server'),
        ];
    }

    protected function colorFor(?string $status): string
    {
        return match ($status) {
            'critical' => 'danger',
            'warning' => 'warning',
            'ok' => 'success',
            default => 'gray',
        };
    }
}
