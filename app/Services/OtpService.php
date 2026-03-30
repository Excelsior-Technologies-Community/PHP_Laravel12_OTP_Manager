<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpService
{
    public function send($mobile, $type = null)
    {
        if (cache()->has('otp_cooldown_' . $mobile)) {
            throw new \Exception("Please wait before requesting OTP again");
        }
        $code = rand(config('otp.code_min'), config('otp.code_max'));

        $otp = Otp::create([
            'mobile' => $mobile,
            'code' => $code,
            'type' => $type,
            'tracking_code' => (string) Str::uuid(),
            'expires_at' => Carbon::now()->addMinutes(config('otp.expiry')),
        ]);

        cache()->put('otp_cooldown_' . $mobile, true, config('otp.cooldown'));

        event(new \App\Events\OtpPrepared($otp));

        return $otp;
    }
    public function verify($mobile, $code, $trackingCode)
    {
        $otp = Otp::where('mobile', $mobile)
            ->where('tracking_code', $trackingCode)
            ->latest()
            ->first();

        if (!$otp) return ['status' => false, 'message' => 'OTP not found'];

        if ($otp->expires_at < now()) return ['status' => false, 'message' => 'OTP expired'];

        if ($otp->attempts >= config('otp.max_attempts'))
            return ['status' => false, 'message' => 'Max attempts reached'];

        if ($otp->code != $code) {
            $otp->increment('attempts');
            return ['status' => false, 'message' => 'Invalid OTP'];
        }
        $otp->delete();

        return ['status' => true, 'message' => 'OTP verified successfully'];
    }
}
