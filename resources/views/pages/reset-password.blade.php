@extends('layouts.app')

@section('title', 'Atur Password Baru | Evomi')

@section('content')
<x-auth-shell>
    <div
        class="space-y-8 relative"
        x-data="evomiResetPassword(@js($token), @js($email))"
    >
        <div class="text-center space-y-2">
            <h1 class="text-4xl md:text-5xl font-bold text-white tracking-tight uppercase">Password Baru</h1>
            <p class="text-blue-100/80 font-light italic text-sm">Buat password baru untuk akun Evomi Anda</p>
        </div>

        <div
            x-show="error"
            x-cloak
            x-transition
            class="bg-red-500/20 border border-red-400/40 rounded-2xl px-5 py-3 text-sm text-white text-center"
            x-text="error"
        ></div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="reset-email" class="text-xs font-semibold text-white/80 uppercase tracking-widest ml-1">Email</label>
                <input
                    id="reset-email"
                    type="email"
                    x-model="form.email"
                    required
                    readonly
                    autocomplete="email"
                    class="w-full bg-white/5 border border-white/15 rounded-2xl px-5 py-4 text-white/70 outline-none cursor-not-allowed"
                >
            </div>

            <div class="space-y-2">
                <label for="reset-password" class="text-xs font-semibold text-white/80 uppercase tracking-widest ml-1">Password Baru</label>
                <div class="relative">
                    <input
                        id="reset-password"
                        :type="showPassword ? 'text' : 'password'"
                        x-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
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

            <div class="space-y-2">
                <label for="reset-password-confirm" class="text-xs font-semibold text-white/80 uppercase tracking-widest ml-1">Ulangi Password</label>
                <input
                    id="reset-password-confirm"
                    :type="showPassword ? 'text' : 'password'"
                    x-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full bg-white/10 border border-white/20 rounded-2xl px-5 py-4 text-white placeholder:text-white/40 focus:bg-white/20 focus:border-white/40 outline-none transition-all duration-200"
                >
            </div>

            @include('partials.turnstile-field', ['theme' => 'dark'])

            <button
                type="submit"
                :disabled="loading"
                class="w-full bg-white text-[#1172ba] font-bold py-4 rounded-2xl shadow-lg shadow-blue-950/10 hover:bg-blue-50 active:scale-[0.99] transition-all uppercase tracking-widest text-sm mt-4 disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span x-text="loading ? 'Menyimpan...' : 'Simpan Password Baru'"></span>
            </button>
        </form>

        <div class="text-center pt-2">
            <p class="text-sm text-white/70">
                Tautan sudah kedaluwarsa?
                <a href="{{ route('password.request') }}" data-soft-nav class="text-white font-bold hover:underline underline-offset-4 tracking-wider">MINTA ULANG</a>
            </p>
        </div>

        @include('partials.auth-modal')
    </div>
</x-auth-shell>
@endsection
