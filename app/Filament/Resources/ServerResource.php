<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\Filament\Resources\ServerResource\RelationManagers;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Servers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Server Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('host')
                            ->label('Host / IP Address')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('port')
                            ->numeric()
                            ->required()
                            ->default(22),

                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('group')
                            ->label('Group (optional)')
                            ->placeholder('Production, Database, Web...'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('SSH Access')
                    ->schema([
                        Forms\Components\Textarea::make('private_key')
                            ->label('SSH Private Key')
                            ->required()
                            ->rows(8)
                            ->placeholder('-----BEGIN OPENSSH PRIVATE KEY-----')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Checks')
                    ->schema([
                        Forms\Components\CheckboxList::make('checkTypes')
                            ->label('Checks to run on this server')
                            ->options([
                                'cpu' => 'CPU Usage',
                                'ram' => 'RAM Usage',
                                'disk' => 'Disk Usage',
                                'uptime' => 'Uptime',
                            ])
                            ->default(['cpu', 'ram', 'disk'])
                            ->columns(2)
                            ->visibleOn('create'),
                    ])
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('host')
                    ->searchable(),

                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn ($record) => $record->last_error),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Last Checked')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'unknown' => 'Unknown',
                    ]),
            ])
            ->actions([
Tables\Actions\Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function ($record) {
                        $sshService = app(\App\Services\SshService::class);
                        try {
                            $sshService->connect($record);
                            $record->update(['status' => 'online', 'last_error' => null, 'last_checked_at' => now()]);
                            \Filament\Notifications\Notification::make()
                                ->title('Connection successful')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            $record->update(['status' => 'offline', 'last_error' => $e->getMessage()]);
                            \Filament\Notifications\Notification::make()
                                ->title('Connection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChecksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}

