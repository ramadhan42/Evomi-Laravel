@extends('layouts.app')

@php
    $shareTitle = (string) ($product['title'] ?? 'Evomi');
    $shareDescription = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($product['description'] ?? ''))));
    if ($shareDescription === '') {
        $shareDescription = evomi_l(
            'Temukan keharuman eksklusif Evomi yang mencerminkan kepribadian Anda.',
            'Discover exclusive Evomi fragrances that reflect your personality.'
        );
    }
    $shareDescriptionShort = \Illuminate\Support\Str::limit($shareDescription, 180, '…');
    // Prefer static cached JPEG under /storage/share (Twitter-friendly, no PHP middleware).
    // Fallback to dynamic route if cache file is missing.
    $shareCacheRel = 'share/product-'.$product['id'].'.jpg';
    $shareCache = storage_path('app/public/'.$shareCacheRel);
    if (is_file($shareCache)) {
        $shareImage = asset('storage/'.$shareCacheRel).'?v='.filemtime($shareCache);
    } else {
        $shareImage = url('/share/product/'.$product['id'].'.jpg').'?v='.time();
    }
    $shareUrl = url()->current();
@endphp

@section('title', $shareTitle.' | Evomi')
@section('meta_description', $shareDescriptionShort)

@push('head')
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Evomi">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'id_ID' }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:title" content="{{ $shareTitle }}">
    <meta property="og:description" content="{{ $shareDescriptionShort }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:secure_url" content="{{ $shareImage }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $shareTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $shareTitle }}">
    <meta name="twitter:description" content="{{ $shareDescriptionShort }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <meta name="twitter:image:alt" content="{{ $shareTitle }}">
    <link rel="canonical" href="{{ $shareUrl }}">
@endpush

@section('content')
    <div class="bg-white flex flex-col justify-center items-center w-full overflow-visible relative z-0">
        @include('belanja.detail')
    </div>
@endsection
