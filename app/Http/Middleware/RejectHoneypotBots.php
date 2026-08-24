<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectHoneypotBots
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.honeypot.enabled')) {
            return $next($request);
        }

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        foreach (config('security.honeypot.fields', []) as $field) {
            if (trim((string) $request->input($field)) === '') {
                continue;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'OK',
                ]);
            }

            return redirect()->back();
        }

        return $next($request);
    }
}
