<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpLookup
{
    /**
     * Resolve the real public client IP (Cloudflare / reverse proxy aware).
     */
    public function clientIp(Request $request): ?string
    {
        $candidates = [
            $request->headers->get('CF-Connecting-IP'),
            $request->headers->get('True-Client-IP'),
            $request->headers->get('X-Real-IP'),
        ];

        $forwarded = (string) $request->headers->get('X-Forwarded-For', '');
        if ($forwarded !== '') {
            foreach (explode(',', $forwarded) as $part) {
                $candidates[] = trim($part);
            }
        }

        $candidates[] = $request->ip();

        foreach ($candidates as $candidate) {
            $ip = trim((string) $candidate);
            if ($ip === '') {
                continue;
            }
            // Strip port from IPv4:port
            if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $ip, $m)) {
                $ip = $m[1];
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * @return array{country:?string,country_code:?string,region:?string,city:?string,ip:?string}
     */
    public function lookup(?string $ip): array
    {
        $empty = [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
            'ip' => $ip,
        ];

        $ip = trim((string) $ip);

        // Local / private IP: on local env, resolve public egress IP so dashboard still shows a country.
        if ($ip === '' || $this->isPrivateIp($ip)) {
            if (app()->environment('local')) {
                $public = $this->lookupPublicSelf();
                if (($public['country'] ?? null) !== null) {
                    return $public;
                }
            }

            return array_merge($empty, [
                'country' => 'Local / Private',
                'country_code' => 'LO',
                'city' => 'Localhost',
                'ip' => $ip !== '' ? $ip : null,
            ]);
        }

        $cacheKey = 'geoip:v2:'.md5($ip);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($ip, $empty) {
            $result = $this->fetchIpWhoIs($ip);
            if (($result['country'] ?? null) !== null) {
                return array_merge($empty, $result, ['ip' => $ip]);
            }

            $result = $this->fetchIpApi($ip);
            if (($result['country'] ?? null) !== null) {
                return array_merge($empty, $result, ['ip' => $ip]);
            }

            Log::warning('GeoIpLookup empty result', ['ip' => $ip]);

            return array_merge($empty, ['ip' => $ip]);
        });
    }

    /**
     * Ask geo API for the server's outbound public IP (useful for local testing).
     *
     * @return array{country:?string,country_code:?string,region:?string,city:?string,ip:?string}
     */
    private function lookupPublicSelf(): array
    {
        return Cache::remember('geoip:v2:self', now()->addHours(6), function () {
            $empty = [
                'country' => null,
                'country_code' => null,
                'region' => null,
                'city' => null,
                'ip' => null,
            ];

            try {
                $response = Http::timeout(4)
                    ->acceptJson()
                    ->get('https://ipwho.is/', [
                        'fields' => 'success,ip,country,country_code,region,city',
                    ]);

                if (! $response->ok()) {
                    return $empty;
                }

                $data = $response->json();
                if (! is_array($data) || empty($data['success'])) {
                    return $empty;
                }

                return [
                    'country' => $this->strOrNull($data['country'] ?? null),
                    'country_code' => $this->strOrNull($data['country_code'] ?? null),
                    'region' => $this->strOrNull($data['region'] ?? null),
                    'city' => $this->strOrNull($data['city'] ?? null),
                    'ip' => $this->strOrNull($data['ip'] ?? null),
                ];
            } catch (\Throwable $e) {
                Log::warning('GeoIpLookup self-lookup failed', [
                    'error' => $e->getMessage(),
                ]);

                return $empty;
            }
        });
    }

    /**
     * @return array{country:?string,country_code:?string,region:?string,city:?string}
     */
    private function fetchIpWhoIs(string $ip): array
    {
        $empty = [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
        ];

        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->get("https://ipwho.is/{$ip}", [
                    'fields' => 'success,country,country_code,region,city',
                ]);

            if (! $response->ok()) {
                return $empty;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data['success'])) {
                return $empty;
            }

            return [
                'country' => $this->strOrNull($data['country'] ?? null),
                'country_code' => $this->strOrNull($data['country_code'] ?? null),
                'region' => $this->strOrNull($data['region'] ?? null),
                'city' => $this->strOrNull($data['city'] ?? null),
            ];
        } catch (\Throwable $e) {
            Log::warning('GeoIpLookup ipwho.is failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * Fallback provider (HTTP, no key needed for light usage).
     *
     * @return array{country:?string,country_code:?string,region:?string,city:?string}
     */
    private function fetchIpApi(string $ip): array
    {
        $empty = [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
        ];

        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,countryCode,regionName,city',
                ]);

            if (! $response->ok()) {
                return $empty;
            }

            $data = $response->json();
            if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
                return $empty;
            }

            return [
                'country' => $this->strOrNull($data['country'] ?? null),
                'country_code' => $this->strOrNull($data['countryCode'] ?? null),
                'region' => $this->strOrNull($data['regionName'] ?? null),
                'city' => $this->strOrNull($data['city'] ?? null),
            ];
        } catch (\Throwable $e) {
            Log::warning('GeoIpLookup ip-api failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    public function isPrivateIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function strOrNull(mixed $value): ?string
    {
        $v = trim((string) $value);

        return $v === '' ? null : mb_substr($v, 0, 120);
    }
}
