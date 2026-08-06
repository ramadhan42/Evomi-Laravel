@php
    // $src   : Alpine expression returning the image URL
    // $alt   : Alpine expression for alt text (optional)
    // $size  : tailwind size classes for the frame
    // $fit   : 'contain' for packshots/bottles, 'cover' for photos
    $src = $src ?? "''";
    $alt = $alt ?? "''";
    $size = $size ?? 'h-14 w-14';
    $fit = ($fit ?? 'contain') === 'cover' ? 'object-cover' : 'object-contain';
    $pad = $fit === 'object-contain' ? 'p-1.5' : '';
    $rounded = $rounded ?? 'rounded-xl';
@endphp
<div
    x-data="{ src: {{ $src }}, broken: false }"
    x-effect="src = {{ $src }}; broken = false"
    class="{{ $size }} {{ $rounded }} shrink-0 overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center"
>
    <img
        x-show="src && !broken"
        :src="src"
        :alt="{{ $alt }}"
        loading="lazy"
        decoding="async"
        class="h-full w-full {{ $fit }} {{ $pad }}"
        x-on:error="broken = true"
    >
    <svg
        x-show="!src || broken"
        class="h-1/2 w-1/2 text-gray-300"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        aria-hidden="true"
    >
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <circle cx="8.5" cy="8.5" r="1.5"/>
        <path d="m21 15-5-5L5 21"/>
    </svg>
</div>
