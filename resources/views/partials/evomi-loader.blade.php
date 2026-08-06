{{-- Full-page loader (parity with Next.js LoadingScreen) — first visit / hard refresh only --}}
<div
    id="evomi-loader"
    class="evomi-loader"
    aria-busy="true"
    aria-live="polite"
    role="status"
>
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
