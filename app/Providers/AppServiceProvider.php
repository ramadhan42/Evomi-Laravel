<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configurePasswordReset();

        Model::preventLazyLoading(! $this->app->isProduction());

        $appUrl = config('app.url');

        if (is_string($appUrl) && $appUrl !== '') {
            URL::forceRootUrl(rtrim($appUrl, '/'));

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }

    private function configurePasswordReset(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            $minutes = (int) config('auth.passwords.users.expire', 60);

            return (new MailMessage)
                ->subject('Reset Password Akun Evomi')
                ->greeting('Halo!')
                ->line('Kami menerima permintaan reset password untuk akun Evomi yang memakai alamat email ini.')
                ->action('Atur Password Baru', $url)
                ->line("Tautan di atas hanya berlaku {$minutes} menit dan hanya bisa dipakai satu kali.")
                ->line('Jika Anda tidak pernah meminta reset password, abaikan saja email ini. Password Anda tidak berubah.')
                ->salutation('Salam hangat, Tim Evomi');
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($email !== '' ? $email : $request->ip()),
            ];
        });

        RateLimiter::for('auth-register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        // Satu email hanya boleh dimintakan tautan reset 3x/jam supaya kotak
        // masuk korban tidak bisa dibanjiri lewat form publik ini.
        RateLimiter::for('auth-forgot', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(3)->by('forgot-ip:'.$request->ip()),
                Limit::perHour(3)->by('forgot-email:'.($email !== '' ? $email : $request->ip())),
            ];
        });

        RateLimiter::for('auth-reset', fn (Request $request) => Limit::perMinute(6)->by('reset-ip:'.$request->ip()));

        // Tamu mengisi form kontak dibatasi ketat per IP, sedangkan percakapan
        // milik user yang sudah login dihitung per akun supaya satu jaringan
        // bersama (kantor/kampus) tidak saling menghabiskan kuota.
        RateLimiter::for('contact-post', function (Request $request) {
            $user = auth('sanctum')->user();

            if ($user) {
                return Limit::perMinute(10)
                    ->by('contact-user:'.$user->getAuthIdentifier())
                    ->response(fn () => response()->json([
                        'success' => false,
                        'message' => 'Maksimal 10 pesan per menit. Tunggu sebentar sebelum mengirim lagi ya.',
                    ], 429));
            }

            return Limit::perMinute(5)
                ->by('contact-ip:'.$request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.',
                ], 429));
        });

        RateLimiter::for('newsletter', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('guest-checkout', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        RateLimiter::for('traffic-ping', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
