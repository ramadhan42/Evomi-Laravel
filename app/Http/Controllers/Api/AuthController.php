<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken; // Pastikan ini di-import
use Throwable;

class AuthController extends Controller
{
    /**
     * Umur token, ditentukan centang "Biarkan saya tetap masuk".
     *
     * Tanpa centang itu token tetap diberi batas, jadi sesi yang tertinggal di
     * perangkat pinjaman ikut mati sendiri alih-alih berlaku selamanya.
     */
    private static function tokenExpiry(Carbon $now, bool $remember): Carbon
    {
        return $remember
            ? $now->copy()->addDays(max(1, (int) config('evomi.auth.remember_days', 30)))
            : $now->copy()->addHours(max(1, (int) config('evomi.auth.session_hours', 12)));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $now = now();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'password_changed_at' => $now,
            'is_admin' => false,
            'last_login_at' => $now,
            'last_seen_at' => $now,
        ]);

        $remember = $request->boolean('remember');
        $expiresAt = self::tokenExpiry($now, $remember);
        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'remember' => $remember,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Kredensial salah.']]);
        }

        // Pastikan email admin dari .env selalu punya flag is_admin
        $adminEmail = config('evomi.development_admin.email');
        if (is_string($adminEmail) && $adminEmail !== '' && strcasecmp($user->email, $adminEmail) === 0) {
            if (! $user->is_admin) {
                $user->forceFill(['is_admin' => true])->save();
            }
        }

        $now = now();
        $user->forceFill([
            'last_login_at' => $now,
            'last_seen_at' => $now,
        ])->save();

        $remember = $request->boolean('remember');
        $expiresAt = self::tokenExpiry($now, $remember);
        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => $user->fresh(),
            'token' => $token,
            'remember' => $remember,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /**
     * Lupa Password langkah 1: kirim tautan reset berisi token sekali pakai.
     *
     * Response-nya selalu sama baik email terdaftar maupun tidak, supaya form
     * ini tidak bisa dipakai memetakan alamat email mana saja yang punya akun.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $generic = response()->json([
            'success' => true,
            'message' => 'Jika email tersebut terdaftar, kami sudah mengirim tautan reset password. Silakan cek kotak masuk maupun folder spam Anda.',
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim email reset password', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email reset gagal dikirim karena kendala server. Coba lagi beberapa saat lagi.',
            ], 500);
        }

        // RESET_THROTTLED tetap dibalas generik supaya tidak membocorkan bahwa
        // email tersebut ada; user cukup memakai tautan yang sudah dikirim.
        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan reset password tidak dapat diproses. Silakan coba lagi.',
            ], 422);
        }

        return $generic;
    }

    /**
     * Lupa Password langkah 2: tukar token dari email dengan password baru.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'password_changed_at' => now(),
                ])->save();

                // Password berganti berarti sesi lama harus mati: kalau akun ini
                // sempat dibajak, token Sanctum penyerang ikut hangus.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => $status === Password::INVALID_TOKEN
                    ? 'Tautan reset sudah kedaluwarsa atau pernah dipakai. Silakan minta tautan baru.'
                    : 'Reset password gagal. Pastikan tautan yang Anda buka masih berlaku.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login dengan password baru Anda.',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Logout khusus via navigator.sendBeacon
     */
    public function logoutBeacon(Request $request)
    {
        // Ambil token string dari body request FormData
        $tokenString = $request->input('token');

        if ($tokenString) {
            // Karena plainTextToken Sanctum biasanya berbentuk "id|token_hash",
            // kita cari token asli tersebut menggunakan static method findToken dari Sanctum
            $token = PersonalAccessToken::findToken($tokenString);

            if ($token) {
                // Hapus token dari database (menghancurkan sesi login)
                $token->delete();

                return response()->json(['status' => 'success', 'message' => 'Beacon logout successful']);
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Token invalid or not found'], 400);
    }
}
