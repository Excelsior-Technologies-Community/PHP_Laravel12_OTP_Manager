<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\OtpSecurityLog;
use App\Services\DeviceFingerprintService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OtpRateLimiter
{
    public function __construct(private DeviceFingerprintService $fingerprint) {}

    public function handle(Request $request, Closure $next): Response
    {
        $mobile = $request->input('mobile');
        $ip     = $request->ip();
        $fp     = $this->fingerprint->generate($request);
        $ua     = $request->userAgent() ?? '';

        // 1. Mobile required
        if (!$mobile) {
            return $this->blocked($request, 'Mobile number is required');
        }

        // 2. Bot detection
        $botPatterns = ['bot', 'crawl', 'spider', 'curl', 'wget', 'python', 'scrapy'];
        foreach ($botPatterns as $pattern) {
            if (str_contains(strtolower($ua), $pattern)) {
                $this->logAndBlock($ip, $fp, $mobile, 'Bot detected: ' . $ua);
                return $this->blocked($request, 'Automated requests are not allowed.');
            }
        }

        // 3. Check blocked IP table
        $blocked = BlockedIp::where('ip_address', $ip)
            ->orWhere('fingerprint', $fp)
            ->first();

        if ($blocked && $blocked->isActive()) {
            OtpSecurityLog::create([
                'mobile'     => $mobile,
                'ip_address' => $ip,
                'fingerprint'=> $fp,
                'user_agent' => $ua,
                'event_type' => 'send_otp',
                'status'     => 'blocked',
                'meta'       => json_encode(['reason' => $blocked->reason]),
            ]);
            return $this->blocked($request, 'Your device/IP is temporarily blocked. Try again later.');
        }

        // 4. IP-based rate limit: max 5 OTP requests per 10 minutes
        $ipKey = 'otp_ip_limit_' . $ip;
        $ipCount = (int) cache()->get($ipKey, 0);

        if ($ipCount >= 5) {
            $this->logAndBlock($ip, $fp, $mobile, 'IP rate limit exceeded');
            return $this->blocked($request, 'Too many requests from your IP. Please wait 10 minutes.');
        }

        cache()->put($ipKey, $ipCount + 1, 600);

        // 5. Fingerprint-based rate limit: max 3 per 5 minutes
        $fpKey = 'otp_fp_limit_' . $fp;
        $fpCount = (int) cache()->get($fpKey, 0);

        if ($fpCount >= 3) {
            $this->logAndBlock($ip, $fp, $mobile, 'Device fingerprint rate limit exceeded');
            return $this->blocked($request, 'Too many OTP requests from this device. Please wait.');
        }

        cache()->put($fpKey, $fpCount + 1, 300);

        return $next($request);
    }

    private function logAndBlock(string $ip, string $fp, string $mobile, string $reason): void
    {
        OtpSecurityLog::create([
            'mobile'     => $mobile,
            'ip_address' => $ip,
            'fingerprint'=> $fp,
            'event_type' => 'send_otp',
            'status'     => 'blocked',
            'meta'       => json_encode(['reason' => $reason]),
        ]);

        $existing = BlockedIp::where('ip_address', $ip)->first();

        if ($existing) {
            $existing->increment('hit_count');
            $existing->update([
                'blocked_until' => now()->addMinutes(30),
                'reason'        => $reason,
            ]);
        } else {
            BlockedIp::create([
                'ip_address'    => $ip,
                'fingerprint'   => $fp,
                'reason'        => $reason,
                'blocked_until' => now()->addMinutes(30),
            ]);
        }
    }

    private function blocked(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => false, 'message' => $message], 429);
        }
        return back()->with('error', $message);
    }
}
