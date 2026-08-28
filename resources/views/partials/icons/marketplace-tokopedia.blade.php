{{-- Tokopedia: tas belanja bergagang yang sekaligus wajah burung hantu "Toped".
     Mata dan paruh dilubangi (fill-rule evenodd) supaya ikut warna latar tombol -
     putih saat normal, warna merek saat hover. --}}
@php($class = $class ?? 'w-[18px] h-[18px]')
<svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    {{-- Gagang --}}
    <path d="M8.9 7.4V5.9a3.1 3.1 0 0 1 6.2 0v1.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>

    {{-- Badan tas + rongga mata + paruh --}}
    <path
        fill="currentColor"
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M5.6 7.4h12.8a2.4 2.4 0 0 1 2.4 2.4v6.6A5.6 5.6 0 0 1 15.2 22H8.8a5.6 5.6 0 0 1-5.6-5.6V9.8a2.4 2.4 0 0 1 2.4-2.4Zm3.45 3.6a2.85 2.85 0 1 0 0 5.7 2.85 2.85 0 0 0 0-5.7Zm5.9 0a2.85 2.85 0 1 0 0 5.7 2.85 2.85 0 0 0 0-5.7ZM10.65 17.3h2.7L12 19.25Z"
    />

    {{-- Pupil --}}
    <circle cx="9.05" cy="13.85" r="1.15" fill="currentColor"/>
    <circle cx="14.95" cy="13.85" r="1.15" fill="currentColor"/>
</svg>
