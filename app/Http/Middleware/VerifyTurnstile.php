<?php

namespace App\Http\Middleware;

use App\Services\TurnstileVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyTurnstile
{
    public function __construct(
        private readonly TurnstileVerifier $turnstile,
    ) {}

    /**
     * @param  string|null  $mode  null      = verifikasi di setiap request.
     *                             "session" = user yang login cukup verifikasi
     *                                         sekali per jendela waktu (lihat
     *                                         security.turnstile.session_minutes),
     *                                         tamu tetap wajib tiap request.
     *                                         Dipakai chat supaya tidak perlu
     *                                         centang captcha tiap kirim pesan.
     */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        if (! config('security.turnstile.enabled')) {
            return $next($request);
        }

        $user = auth('sanctum')->user();
        $sessionKey = null;
        if ($mode === 'session' && $user) {
            $sessionKey = 'turnstile-session:user:'.$user->getAuthIdentifier();
            $scope = $request->input('captcha_scope');
            if (is_string($scope) && preg_match('/^[A-Za-z0-9._:-]{1,80}$/', $scope)) {
                $sessionKey .= ':'.$scope;
            }

            if (Cache::has($sessionKey)) {
                return $next($request);
            }
        }

        $token = $request->input('captcha_token') ?? $request->input('cf-turnstile-response');

        if (! is_string($token) || trim($token) === '') {
            return $this->reject('Verifikasi keamanan wajib diisi.');
        }

        if (! $this->turnstile->verify($token, $request->ip())) {
            return $this->reject('Verifikasi keamanan gagal. Silakan coba lagi.');
        }

        if ($sessionKey) {
            Cache::put(
                $sessionKey,
                true,
                now()->addMinutes((int) config('security.turnstile.session_minutes', 30)),
            );
        }

        return $next($request);
    }

    /**
     * Flag captcha_required dipakai frontend untuk memunculkan kembali widget
     * yang sudah disembunyikan setelah verifikasi sebelumnya.
     */
    private function reject(string $message): Response
    {
        return response()->json([
            'success' => false,
            'captcha_required' => true,
            'message' => $message,
        ], 422);
    }
}
