<?php

use App\Http\Middleware\BlockProbePaths;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RejectHoneypotBots;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UpdateUserLastSeen;
use App\Http\Middleware\VerifyTurnstile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: [
            'evomi_locale',
        ]);

        // env() di sini tidak bisa diganti config(): berkas ini dieksekusi sebelum
        // config ter-load. Konsekuensinya, setelah `php artisan config:cache` nilai
        // di .env diabaikan dan default di bawah yang dipakai. Aman selama default
        // ini sama dengan .env — kalau nilainya perlu diubah per-environment,
        // pindahkan dulu ke config/ dan baca dari sana lewat middleware terpisah.
        if (filter_var(env('SECURITY_API_THROTTLE', true), FILTER_VALIDATE_BOOL)) {
            $middleware->throttleApi(env('SECURITY_API_THROTTLE_LIMIT', '120,1'));
        }

        $middleware->append([
            BlockProbePaths::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'last.seen' => UpdateUserLastSeen::class,
            'honeypot' => RejectHoneypotBots::class,
            'turnstile' => VerifyTurnstile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('orders:expire-unpaid')->everyFiveMinutes();
    })->create();
