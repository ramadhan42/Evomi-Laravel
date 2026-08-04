@extends('layouts.app')

@section('title', $title . ' | Evomi')

@section('content')
    <section class="min-h-[60vh] flex flex-col items-center justify-center px-6 py-20 bg-white text-center">
        <h1 class="text-[28px] md:text-[42px] font-bold text-[#1172BA] mb-3">{{ $title }}</h1>
        <p class="text-[#5D5D5D] text-[14px] md:text-[16px] max-w-lg mb-8">
            {{ $description ?? 'Halaman ini sedang diport dari frontend Next.js Evomi.' }}
        </p>
        <a
            href="{{ route('beranda') }}"
            class="inline-flex items-center gap-2 bg-[#1172BA] text-white text-[13px] md:text-[14px] px-6 py-2.5 rounded-full font-semibold hover:bg-[#0e5d99] transition-colors"
        >
            Kembali ke Beranda
        </a>
    </section>
@endsection
