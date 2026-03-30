<?php

namespace App\Events;

use App\Models\Otp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpPrepared
{
    use Dispatchable, SerializesModels;
    public Otp $otp;
    public function __construct(Otp $otp)
    {
        $this->otp = $otp;
    }
}
