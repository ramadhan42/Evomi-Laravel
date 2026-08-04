<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastSeen
{
    /**
     * Refresh last_seen_at for authenticated API users (throttled).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user instanceof User) {
            return $response;
        }

        $shouldTouch = $user->last_seen_at === null
            || $user->last_seen_at->lt(now()->subMinutes(2));

        if ($shouldTouch) {
            User::query()
                ->whereKey($user->id)
                ->update(['last_seen_at' => now()]);
        }

        return $response;
    }
}
