<?php

namespace App\Filament\Resources\ServerResource\RelationManagers;

use App\Services\SshService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'checks';

    protected static ?string $title = 'Checks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'cpu' => 'CPU Usage',
                        'ram' => 'RAM Usage',
                        'disk' => 'Disk Usage',
                        'uptime' => 'Uptime',
                        'updates' => 'Available Updates',
                        'ssl' => 'SSL Certificate Expiry',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state === 'ssl') {
                            $set('warning_threshold', 14);
                            $set('critical_threshold', 7);
                            $set('alert_days_before', 15);
                        } else {
                            $set('warning_threshold', 70);
                            $set('critical_threshold', 90);
                        }
                    }),

                Forms\Components\TextInput::make('domain')
                    ->label('Domain')
                    ->placeholder('example.com')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'ssl')
                    ->required(fn (Forms\Get $get) => $get('type') === 'ssl'),

                Forms\Components\TextInput::make('alert_days_before')
                    ->label('Alert me this many days before expiry')
                    ->numeric()
                    ->default(15)
                    ->visible(fn (Forms\Get $get) => $get('type') === 'ssl')
                    ->helperText('After this window passes without renewal, the alert escalates to critical.'),

                Forms\Components\TextInput::make('domain_registration_expires_at')
                    ->label('Domain registration expiry (optional)')
                    ->helperText('When the domain name itself needs to be renewed with your registrar (not the SSL cert).')
                    ->type('date')
                    ->visible(fn (Forms\Get $get) => $get('type') === 'ssl'),

                Forms\Components\TextInput::make('warning_threshold')
                    ->label(fn (Forms\Get $get) => $get('type') === 'ssl' ? 'Warning (days remaining)' : 'Warning Threshold (%)')
                    ->numeric()
                    ->required()
                    ->default(70)
                    ->visible(fn (Forms\Get $get) => $get('type') !== 'ssl'),

                Forms\Components\TextInput::make('critical_threshold')
                    ->label(fn (Forms\Get $get) => $get('type') === 'ssl' ? 'Critical (days remaining)' : 'Critical Threshold (%)')
                    ->numeric()
                    ->required()
                    ->default(90),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ssl_issued_at')
                    ->label('Issued')
                    ->date()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ssl_expires_at')
                    ->label('Expires')
                    ->date()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ssl_last_renewed_at')
                    ->label('Last Renewed')
                    ->date()
                    ->placeholder('Never'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('results')
                    ->label('Latest Value')
                    ->state(function ($record) {
                        $latest = $record->results()->latest()->first();

                        if (! $latest) {
                            return '—';
                        }

                        $suffix = $record->type === 'ssl' ? ' days' : '%';

                        return "{$latest->value}{$suffix} ({$latest->status})";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $latest = $record->results()->latest()->first();
                        return match ($latest?->status) {
                            'critical' => 'danger',
                            'warning' => 'warning',
                            'ok' => 'success',
                            default => 'gray',
                        };
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('renew_certificate')
                    ->label('Renew Certificate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->type === 'ssl')
                    ->requiresConfirmation()
                    ->modalHeading('Renew SSL Certificate')
                    ->modalDescription(function ($record) {
                        $server = $this->getOwnerRecord();

                        try {
                            $sshService = app(SshService::class);
                            $ssh = $sshService->connect($server);
                            $webServer = $sshService->detectWebServer($ssh, $record->domain);
                        } catch (\Throwable $e) {
                            return "Could not connect to the server to detect the web server. Error: {$e->getMessage()}";
                        }

                        if ($webServer === null) {
                            return "Couldn't detect a local Nginx or Apache config for {$record->domain} on this server. "
                                . "This domain may be managed by your hosting provider or DNS panel — please renew it there, "
                                . "or run the appropriate certbot command manually on the server.";
                        }

                        return "This will run 'certbot renew' for {$record->domain} and reload {$webServer} on {$server->name}. Continue?";
                    })
                    ->action(function ($record, SshService $sshService) {
                        $server = $this->getOwnerRecord();

                        try {
                            $ssh = $sshService->connect($server);
                            $webServer = $sshService->detectWebServer($ssh, $record->domain);

                            if ($webServer === null) {
                                Notification::make()
                                    ->title('No local web server detected')
                                    ->body("Please renew {$record->domain} through your hosting provider, or run certbot manually on the server.")
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $output = $sshService->renewCertificate($ssh, $record->domain, $webServer);

                            $record->update(['ssl_last_renewed_at' => now()]);

                            // Re-check the certificate immediately so the dashboard reflects
                            // the new expiry date right away, instead of waiting for the
                            // next scheduled check.
                            try {
                                $details = $sshService->getSslCertificateDetails($ssh, $record->domain);

                                $record->update([
                                    'ssl_issued_at' => $details['issued_at'],
                                    'ssl_expires_at' => $details['expires_at'],
                                ]);

                                \App\Models\CheckResult::create([
                                    'server_check_id' => $record->id,
                                    'value' => $details['days_remaining'],
                                    'status' => 'ok',
                                ]);

                                $record->update(['last_alerted_at' => null]);
                            } catch (\Throwable $e) {
                                // Renewal succeeded; the immediate re-check is best-effort.
                            }

                            Notification::make()
                                ->title('Certificate renewal executed')
                                ->body(str($output)->limit(200))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Renewal failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
