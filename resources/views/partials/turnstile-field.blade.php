@php
    $tsTheme = $theme ?? 'light';
    $tsScope = $scope ?? '';
    $tsMountId = $mountId ?? null;

    $tsHas = $tsScope.'hasTurnstile';
    $tsStatus = $tsScope.'turnstileStatus';
    $tsRun = $tsScope.'runTurnstile()';

    $tsIdle = evomi_l('Saya bukan robot', "I'm not a robot");
    $tsPending = evomi_l('Memverifikasi...', 'Verifying...');
    $tsError = evomi_l('Verifikasi gagal. Klik untuk coba lagi.', 'Verification failed. Click to retry.');
    $tsDone = evomi_l('Terverifikasi', 'Verified');
@endphp

<div class="evomi-turnstile evomi-turnstile--{{ $tsTheme }}" x-show="{{ $tsHas }}" x-cloak>
    <button
        type="button"
        class="evomi-turnstile__box"
        :class="{{ $tsStatus }} === 'error' ? 'is-error' : ''"
        x-show="{{ $tsStatus }} !== 'verified'"
        :disabled="{{ $tsStatus }} === 'pending'"
        @click="{{ $tsRun }}"
    >
        <span class="evomi-turnstile__mark" :class="{{ $tsStatus }} === 'pending' ? 'is-loading' : ''">
            <svg
                x-show="{{ $tsStatus }} === 'pending'"
                class="evomi-turnstile__spinner"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
        </span>

        <span class="evomi-turnstile__text">
            <span x-text="{{ $tsStatus }} === 'pending' ? @js($tsPending) : ({{ $tsStatus }} === 'error' ? @js($tsError) : @js($tsIdle))">{{ $tsIdle }}</span>
        </span>

        <span class="evomi-turnstile__brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.5c0 4.4 3.1 8.5 7.5 9.5 4.4-1 7.5-5.1 7.5-9.5V6L12 3Z" />
            </svg>
            Cloudflare
        </span>
    </button>

    <div
        class="evomi-turnstile__done"
        x-show="{{ $tsStatus }} === 'verified'"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
    >
        <span class="evomi-turnstile__done-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </span>
        {{ $tsDone }}
    </div>

    <div
        class="evomi-turnstile__mount"
        @if ($tsMountId) id="{{ $tsMountId }}" @else x-ref="turnstile" @endif
    ></div>
</div>
