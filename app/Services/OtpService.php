<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\OtpSecurityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(
        private DeviceFingerprintService $fingerprint,
        private GeoFenceService $geo
    ) {}

    public function send(string $mobile, ?string $type = null, ?Request $request = null): Otp
    {
        if (cache()->has('otp_cooldown_' . $mobile)) {
            throw new \Exception('Please wait before requesting OTP again');
        }

        $deviceInfo = $request ? $this->fingerprint->extract($request) : [];
        $geoInfo    = $request ? $this->geo->lookup($request->ip()) : [];

        $code = rand(config('otp.code_min'), config('otp.code_max'));

        $otp = Otp::create([
            'mobile'        => $mobile,
            'code'          => $code,
            'type'          => $type,
            'tracking_code' => (string) Str::uuid(),
            'expires_at'    => Carbon::now()->addMinutes(config('otp.expiry')),
            'status'        => 'pending',
            'ip_address'    => $deviceInfo['ip_address'] ?? null,
            'user_agent'    => $deviceInfo['user_agent'] ?? null,
            'fingerprint'   => $deviceInfo['fingerprint'] ?? null,
            'country'       => $geoInfo['country'] ?? null,
            'city'          => $geoInfo['city'] ?? null,
        ]);

        cache()->put('otp_cooldown_' . $mobile, true, config('otp.cooldown'));

        // Security log
        OtpSecurityLog::create([
            'mobile'     => $mobile,
            'ip_address' => $deviceInfo['ip_address'] ?? 'unknown',
            'user_agent' => $deviceInfo['user_agent'] ?? null,
            'fingerprint'=> $deviceInfo['fingerprint'] ?? null,
            'country'    => $geoInfo['country'] ?? null,
            'city'       => $geoInfo['city'] ?? null,
            'region'     => $geoInfo['region'] ?? null,
            'latitude'   => $geoInfo['latitude'] ?? null,
            'longitude'  => $geoInfo['longitude'] ?? null,
            'event_type' => 'send_otp',
            'status'     => 'success',
            'meta'       => json_encode(['tracking_code' => $otp->tracking_code]),
        ]);

        event(new \App\Events\OtpPrepared($otp));

        return $otp;
    }

    public function verify(string $mobile, string $code, string $trackingCode, ?Request $request = null): array
    {
        $deviceInfo = $request ? $this->fingerprint->extract($request) : [];
        $ip         = $deviceInfo['ip_address'] ?? 'unknown';
        $fp         = $deviceInfo['fingerprint'] ?? null;

        $otp = Otp::where('mobile', $mobile)
            ->where('tracking_code', $trackingCode)
            ->latest()
            ->first();

        if (!$otp) {
            $this->securityLog($mobile, $ip, $fp, $deviceInfo, 'verify_otp', 'failed', 'OTP not found');
            return ['status' => false, 'message' => 'OTP not found'];
        }

        if ($otp->blocked_until && now()->lt($otp->blocked_until)) {
            $this->securityLog($mobile, $ip, $fp, $deviceInfo, 'verify_otp', 'blocked', 'Blocked until ' . $otp->blocked_until);
            return ['status' => false, 'message' => 'Too many attempts. Try again later.'];
        }

        if ($otp->expires_at < now()) {
            $otp->update(['status' => 'expired']);
            $this->securityLog($mobile, $ip, $fp, $deviceInfo, 'verify_otp', 'failed', 'OTP expired');
            return ['status' => false, 'message' => 'OTP expired'];
        }

        if ($otp->attempts >= config('otp.max_attempts')) {
            $otp->update(['status' => 'failed', 'blocked_until' => now()->addMinutes(5)]);
            $this->securityLog($mobile, $ip, $fp, $deviceInfo, 'verify_otp', 'blocked', 'Max attempts reached');
            return ['status' => false, 'message' => 'Maximum attempts reached'];
        }

        if ($otp->code != $code) {
            $otp->increment('attempts');
            $this->securityLog($mobile, $ip, $fp, $deviceInfo, 'verify_otp', 'failed', 'Invalid OTP code');
            return ['status' => false, 'message' => 'Invalid OTP'];
        }

        $otp->update(['is_verified' => true, 'status' => 'verified']);
        $this->securityLog($mobile, $ip, $fp, $deviceInfo, 'verify_otp', 'success', 'OTP verified');

        return ['status' => true, 'message' => 'OTP verified successfully'];
    }

    private function securityLog(string $mobile, string $ip, ?string $fp, array $device, string $event, string $status, string $reason): void
    {
        OtpSecurityLog::create([
            'mobile'     => $mobile,
            'ip_address' => $ip,
            'user_agent' => $device['user_agent'] ?? null,
            'fingerprint'=> $fp,
            'event_type' => $event,
            'status'     => $status,
            'meta'       => json_encode(['reason' => $reason]),
        ]);
    }
}
