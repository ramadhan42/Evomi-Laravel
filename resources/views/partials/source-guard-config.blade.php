@php
    $sourceGuardConfig = [
        'enabled' => (bool) config('security.source_guard.enabled'),
        'exemptEmail' => config('security.source_guard.exempt_email'),
    ];
@endphp
<script>
    window.EVOMI_SOURCE_GUARD = @json($sourceGuardConfig);
</script>
