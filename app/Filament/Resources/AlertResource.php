<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Alerts';

    protected static ?string $navigationBadgeTooltip = 'Unresolved alerts';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_resolved', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('server_id')
                    ->relationship('server', 'name')
                    ->required(),

                Forms\Components\TextInput::make('rule_triggered')
                    ->required(),

                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_resolved'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rule_triggered')
                    ->label('Rule')
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_resolved')
                    ->label('Resolved')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Triggered')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Status')
                    ->trueLabel('Resolved')
                    ->falseLabel('Unresolved')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Alert $record) => ! $record->is_resolved)
                    ->action(fn (Alert $record) => $record->update(['is_resolved' => true])),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlerts::route('/'),
            'edit' => Pages\EditAlert::route('/{record}/edit'),
        ];
    }
}
