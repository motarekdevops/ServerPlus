<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Alert Settings';

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::current();

        $this->form->fill([
            'email_enabled' => $setting->email_enabled,
            'email_address' => $setting->email_address,
            'telegram_enabled' => $setting->telegram_enabled,
            'telegram_bot_token' => $setting->telegram_bot_token,
            'telegram_chat_id' => $setting->telegram_chat_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Notifications')
                    ->schema([
                        Forms\Components\Toggle::make('email_enabled')
                            ->label('Enable Email Alerts')
                            ->live(),

                        Forms\Components\TextInput::make('email_address')
                            ->label('Alert Email Address')
                            ->email()
                            ->visible(fn (Forms\Get $get) => $get('email_enabled'))
                            ->required(fn (Forms\Get $get) => $get('email_enabled')),
                    ]),

                Forms\Components\Section::make('Telegram Notifications')
                    ->schema([
                        Forms\Components\Toggle::make('telegram_enabled')
                            ->label('Enable Telegram Alerts')
                            ->live(),

                        Forms\Components\TextInput::make('telegram_bot_token')
                            ->label('Bot Token')
                            ->password()
                            ->revealable()
                            ->visible(fn (Forms\Get $get) => $get('telegram_enabled'))
                            ->required(fn (Forms\Get $get) => $get('telegram_enabled'))
                            ->helperText('Get this from @BotFather on Telegram'),

                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Chat ID')
                            ->visible(fn (Forms\Get $get) => $get('telegram_enabled'))
                            ->required(fn (Forms\Get $get) => $get('telegram_enabled'))
                            ->helperText('Your Telegram chat ID (message @userinfobot to find it)'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::current()->update($data);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
