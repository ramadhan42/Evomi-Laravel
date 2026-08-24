<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockProbePaths
{
    /** @var list<string> */
    private const PROBE_FRAGMENTS = [
        '.env',
        'wp-admin',
        'wp-login',
        'wp-content',
        'phpmyadmin',
        'phpinfo',
        'vendor/phpunit',
        'storage/logs',
        'actuator',
        'cgi-bin',
        '.git',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.block_probes.enabled')) {
            return $next($request);
        }

        $path = strtolower($request->path());

        foreach (self::PROBE_FRAGMENTS as $fragment) {
            if (str_contains($path, $fragment)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
