@php
    $src = $src ?? 'section 3/vector-divider.svg';
    $variant = $variant ?? 'after-fifth';

    $wrapClass = $variant === 'after-third'
        ? 'relative z-20 w-full leading-[0] pointer-events-none -my-[8px] sm:-my-[11px] md:-my-[15px] lg:-my-[19px]'
        : 'relative z-30 w-full leading-[0] pointer-events-none -mt-[14px] sm:-mt-[18px] md:-mt-[24px] lg:-mt-[28px] -mb-[2px]';

    $imgClass = $variant === 'after-third'
        ? 'mx-auto block w-full h-[16px] sm:h-[22px] md:h-[30px] lg:h-[38px] object-cover object-center select-none'
        : 'mx-auto block w-full h-[22px] sm:h-[28px] md:h-[38px] lg:h-[48px] object-cover object-center select-none';
@endphp
<div class="{{ $wrapClass }}" aria-hidden="true">
    <img
        src="{{ asset('src/images/' . $src) }}"
        alt=""
        class="{{ $imgClass }}"
        draggable="false"
    >
</div>
