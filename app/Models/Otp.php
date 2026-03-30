<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'mobile',
        'code',
        'type',
        'tracking_code',
        'attempts',
        'expires_at'
    ];
}