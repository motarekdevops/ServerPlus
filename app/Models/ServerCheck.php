<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerCheck extends Model
{
    protected $fillable = [
        'server_id', 'type', 'warning_threshold', 'critical_threshold', 'is_active', 'last_alerted_at',
    ];

    protected $casts = [
        'last_alerted_at' => 'datetime',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function results()
    {
        return $this->hasMany(CheckResult::class);
    }
}
