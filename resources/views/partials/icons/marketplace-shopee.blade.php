{{-- Shopee: tas belanja padat bergagang dengan huruf "S" dilubangi.
     Disamakan bobotnya dengan ikon Tokopedia dan TikTok Shop supaya ketiganya
     terbaca sebagai satu set.

     "S" dilubangi lewat <mask> - bukan diwarnai putih - karena latar tombol
     berubah saat hover (putih -> warna merek), jadi huruf ini harus tembus. --}}
@php($class = $class ?? 'w-[18px] h-[18px]')
@php($shopeeMaskId = 'evomi-shopee-s-' . uniqid())
<svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <mask id="{{ $shopeeMaskId }}" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24">
        <rect width="24" height="24" fill="#fff"/>
        <path d="M13.7 12.6c-.5-.5-1.2-.8-1.9-.8-1 0-1.7.5-1.7 1.3 0 .8.7 1.1 1.8 1.5 1.2.4 2 .9 2 2 0 1.1-1 1.8-2.2 1.8-.9 0-1.7-.3-2.3-.9" stroke="#000" stroke-width="1.7" stroke-linecap="round" fill="none"/>
    </mask>

    {{-- Gagang --}}
    <path d="M8.9 7.4V5.9a3.1 3.1 0 0 1 6.2 0v1.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>

    {{-- Badan tas, sedikit meruncing ke bawah seperti lambang aslinya --}}
    <path
        fill="currentColor"
        mask="url(#{{ $shopeeMaskId }})"
        d="M5.6 7.4h12.8a2.2 2.2 0 0 1 2.19 2.42l-.95 9.5A2.9 2.9 0 0 1 16.76 22H7.24a2.9 2.9 0 0 1-2.88-2.68l-.95-9.5A2.2 2.2 0 0 1 5.6 7.4Z"
    />
</svg>
