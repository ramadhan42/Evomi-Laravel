{{-- Tokopedia: logo resmi (raster), bukan ikon garis seperti Shopee dan TikTok
     Shop. Ukurannya disamakan lewat .evomi-mp__icon img di app.css supaya
     ketiganya sejajar; warnanya tetap warna merek, jadi tidak ikut berubah saat
     tombolnya di-hover. --}}
@php($class = $class ?? 'w-[18px] h-[18px]')
<img
    src="{{ asset('src/images/marketplace/logo-tokopedia.webp') }}"
    alt=""
    class="{{ $class }} object-contain"
    width="96"
    height="96"
    loading="lazy"
    decoding="async"
    draggable="false"
>
