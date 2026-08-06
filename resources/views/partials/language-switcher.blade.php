{{--
  Language switcher — parity with Next.js LanguageSwitcher.tsx
  @param string $variant  dark|light  (default: dark)
  @param string $size     sm|md|admin (default: md)
  @param string $class    optional extra classes
--}}
@php
    $variant = $variant ?? 'dark';
    $size = $size ?? 'md';
    $isLight = $variant === 'light';
    $isAdmin = $size === 'admin';

    if ($isAdmin) {
        $wrapClass = 'admin-lang-switch';
    } else {
        $track = $isLight ? 'bg-white/20 text-white/70' : 'bg-gray-100 text-gray-500';
        $activeClass = $isLight ? 'bg-white text-[#1172BA] shadow-sm' : 'bg-white text-gray-900 shadow-sm';
        $pad = $size === 'sm' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-[11px] md:text-[12px]';
        $wrapClass = "inline-flex items-center gap-0.5 p-0.5 rounded-full {$track}";
    }
@endphp

@if ($isAdmin)
    <div
        data-no-locale-fx
        class="{{ $wrapClass }} {{ $class ?? '' }}"
        role="group"
        aria-label="Language"
    >
        <span
            class="admin-lang-thumb"
            :class="locale === 'en' ? 'is-en' : 'is-id'"
            aria-hidden="true"
        ></span>
        <button
            type="button"
            class="admin-lang-btn"
            :class="locale === 'id' ? 'is-active' : ''"
            :aria-pressed="(locale === 'id').toString()"
            aria-label="Switch language to ID"
            @click.stop="setLocale('id')"
        >ID</button>
        <button
            type="button"
            class="admin-lang-btn"
            :class="locale === 'en' ? 'is-active' : ''"
            :aria-pressed="(locale === 'en').toString()"
            aria-label="Switch language to EN"
            @click.stop="setLocale('en')"
        >EN</button>
    </div>
@else
    <div
        data-no-locale-fx
        class="{{ $wrapClass }} {{ $class ?? '' }}"
        role="group"
        aria-label="Language"
    >
        <button
            type="button"
            class="{{ $pad }} rounded-full font-semibold tracking-wide transition-all duration-200"
            :class="locale === 'id' ? '{{ $activeClass }}' : 'hover:text-inherit'"
            :aria-pressed="(locale === 'id').toString()"
            aria-label="Switch language to ID"
            @click.stop="setLocale('id')"
        >ID</button>
        <button
            type="button"
            class="{{ $pad }} rounded-full font-semibold tracking-wide transition-all duration-200"
            :class="locale === 'en' ? '{{ $activeClass }}' : 'hover:text-inherit'"
            :aria-pressed="(locale === 'en').toString()"
            aria-label="Switch language to EN"
            @click.stop="setLocale('en')"
        >EN</button>
    </div>
@endif
