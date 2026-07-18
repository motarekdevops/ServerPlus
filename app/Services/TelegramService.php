<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    public function send(string $message): bool
    {
        $setting = Setting::current();

        if (! $setting->telegram_enabled || ! $setting->telegram_bot_token || ! $setting->telegram_chat_id) {
            return false;
        }

        $response = Http::post("https://api.telegram.org/bot{$setting->telegram_bot_token}/sendMessage", [
            'chat_id' => $setting->telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        return $response->successful();
    }
}
