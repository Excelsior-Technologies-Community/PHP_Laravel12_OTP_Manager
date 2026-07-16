<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoFenceService
{
    /**
     * Lookup geo data for an IP address.
     * Uses ip-api.com free tier (no key needed, 45 req/min).
     * Results cached for 24 hours per IP.
     */
    public function lookup(string $ip): array
    {
        // Skip private/loopback IPs
        if ($this->isPrivateIp($ip)) {
            return $this->emptyGeo();
        }

        return Cache::remember("geo_{$ip}", 86400, function () use ($ip) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon,isp,query");

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === 'success') {
                        return [
                            'country'   => $data['country'] ?? null,
                            'region'    => $data['regionName'] ?? null,
                            'city'      => $data['city'] ?? null,
                            'latitude'  => $data['lat'] ?? null,
                            'longitude' => $data['lon'] ?? null,
                            'isp'       => $data['isp'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable) {
                // Geo lookup failure should never break OTP flow
            }

            return $this->emptyGeo();
        });
    }

    private function isPrivateIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1']) ||
               filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function emptyGeo(): array
    {
        return ['country' => null, 'region' => null, 'city' => null, 'latitude' => null, 'longitude' => null, 'isp' => null];
    }
}
