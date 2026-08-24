@extends('layouts.app')

@section('title', 'Login | Evomi')

@section('content')
<x-auth-shell>
    <div class="space-y-8 relative" x-data="evomiAuth('login')">
        <div class="text-center space-y-2">
            <h1 class="text-4xl md:text-5xl font-bold text-white tracking-tight uppercase">Masuk</h1>
            <p class="text-blue-100/80 font-light italic text-sm">Lanjutkan perjalanan Anda bersama Evomi</p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="login-email" class="text-xs font-semibold text-white/80 uppercase tracking-widest ml-1">Email</label>
                <input
                    id="login-email"
                    type="email"
                    x-model="form.email"
                    required
                    autocomplete="email"
                    placeholder="Masukkan email Anda"
                    class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4 text-white placeholder:text-white/40 focus:bg-white/20 focus:border-white/40 outline-none transition-all duration-200"
                >
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center ml-1">
                    <label for="login-password" class="text-xs font-semibold text-white/80 uppercase tracking-widest">Password</label>
                    <a href="{{ route('password.request') }}" data-soft-nav class="text-[10px] text-white/60 hover:text-white uppercase tracking-wider transition-colors">Lupa?</a>
                </div>
                <div class="relative">
                    <input
                        id="login-password"
                        :type="showPassword ? 'text' : 'password'"
                        x-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4 pr-12 text-white placeholder:text-white/40 focus:bg-white/20 focus:border-white/40 outline-none transition-all duration-200"
                    >
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-white/55 hover:text-white transition-colors"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    >
                        <template x-if="!showPassword">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </template>
                        <template x-if="showPassword">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </template>
                    </button>
                </div>
            </div>

            @include('partials.turnstile-field', ['theme' => 'dark'])

            <button
                type="submit"
                :disabled="loading"
                class="w-full bg-white text-[#1172ba] font-bold py-4 rounded-2xl shadow-lg shadow-blue-950/10 hover:bg-blue-50 active:scale-[0.99] transition-all uppercase tracking-widest text-sm mt-4 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span x-text="loading ? 'Memproses...' : 'Masuk Sekarang'"></span>
            </button>
        </form>

        <div class="text-center pt-2">
            <p class="text-sm text-white/70">
                Belum punya akun?
                <a href="{{ route('register') }}" data-soft-nav class="text-white font-bold hover:underline underline-offset-4 tracking-wider">DAFTAR</a>
            </p>
        </div>

        {{-- Modal --}}
        <div
            x-show="modal.show"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
            @keydown.escape.window="closeModal()"
        >
            <div
                class="bg-[#1172ba] border border-white/20 rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl"
                @click.outside="closeModal()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/10 backdrop-blur-sm">
                    <template x-if="modal.type === 'success'">
                        <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <template x-if="modal.type === 'warning'">
                        <svg class="h-8 w-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="modal.type === 'error'">
                        <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xl font-bold text-white uppercase tracking-wide" x-text="modal.title"></h3>
                    <p class="text-sm text-blue-100/80 font-light leading-relaxed" x-text="modal.message"></p>
                </div>

                <button
                    type="button"
                    class="w-full font-bold py-3 rounded-xl transition-all uppercase tracking-wider text-xs shadow-md active:scale-[0.98]"
                    :class="modal.type === 'success'
                        ? 'bg-green-500 text-white hover:bg-green-600'
                        : modal.type === 'warning'
                            ? 'bg-amber-500 text-white hover:bg-amber-600'
                            : 'bg-white text-[#1172ba] hover:bg-blue-50'"
                    @click="closeModal()"
                    x-text="modal.cta"
                ></button>
            </div>
        </div>
    </div>
</x-auth-shell>
@endsection
