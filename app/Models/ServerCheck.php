<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerCheck extends Model
{
    protected $fillable = [
        'server_id', 'type', 'warning_threshold', 'critical_threshold', 'is_active', 'last_alerted_at',
        'domain', 'ssl_issued_at', 'ssl_expires_at', 'ssl_last_renewed_at',
        'domain_registration_expires_at', 'alert_days_before',
    ];

    protected $casts = [
        'last_alerted_at' => 'datetime',
        'ssl_issued_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'ssl_last_renewed_at' => 'datetime',
        'domain_registration_expires_at' => 'date',
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
