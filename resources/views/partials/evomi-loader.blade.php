{{-- Full-page loader (parity with Next.js LoadingScreen) — first visit / hard refresh only --}}
@php
    /**
     * Optional video loading screen. The first candidate that exists wins;
     * without any of them the loader keeps its animated orb, so a missing
     * file can never break the page.
     */
    $loaderMedia = static function (?string $value): ?string {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return str_starts_with($value, 'http') ? $value : asset(ltrim($value, '/'));
    };

    $loaderSources = [];
    $configuredVideo = trim((string) config('evomi.loader_video'));

    if ($configuredVideo !== '') {
        $loaderSources[] = [
            'src' => $loaderMedia($configuredVideo),
            'type' => str_ends_with(strtolower($configuredVideo), '.webm') ? 'video/webm' : 'video/mp4',
        ];
    } else {
        $loaderCandidates = [
            'storage/loading-screen/loading-screen.webm' => 'video/webm',
            'storage/loading-screen/loading-screen.mp4' => 'video/mp4',
            'videos/loading-screen.webm' => 'video/webm',
            'videos/loading-screen.mp4' => 'video/mp4',
        ];

        foreach ($loaderCandidates as $path => $mime) {
            if (is_file(public_path($path))) {
                $loaderSources[] = [
                    'src' => asset($path).'?v='.filemtime(public_path($path)),
                    'type' => $mime,
                ];
            }
        }
    }

    $loaderPoster = $loaderMedia(config('evomi.loader_video_poster'))
        ?? (is_file(public_path('storage/loading-screen/loading-screen.jpg'))
            ? asset('storage/loading-screen/loading-screen.jpg')
            : null);
@endphp
<div
    id="evomi-loader"
    class="evomi-loader{{ $loaderSources !== [] ? ' has-video' : '' }}"
    aria-busy="true"
    aria-live="polite"
    role="status"
>
    @if ($loaderSources !== [])
        <video
            id="evomi-loader-video"
            class="evomi-loader-video"
            muted
            playsinline
            disablepictureinpicture
            preload="auto"
            tabindex="-1"
            aria-hidden="true"
            @if ($loaderPoster) poster="{{ $loaderPoster }}" @endif
        >
            @foreach ($loaderSources as $source)
                <source src="{{ $source['src'] }}" type="{{ $source['type'] }}">
            @endforeach
        </video>
        <div class="evomi-loader-scrim" aria-hidden="true"></div>
    @endif

    <div class="evomi-loader-inner">
        <div class="evomi-loader-orb" aria-hidden="true">
            <div class="evomi-pulse-ring"></div>
            <div class="evomi-orbit-ring"></div>
            <div class="evomi-loader-dot"></div>
        </div>

        <div class="evomi-loader-copy">
            <p class="evomi-loader-brand">EVOMI</p>
            <p class="evomi-loader-tagline">Every Version of Me</p>
        </div>

        <div class="evomi-loader-track" aria-hidden="true">
            <div id="evomi-loader-bar" class="evomi-loader-bar" style="width: 8%"></div>
        </div>
    </div>
</div>
