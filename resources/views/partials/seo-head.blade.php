{{--
    Shared search/social head tags.

    Fed either by SiteSeo::forPage() (the dashboard SEO menu) or by
    ArticleSeo::forArticle() (per-article fields) - both return the same shape,
    so nothing here needs to know which one it got.
--}}
@php
    $seoOgType = $seo['og_type'] ?? 'website';
    $seoImage = trim((string) ($seo['image'] ?? ''));
    $seoArticle = $article ?? null;
@endphp

<meta name="robots" content="{{ $seo['robots'] }}">
@if (($seo['keywords_string'] ?? '') !== '')
    <meta name="keywords" content="{{ $seo['keywords_string'] }}">
@endif
<link rel="canonical" href="{{ $seo['canonical'] }}">

<meta property="og:type" content="{{ $seoOgType }}">
<meta property="og:site_name" content="Evomi">
<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
@if ($seoImage !== '')
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:secure_url" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="{{ $seo['title'] }}">
@endif

@if ($seoOgType === 'article' && $seoArticle)
    @if (! empty($seoArticle['published_at_iso']))
        <meta property="article:published_time" content="{{ $seoArticle['published_at_iso'] }}">
    @endif
    @if (! empty($seoArticle['updated_at']))
        <meta property="article:modified_time" content="{{ $seoArticle['updated_at'] }}">
    @endif
    @if (! empty($seoArticle['category']))
        <meta property="article:section" content="{{ $seoArticle['category'] }}">
    @endif
    @foreach ($seo['keywords'] ?? [] as $seoKeyword)
        <meta property="article:tag" content="{{ $seoKeyword }}">
    @endforeach
    @if (! empty($seoArticle['author']))
        <meta name="author" content="{{ $seoArticle['author'] }}">
    @endif
@endif

<meta name="twitter:card" content="{{ $seoImage !== '' ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if ($seoImage !== '')
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $seo['title'] }}">
@endif

@if (! empty($seo['schema']))
    <script type="application/ld+json">{!! json_encode($seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
