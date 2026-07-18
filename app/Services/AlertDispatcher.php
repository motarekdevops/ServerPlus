<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Setting;
use App\Notifications\ServerAlertNotification;
use Illuminate\Support\Facades\Notification;

class AlertDispatcher
{
    public function __construct(protected TelegramService $telegram) {}

    public function dispatch(Alert $alert): void
    {
        $setting = Setting::current();

        if ($setting->email_enabled && $setting->email_address) {
            Notification::route('mail', $setting->email_address)
                ->notify(new ServerAlertNotification($alert));
        }

        if ($setting->telegram_enabled) {
            $message = "🔴 <b>ServerPlus Alert</b>\n\n"
                . "Server: {$alert->server->name}\n"
                . "Host: {$alert->server->host}\n"
                . "Rule: {$alert->rule_triggered}\n"
                . "{$alert->message}";

            $this->telegram->send($message);
        }
    }
}
