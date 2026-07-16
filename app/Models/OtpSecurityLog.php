<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpSecurityLog extends Model
{
    protected $fillable = [
        'mobile', 'ip_address', 'user_agent', 'fingerprint',
        'country', 'city', 'region', 'latitude', 'longitude',
        'event_type', 'status', 'meta',
    ];
}
