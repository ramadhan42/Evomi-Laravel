{{-- TikTok Shop: tas belanja melebar ke bawah dengan not TikTok di tengahnya.
     Not-nya dilubangi dari badan tas (fill-rule evenodd), jadi ikut warna latar
     tombol - putih saat normal, warna merek saat hover.

     Ukuran tas disamakan dengan ikon Shopee dan Tokopedia supaya bobot ketiganya
     seimbang saat berjajar. --}}
@php($class = $class ?? 'w-[18px] h-[18px]')
<svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    {{-- Gagang --}}
    <path d="M9.1 7.4V5.9a2.9 2.9 0 0 1 5.8 0v1.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>

    {{-- Badan tas + rongga not TikTok --}}
    <path
        fill="currentColor"
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M6.9 7.4h10.2q2 0 2.28 1.97l1.33 9.4q.39 2.79-2.44 2.79H5.73q-2.83 0-2.44-2.79l1.33-9.4Q4.9 7.4 6.9 7.4Zm6.4 2.24h-1.66v6.81a1.35 1.35 0 1 1-1.04-1.3v-1.72a3.07 3.07 0 1 0 2.7 3.02V12.96a3.74 3.74 0 0 0 2.13.68V11.98a2.08 2.08 0 0 1-2.13-2.03v-.31Z"
    />
</svg>
