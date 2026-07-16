<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'mobile', 'code', 'type', 'tracking_code',
        'attempts', 'is_verified', 'status', 'blocked_until', 'expires_at',
        'ip_address', 'user_agent', 'fingerprint', 'country', 'city',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'blocked_until'=> 'datetime',
        'is_verified'  => 'boolean',
    ];
}
