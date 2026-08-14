@extends('layouts.app')

@section('title', evomi_l('Pengaturan Profil | Evomi', 'Profile Settings | Evomi'))

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileSettings" class="profile-page-card evomi-soft-enter">
        <div
            x-show="loading"
            x-cloak
            class="profile-page-card__loader absolute inset-0 z-10"
        >
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat profil...', 'Loading profile...') }}</p>
        </div>

        <div
            x-show="!loading"
            x-cloak
            class="profile-page-card__body"
        >
            <div class="relative shrink-0 px-5 sm:px-7 py-5 text-white" data-soft-enter="up" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 55%, #0e6aad 100%)">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
                <div class="relative flex items-start gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.223.52.337.317.198.71.24 1.04.08l1.21-.59a1.125 1.125 0 0 1 1.45.42l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .674c-.01.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.449.42l-1.211-.59a1.14 1.14 0 0 0-1.04.078 7.94 7.94 0 0 1-.519.338c-.332.183-.582.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a7.957 7.957 0 0 1-.52-.337 1.14 1.14 0 0 0-1.04-.08l-1.21.59a1.125 1.125 0 0 1-1.45-.42l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a7.723 7.723 0 0 1 0-.675c.007-.378-.138-.75-.43-.991l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.45-.42l1.21.59c.33.16.723.118 1.04-.078.172-.114.346-.236.52-.337.332-.184.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    </span>
                    <div class="min-w-0 pt-0.5">
                        <h1 class="text-lg sm:text-xl font-bold tracking-tight">{{ evomi_l('Pengaturan Profil', 'Profile Settings') }}</h1>
                        <p class="text-[12px] sm:text-sm text-white/80 font-medium mt-0.5">{{ evomi_l('Perbarui foto, kontak, dan alamat pengiriman utama Anda.', 'Update your photo, contact info, and default shipping address.') }}</p>
                    </div>
                </div>
            </div>

            <div class="profile-page-card__scroll p-5 sm:p-7 bg-white" data-soft-enter="up">
                <div
                    x-show="status.message"
                    x-cloak
                    class="mb-5 p-4 rounded-2xl text-sm font-medium flex items-center gap-3 border"
                    :class="{
                        'bg-emerald-50 border-emerald-100 text-emerald-800': status.type === 'success',
                        'bg-rose-50 border-rose-100 text-rose-800': status.type === 'error',
                        'bg-white border-slate-200 text-slate-700': status.type === 'processing' || !status.type
                    }"
                >
                    <div x-show="status.type === 'processing'" class="w-5 h-5 border-2 border-slate-200 border-t-[#1172BA] rounded-full animate-spin shrink-0"></div>
                    <svg x-show="status.type === 'error'" class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    <span x-text="status.message"></span>
                </div>

                <form @submit.prevent="save" class="space-y-5 max-w-3xl bg-white rounded-2xl border border-gray-100 p-5 sm:p-7">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="group/login relative rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 flex items-start gap-3 transition-colors hover:border-slate-200 hover:bg-white">
                            <span class="mt-0.5 w-9 h-9 rounded-xl bg-white border border-slate-100 flex items-center justify-center shrink-0 shadow-sm text-[#1172BA]">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Last login</p>
                                <p class="text-sm font-semibold text-slate-800 mt-0.5" x-text="lastLoginLabel"></p>
                            </div>
                            <div class="pointer-events-none absolute left-1/2 top-[calc(100%+10px)] z-30 w-[min(100%,17.5rem)] -translate-x-1/2 opacity-0 scale-95 translate-y-1 transition-all duration-200 group-hover/login:opacity-100 group-hover/login:scale-100 group-hover/login:translate-y-0">
                                <div class="relative rounded-2xl border border-slate-200/90 bg-slate-900 px-3.5 py-3 text-left shadow-[0_18px_40px_-18px_rgba(15,23,42,0.55)]">
                                    <span class="absolute -top-1.5 left-1/2 h-3 w-3 -translate-x-1/2 rotate-45 rounded-[3px] border-l border-t border-slate-200/90 bg-slate-900"></span>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ evomi_l('Login berhasil terakhir', 'Last successful login') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-white" x-text="lastLoginLabel"></p>
                                    <p class="mt-1.5 text-[11px] text-slate-400 leading-relaxed">{{ evomi_l('Dicatat setiap kali Anda masuk ke akun Evomi.', 'Recorded each time you sign in to your Evomi account.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="group/seen relative rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 flex items-start gap-3 transition-colors hover:border-slate-200 hover:bg-white">
                            <span class="mt-0.5 w-9 h-9 rounded-xl bg-white border border-slate-100 flex items-center justify-center shrink-0 shadow-sm text-[#1172BA]">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Last seen</p>
                                <p class="text-sm font-semibold text-slate-800 mt-0.5" x-text="lastSeenLabel"></p>
                            </div>
                            <div class="pointer-events-none absolute left-1/2 top-[calc(100%+10px)] z-30 w-[min(100%,17.5rem)] -translate-x-1/2 opacity-0 scale-95 translate-y-1 transition-all duration-200 group-hover/seen:opacity-100 group-hover/seen:scale-100 group-hover/seen:translate-y-0">
                                <div class="relative rounded-2xl border border-slate-200/90 bg-slate-900 px-3.5 py-3 text-left shadow-[0_18px_40px_-18px_rgba(15,23,42,0.55)]">
                                    <span class="absolute -top-1.5 left-1/2 h-3 w-3 -translate-x-1/2 rotate-45 rounded-[3px] border-l border-t border-slate-200/90 bg-slate-900"></span>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ evomi_l('Waktu tepat', 'Exact time') }}</p>
                                    <p class="mt-1 text-sm font-semibold text-white" x-text="lastSeenExact"></p>
                                    <p class="mt-1.5 text-[11px] text-slate-400 leading-relaxed">{{ evomi_l('Diperbarui saat Anda aktif memakai aplikasi dalam keadaan login.', 'Updated while you are actively using the app while signed in.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-5 pb-2">
                        <div class="relative">
                            <div class="h-24 w-24 rounded-full border-4 border-white overflow-hidden flex items-center justify-center ring-2 ring-[#1172BA]/20 bg-[#1172BA]/10">
                                <template x-if="avatarPreview">
                                    <img :src="avatarPreview" alt="{{ evomi_l('Foto profil', 'Profile photo') }}" class="h-full w-full object-cover" x-on:error="onAvatarError()">
                                </template>
                                <template x-if="!avatarPreview">
                                    <span class="text-3xl font-bold text-[#1172BA]" x-text="initial"></span>
                                </template>
                            </div>
                            <button type="button" class="absolute bottom-0 right-0 text-white p-2 rounded-full bg-[#1172BA] hover:opacity-90 transition-opacity" @click="$refs.avatarInput.click()" aria-label="{{ evomi_l('Ganti foto profil', 'Change profile photo') }}">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                            </button>
                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" x-ref="avatarInput" @change="onAvatarChange">
                        </div>
                        <div class="text-center sm:text-left">
                            <p class="text-sm font-semibold text-slate-900">{{ evomi_l('Foto Profil', 'Profile Photo') }}</p>
                            <p class="text-xs text-slate-500 mt-1 max-w-xs">{{ evomi_l('JPG atau PNG, maks. 2MB.', 'JPG or PNG, max. 2MB.') }}</p>
                            <p x-show="avatarPath && !avatarFile" x-cloak class="text-[11px] text-emerald-600 font-medium mt-1.5">{{ evomi_l('Foto sudah tersimpan', 'Photo saved') }}</p>
                            <p x-show="avatarFile" x-cloak class="text-[11px] text-amber-600 font-medium mt-1.5">{{ evomi_l('Foto baru siap diunggah', 'New photo ready to upload') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1 mb-2">{{ evomi_l('Nama Lengkap', 'Full Name') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                </span>
                                <input type="text" x-model="form.name" required class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 outline-none transition-all bg-white text-sm text-slate-900 font-medium focus:ring-2 focus:ring-[#1172BA]/30 focus:border-[#1172BA]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1 mb-2">{{ evomi_l('Alamat Email', 'Email Address') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                </span>
                                <input type="email" x-model="form.email" required class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 outline-none transition-all bg-white text-sm text-slate-900 font-medium focus:ring-2 focus:ring-[#1172BA]/30 focus:border-[#1172BA]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1 mb-2">{{ evomi_l('Nomor Telepon', 'Phone Number') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                </span>
                                <input type="tel" x-model="form.phone" maxlength="20" placeholder="08xxxxxxxxxx" class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 outline-none transition-all bg-white text-sm text-slate-900 font-medium focus:ring-2 focus:ring-[#1172BA]/30 focus:border-[#1172BA]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1 mb-2">
                                {{ evomi_l('Kata Sandi Baru', 'New Password') }}
                                <span class="text-slate-400 font-normal lowercase italic">{{ evomi_l('(kosongkan jika tidak diubah)', '(leave blank to keep unchanged)') }}</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                </span>
                                <input :type="showPassword ? 'text' : 'password'" x-model="form.password" placeholder="••••••••" autocomplete="new-password" minlength="8" class="w-full pl-11 pr-12 py-3.5 rounded-2xl border border-slate-200 outline-none transition-all bg-white text-sm text-slate-900 focus:ring-2 focus:ring-[#1172BA]/30 focus:border-[#1172BA]">
                                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-700" @click="showPassword = !showPassword" :aria-label="showPassword ? $L('Sembunyikan kata sandi', 'Hide password') : $L('Tampilkan kata sandi', 'Show password')">
                                    <svg x-show="!showPassword" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    <svg x-show="showPassword" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1 mb-2">{{ evomi_l('Alamat Pengiriman Default', 'Default Shipping Address') }}</label>
                        <div class="relative">
                            <span class="absolute top-4 left-0 flex items-start pl-4 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </span>
                            <textarea x-model="form.address" rows="4" placeholder="{{ evomi_l('Tuliskan alamat lengkap beserta kode pos...', 'Enter full address including postal code...') }}" class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 outline-none transition-all bg-white text-sm text-slate-900 resize-none min-h-[100px] leading-relaxed focus:ring-2 focus:ring-[#1172BA]/30 focus:border-[#1172BA]"></textarea>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button type="submit" :disabled="saving || status.type === 'processing'" class="w-full sm:w-auto text-white px-8 py-3.5 rounded-2xl font-semibold text-sm bg-[#1172BA] hover:opacity-90 active:scale-[0.99] transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-text="saving || status.type === 'processing' ? $L('Menyimpan...', 'Saving...') : $L('Simpan Perubahan', 'Save Changes')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-show="toast"
            x-cloak
            x-transition
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[110] px-4 py-2.5 rounded-full bg-slate-900 text-white text-sm font-medium shadow-lg"
            x-text="toast"
        ></div>
    </div>
</x-profile-shell>
@endsection
