<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address', 'fingerprint', 'reason', 'hit_count', 'blocked_until',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->blocked_until === null || now()->lt($this->blocked_until);
    }
}
