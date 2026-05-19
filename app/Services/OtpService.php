<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpService
{
    /**
     * Send OTP
     */
    public function send($mobile, $type = null)
    {
        // Cooldown check
        if (cache()->has('otp_cooldown_' . $mobile)) {
            throw new \Exception("Please wait before requesting OTP again");
        }

        // Generate OTP
        $code = rand(
            config('otp.code_min'),
            config('otp.code_max')
        );

        // Create OTP
        $otp = Otp::create([
            'mobile' => $mobile,
            'code' => $code,
            'type' => $type,
            'tracking_code' => (string) Str::uuid(),
            'expires_at' => Carbon::now()->addMinutes(config('otp.expiry')),
            'status' => 'pending',
        ]);

        // Store cooldown
        cache()->put(
            'otp_cooldown_' . $mobile,
            true,
            config('otp.cooldown')
        );

        // Trigger event
        event(new \App\Events\OtpPrepared($otp));

        return $otp;
    }

    /**
     * Verify OTP
     */
    public function verify($mobile, $code, $trackingCode)
    {
        $otp = Otp::where('mobile', $mobile)
            ->where('tracking_code', $trackingCode)
            ->latest()
            ->first();

        // OTP not found
        if (!$otp) {
            return [
                'status' => false,
                'message' => 'OTP not found'
            ];
        }

        // Block check
        if ($otp->blocked_until && now()->lt($otp->blocked_until)) {
            return [
                'status' => false,
                'message' => 'Too many attempts. Try again later.'
            ];
        }

        // Expiry check
        if ($otp->expires_at < now()) {

            $otp->update([
                'status' => 'expired'
            ]);

            return [
                'status' => false,
                'message' => 'OTP expired'
            ];
        }

        // Attempt limit check
        if ($otp->attempts >= config('otp.max_attempts')) {

            $otp->update([
                'status' => 'failed',
                'blocked_until' => now()->addMinutes(5)
            ]);

            return [
                'status' => false,
                'message' => 'Maximum attempts reached'
            ];
        }

        // Invalid OTP
        if ($otp->code != $code) {

            $otp->increment('attempts');

            return [
                'status' => false,
                'message' => 'Invalid OTP'
            ];
        }

        // Success
        $otp->update([
            'is_verified' => true,
            'status' => 'verified'
        ]);

        return [
            'status' => true,
            'message' => 'OTP verified successfully'
        ];
    }
}