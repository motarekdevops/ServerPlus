<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'username', 'private_key',
        'group', 'status', 'last_checked_at', 'last_error',
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'last_checked_at' => 'datetime',
    ];

    public function checks()
    {
        return $this->hasMany(ServerCheck::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}