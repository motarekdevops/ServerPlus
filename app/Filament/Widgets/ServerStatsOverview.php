<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerStatsOverview extends BaseWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $total = Server::count();
        $online = Server::where('status', 'online')->count();
        $offline = Server::where('status', 'offline')->count();
        $unresolvedAlerts = Alert::where('is_resolved', false)->count();

        return [
            Stat::make('Total Servers', $total)
                ->icon('heroicon-o-server-stack')
                ->color('gray'),

            Stat::make('Online', $online)
                ->description($online . ' of ' . $total . ' servers up')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Offline', $offline)
                ->description($offline > 0 ? 'Needs attention' : 'All good')
                ->icon('heroicon-o-x-circle')
                ->color($offline > 0 ? 'danger' : 'gray'),

            Stat::make('Active Alerts', $unresolvedAlerts)
                ->description($unresolvedAlerts > 0 ? 'Unresolved' : 'No active alerts')
                ->icon('heroicon-o-bell-alert')
                ->color($unresolvedAlerts > 0 ? 'warning' : 'gray'),
        ];
    }
}
