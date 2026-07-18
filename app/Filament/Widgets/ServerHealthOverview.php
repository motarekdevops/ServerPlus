<?php

namespace App\Filament\Widgets;

use App\Models\Server;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ServerHealthOverview extends BaseWidget
{
    protected static ?string $heading = 'Server Health Overview';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Server::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('host'),

                Tables\Columns\TextColumn::make('health')
                    ->label('Health')
                    ->state(function (Server $record): string {
                        if ($record->status !== 'online') {
                            return 'offline';
                        }

                        $hasCritical = $record->checks()
                            ->whereHas('results', fn ($q) => $q->where('status', 'critical')
                                ->latest()->limit(1))
                            ->exists();

                        $worst = 'ok';
                        foreach ($record->checks as $check) {
                            $latest = $check->results()->latest()->first();
                            if ($latest?->status === 'critical') {
                                $worst = 'critical';
                                break;
                            }
                            if ($latest?->status === 'warning') {
                                $worst = 'warning';
                            }
                        }

                        return $worst;
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'offline' => 'gray',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'critical' => '🔴 Critical',
                        'warning' => '🟡 Warning',
                        'offline' => '⚫ Offline',
                        default => '🟢 Healthy',
                    }),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Last Checked')
                    ->since(),
            ])
            ->paginated(false);
    }
}
