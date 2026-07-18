<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckResult extends Model
{
    protected $fillable = [
        'server_check_id', 'value', 'status',
    ];

    public function check()
    {
        return $this->belongsTo(ServerCheck::class, 'server_check_id');
    }
}