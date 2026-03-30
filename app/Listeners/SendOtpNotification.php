<?php

namespace App\Listeners;

use App\Events\OtpPrepared;

class SendOtpNotification
{
    public function handle(OtpPrepared $event): void
    {
        $otp = $event->otp;
        
        if (config('otp.log')) {
            \Log::info("OTP for {$otp->mobile}: {$otp->code}");
        }
    }
}
