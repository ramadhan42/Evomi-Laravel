{{-- Custom cursor — same CDN & size as evomi.shop (96px, hotspot 33 30) --}}
{{-- Applied on html/body so teleported modals (outside .evomi-site) keep the CDN cursor --}}
@php
    $cursor = config('evomi.cursor', []);
    $enabled = filter_var($cursor['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $cdn = $cursor['cdn'] ?? 'https://cdn.cursors-4u.net/previews/normal-9e607e2c-48.webp';
    $hx = (int) ($cursor['hotspot_x'] ?? 33);
    $hy = (int) ($cursor['hotspot_y'] ?? 30);
@endphp
@if ($enabled)
<style id="evomi-custom-cursor">
    html,
    body,
    body *,
    .evomi-site,
    .evomi-site *,
    .admin-shell,
    .admin-shell *,
    [class*="evomi-"][class*="-modal"],
    [class*="evomi-"][class*="-modal"] *,
    [class*="evomi-"][class*="modal"],
    [class*="evomi-"][class*="modal"] * {
        cursor:
            url('{{ $cdn }}') {{ $hx }} {{ $hy }},
            auto !important;
    }
</style>
@endif
