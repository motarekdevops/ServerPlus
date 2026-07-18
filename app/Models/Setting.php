<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'email_enabled', 'email_address',
        'telegram_enabled', 'telegram_bot_token', 'telegram_chat_id',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'telegram_enabled' => 'boolean',
        'telegram_bot_token' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
