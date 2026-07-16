<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    /**
     * Generate a fingerprint hash from request signals.
     * Combines IP + UA + Accept headers + language for a stable device signature.
     */
    public function generate(Request $request): string
    {
        $signals = implode('|', [
            $request->ip(),
            $request->userAgent() ?? '',
            $request->header('Accept-Language') ?? '',
            $request->header('Accept-Encoding') ?? '',
            $request->header('Accept') ?? '',
        ]);

        return hash('sha256', $signals);
    }

    /**
     * Extract structured device info from request.
     */
    public function extract(Request $request): array
    {
        $ua = $request->userAgent() ?? '';

        return [
            'ip_address'  => $request->ip(),
            'user_agent'  => $ua,
            'fingerprint' => $this->generate($request),
            'is_bot'      => $this->detectBot($ua),
            'browser'     => $this->parseBrowser($ua),
            'platform'    => $this->parsePlatform($ua),
        ];
    }

    private function detectBot(string $ua): bool
    {
        $botPatterns = ['bot', 'crawl', 'spider', 'curl', 'wget', 'python', 'scrapy', 'httpclient'];
        $ua = strtolower($ua);
        foreach ($botPatterns as $pattern) {
            if (str_contains($ua, $pattern)) return true;
        }
        return false;
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Chrome'))  return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari'))  return 'Safari';
        if (str_contains($ua, 'Edge'))    return 'Edge';
        if (str_contains($ua, 'Opera'))   return 'Opera';
        return 'Unknown';
    }

    private function parsePlatform(string $ua): string
    {
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac'))     return 'macOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        return 'Unknown';
    }
}
