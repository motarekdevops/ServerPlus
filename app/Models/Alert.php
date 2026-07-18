<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'server_id', 'rule_triggered', 'message', 'is_resolved',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}