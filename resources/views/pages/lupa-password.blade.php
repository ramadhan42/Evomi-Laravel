@extends('layouts.app')

@section('title', 'Lupa Password | Evomi')

@section('content')
<x-auth-shell>
    <div class="space-y-8 relative" x-data="evomiForgotPassword()">
        <div class="text-center space-y-2">
            <h1 class="text-4xl md:text-5xl font-bold text-white tracking-tight uppercase">Lupa Password</h1>
            <p class="text-blue-100/80 font-light italic text-sm">Kami kirimkan tautan untuk mengatur ulang password Anda</p>
        </div>

        {{-- Form permintaan tautan --}}
        <div x-show="!sent" x-cloak>
            <div
                x-show="error"
                x-cloak
                x-transition
                class="bg-red-500/20 border border-red-400/40 rounded-2xl px-5 py-3 text-sm text-white text-center mb-5"
                x-text="error"
            ></div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <label for="forgot-email" class="text-xs font-semibold text-white/80 uppercase tracking-widest ml-1">Email Terdaftar</label>
                    <input
                        id="forgot-email"
                        type="email"
                        x-model="form.email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="w-full bg-white border border-transparent rounded-2xl px-5 py-4 text-gray-900 placeholder:text-gray-400 focus:border-white focus:ring-2 focus:ring-white/60 outline-none transition-all duration-200"
                    >
                    <p class="text-[11px] text-white/50 leading-relaxed ml-1">
                        Masukkan email yang Anda pakai saat mendaftar. Tautan reset berlaku 60 menit.
                    </p>
                </div>

                {{-- Honeypot: manusia tidak akan pernah mengisi kolom ini --}}
                <input type="text" x-model="form._hp" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden">

                @include('partials.turnstile-field', ['theme' => 'dark'])

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-white text-[#1172ba] font-bold py-4 rounded-2xl shadow-lg shadow-blue-950/10 hover:bg-blue-50 active:scale-[0.99] transition-all uppercase tracking-widest text-sm mt-4 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span x-text="loading ? 'Mengirim...' : 'Kirim Tautan Reset'"></span>
                </button>
            </form>
        </div>

        {{-- Konfirmasi setelah tautan dikirim --}}
        <div x-show="sent" x-cloak x-transition class="space-y-5 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/10 backdrop-blur-sm">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h2 class="text-xl font-bold text-white uppercase tracking-wide">Cek Email Anda</h2>
                <p class="text-sm text-blue-100/80 font-light leading-relaxed">
                    Jika <span class="font-semibold text-white" x-text="sentTo"></span> terdaftar di Evomi,
                    tautan reset password sudah meluncur ke kotak masuk Anda. Jangan lupa cek folder spam.
                </p>
            </div>

            <button
                type="button"
                @click="reopen()"
                class="w-full border border-white/25 text-white font-bold py-3 rounded-2xl hover:bg-white/10 active:scale-[0.99] transition-all uppercase tracking-widest text-xs"
            >
                Kirim Ulang / Ganti Email
            </button>
        </div>

        <div class="text-center pt-2">
            <p class="text-sm text-white/70">
                Ingat password Anda?
                <a href="{{ route('login') }}" data-soft-nav class="text-white font-bold hover:underline underline-offset-4 tracking-wider">MASUK</a>
            </p>
        </div>

        @include('partials.auth-modal')
    </div>
</x-auth-shell>
@endsection
