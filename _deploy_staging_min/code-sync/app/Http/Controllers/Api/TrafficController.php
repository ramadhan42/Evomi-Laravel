<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\GeoIpLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TrafficController extends Controller
{
    public function __construct(private GeoIpLookup $geo)
    {
    }

    /**
     * Storefront beacon: record page view / heartbeat for guest or logged-in user.
     */
    public function ping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visitor_key' => 'nullable|uuid',
            'path' => 'nullable|string|max:500',
            'full_url' => 'nullable|string|max:1000',
            'referrer' => 'nullable|string|max:1000',
            'heartbeat' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $this->normalizePath((string) $request->input('path', '/'));
        if ($this->shouldSkipPath($path)) {
            return response()->json(['success' => true, 'skipped' => true]);
        }

        $visitorKey = (string) ($request->input('visitor_key') ?: Str::uuid());
        $user = $request->user('sanctum');
        $visitorType = $user instanceof User ? SiteVisit::TYPE_USER : SiteVisit::TYPE_GUEST;
        $rawIp = $this->geo->clientIp($request);
        $ua = (string) $request->userAgent();
        $now = now();
        $parsedUa = $this->parseUserAgent($ua);
        $geo = $this->geo->lookup($rawIp);
        // Prefer public IP returned by geo provider when client IP was private (local/dev).
        $ip = $geo['ip'] ?? $rawIp;

        $recent = SiteVisit::query()
            ->where('visitor_key', $visitorKey)
            ->where('path', $path)
            ->where('last_seen_at', '>=', $now->copy()->subSeconds(90))
            ->latest('id')
            ->first();

        if ($recent) {
            $recent->fill([
                'user_id' => $user?->id ?? $recent->user_id,
                'visitor_type' => $visitorType,
                'ip_address' => $ip ?: $recent->ip_address,
                'country' => $geo['country'] ?? $recent->country,
                'country_code' => $geo['country_code'] ?? $recent->country_code,
                'region' => $geo['region'] ?? $recent->region,
                'city' => $geo['city'] ?? $recent->city,
                'full_url' => $request->input('full_url') ?: $recent->full_url,
                'referrer' => $request->input('referrer') ?: $recent->referrer,
                'user_agent' => $ua !== '' ? mb_substr($ua, 0, 500) : $recent->user_agent,
                'device' => $parsedUa['device'],
                'browser' => $parsedUa['browser'],
                'platform' => $parsedUa['platform'],
                'last_seen_at' => $now,
            ]);
            $recent->save();
            $visit = $recent;
        } else {
            $visit = SiteVisit::create([
                'visitor_key' => $visitorKey,
                'user_id' => $user?->id,
                'visitor_type' => $visitorType,
                'ip_address' => $ip,
                'country' => $geo['country'],
                'country_code' => $geo['country_code'],
                'region' => $geo['region'],
                'city' => $geo['city'],
                'path' => $path,
                'full_url' => $request->input('full_url'),
                'referrer' => $request->input('referrer'),
                'user_agent' => $ua !== '' ? mb_substr($ua, 0, 500) : null,
                'device' => $parsedUa['device'],
                'browser' => $parsedUa['browser'],
                'platform' => $parsedUa['platform'],
                'visited_at' => $now,
                'last_seen_at' => $now,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'visitor_key' => $visitorKey,
                'visit_id' => $visit->id,
                'visitor_type' => $visitorType,
            ],
        ]);
    }

    /**
     * Admin: live traffic feed + summary stats.
     * List is unique per visitor identity (user/email, or IP for guests).
     */
    public function index(Request $request)
    {
        $type = strtolower((string) $request->query('type', 'all'));
        $search = trim((string) $request->query('q', ''));
        $limit = min(200, max(20, (int) $request->query('limit', 80)));
        $onlineSince = now()->subSeconds(SiteVisit::ONLINE_WINDOW_SECONDS);
        $todayStart = now()->startOfDay();

        $base = SiteVisit::query()->with(['user:id,name,nama_lengkap,email,is_admin']);

        if (in_array($type, [SiteVisit::TYPE_GUEST, SiteVisit::TYPE_USER], true)) {
            $base->where('visitor_type', $type);
        }

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('visitor_key', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('nama_lengkap', 'like', "%{$search}%");
                    });
            });
        }

        // Fetch a wider window then collapse to one row per identity.
        $raw = (clone $base)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit(max(400, $limit * 8))
            ->get();

        $seen = [];
        $items = [];
        foreach ($raw as $visit) {
            $keys = $this->visitorIdentityKeys($visit);
            $duplicate = false;
            foreach ($keys as $key) {
                if (isset($seen[$key])) {
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate) {
                continue;
            }
            foreach ($keys as $key) {
                $seen[$key] = true;
            }
            $items[] = $this->serializeVisit($visit, $onlineSince);
            if (count($items) >= $limit) {
                break;
            }
        }

        $onlineBreakdown = $this->countOnlineBreakdown($onlineSince);
        $todayGuest = $this->countDistinctIdentities(
            visitorType: SiteVisit::TYPE_GUEST,
            since: $todayStart,
            column: 'visited_at',
        );
        $todayUser = $this->countDistinctIdentities(
            visitorType: SiteVisit::TYPE_USER,
            since: $todayStart,
            column: 'visited_at',
        );
        $todayViews = SiteVisit::query()
            ->where('visited_at', '>=', $todayStart)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'online_now' => $onlineBreakdown['now'],
                    'online_guest' => $onlineBreakdown['guest'],
                    'online_user' => $onlineBreakdown['user'],
                    'today_guest' => $todayGuest,
                    'today_user' => $todayUser,
                    'today_views' => $todayViews,
                    'online_window_seconds' => SiteVisit::ONLINE_WINDOW_SECONDS,
                    'generated_at' => now()->toIso8601String(),
                ],
                'items' => $items,
            ],
        ]);
    }

    /**
     * Identity keys used to collapse repeats.
     * Same user id, email, or IP → treated as one visitor.
     *
     * @return list<string>
     */
    private function visitorIdentityKeys(SiteVisit $visit): array
    {
        $keys = [];

        if ($visit->user_id) {
            $keys[] = 'user:'.(int) $visit->user_id;
        }

        $email = strtolower(trim((string) ($visit->user?->email ?? '')));
        if ($email !== '') {
            $keys[] = 'email:'.$email;
        }

        $ip = trim((string) ($visit->ip_address ?? ''));
        if ($ip !== '') {
            $keys[] = 'ip:'.$ip;
        }

        if ($keys === []) {
            $keys[] = 'vk:'.(string) $visit->visitor_key;
        }

        return $keys;
    }

    /**
     * Unique online visitors, classified by the newest identity's type.
     * Example: same IP with user+guest rows → counts as 1 user (if user is newer).
     *
     * @return array{now:int,user:int,guest:int}
     */
    private function countOnlineBreakdown($onlineSince): array
    {
        // Pull a slightly wider SQL window, then confirm online in PHP
        // (same rule as table is_online) to avoid timezone SQL mismatches.
        $rows = SiteVisit::query()
            ->with(['user:id,email'])
            ->where(function ($q) use ($onlineSince) {
                $q->where('last_seen_at', '>=', $onlineSince->copy()->subMinutes(30))
                    ->orWhere('visited_at', '>=', $onlineSince->copy()->subMinutes(30));
            })
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $seen = [];
        $user = 0;
        $guest = 0;

        foreach ($rows as $visit) {
            $lastSeen = $visit->last_seen_at ?? $visit->visited_at;
            if (! $lastSeen || $lastSeen->lt($onlineSince)) {
                continue;
            }

            $keys = $this->visitorIdentityKeys($visit);
            $duplicate = false;
            foreach ($keys as $key) {
                if (isset($seen[$key])) {
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate) {
                continue;
            }
            foreach ($keys as $key) {
                $seen[$key] = true;
            }

            if ($visit->visitor_type === SiteVisit::TYPE_USER || $visit->user_id) {
                $user++;
            } else {
                $guest++;
            }
        }

        return [
            'now' => $user + $guest,
            'user' => $user,
            'guest' => $guest,
        ];
    }

    private function countDistinctIdentities(?string $visitorType, $since, string $column): int
    {
        $query = SiteVisit::query()
            ->with(['user:id,email'])
            ->where($column, '>=', $since)
            ->orderByDesc($column)
            ->orderByDesc('id')
            ->limit(1000);

        if ($visitorType !== null) {
            $query->where('visitor_type', $visitorType);
        }

        $rows = $query->get();

        $seen = [];
        $count = 0;
        foreach ($rows as $visit) {
            $keys = $this->visitorIdentityKeys($visit);
            $duplicate = false;
            foreach ($keys as $key) {
                if (isset($seen[$key])) {
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate) {
                continue;
            }
            foreach ($keys as $key) {
                $seen[$key] = true;
            }
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVisit(SiteVisit $v, $onlineSince): array
    {
        $lastSeen = $v->last_seen_at ?? $v->visited_at;
        $user = $v->user;

        return [
            'id' => $v->id,
            'visitor_key' => $v->visitor_key,
            'visitor_type' => $v->visitor_type,
            'is_online' => $lastSeen && $lastSeen->gte($onlineSince),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->nama_lengkap ?: $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
            ] : null,
            'ip_address' => $v->ip_address,
            'country' => $v->country,
            'country_code' => $v->country_code,
            'region' => $v->region,
            'city' => $v->city,
            'path' => $v->path,
            'full_url' => $v->full_url,
            'referrer' => $v->referrer,
            'device' => $v->device,
            'browser' => $v->browser,
            'platform' => $v->platform,
            'visited_at' => optional($v->visited_at)?->toIso8601String(),
            'last_seen_at' => optional($lastSeen)?->toIso8601String(),
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return mb_substr($path, 0, 500);
    }

    private function shouldSkipPath(string $path): bool
    {
        $lower = strtolower($path);

        return str_starts_with($lower, '/dashboard')
            || str_starts_with($lower, '/api')
            || str_starts_with($lower, '/up')
            || str_starts_with($lower, '/build')
            || str_starts_with($lower, '/storage');
    }

    /**
     * @return array{device:string,browser:string,platform:string}
     */
    private function parseUserAgent(string $ua): array
    {
        $uaLower = strtolower($ua);
        $device = 'desktop';
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone|blackberry/i', $ua)) {
            $device = 'mobile';
        }

        $browser = 'Other';
        if (str_contains($uaLower, 'edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($uaLower, 'chrome') && ! str_contains($uaLower, 'edg/')) {
            $browser = 'Chrome';
        } elseif (str_contains($uaLower, 'safari') && ! str_contains($uaLower, 'chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($uaLower, 'firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($uaLower, 'opera') || str_contains($uaLower, 'opr/')) {
            $browser = 'Opera';
        }

        $platform = 'Other';
        if (str_contains($uaLower, 'windows')) {
            $platform = 'Windows';
        } elseif (str_contains($uaLower, 'android')) {
            $platform = 'Android';
        } elseif (str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ipad') || str_contains($uaLower, 'ios')) {
            $platform = 'iOS';
        } elseif (str_contains($uaLower, 'mac os') || str_contains($uaLower, 'macintosh')) {
            $platform = 'macOS';
        } elseif (str_contains($uaLower, 'linux')) {
            $platform = 'Linux';
        }

        return compact('device', 'browser', 'platform');
    }
}
