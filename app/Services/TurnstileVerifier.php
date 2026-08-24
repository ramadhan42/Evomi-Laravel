<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $secret = config('security.turnstile.secret_key');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if (is_string($remoteIp) && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->ok()) {
            return false;
        }

        return (bool) $response->json('success');
    }
}
