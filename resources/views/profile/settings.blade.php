@extends('layouts.app')

@section('title', 'Pengaturan Profil | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileSettings" class="space-y-6">
        <div class="profile-brand-header rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #0d5a94 100%)">
            <h1 class="text-2xl sm:text-3xl font-bold">Pengaturan Profil</h1>
            <p class="mt-1 text-white/80 text-sm">Perbarui data akun Evomi Anda</p>
        </div>

        <div x-show="loading" x-cloak class="py-16 flex justify-center">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
        </div>

        <div x-show="!loading" x-cloak class="space-y-6">
            <div x-show="status.message" x-cloak class="rounded-2xl px-4 py-3 text-sm" :class="status.type === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'" x-text="status.message"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Last login</p>
                    <p class="mt-1 text-sm font-medium text-gray-800" x-text="lastLoginLabel || '—'"></p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Last seen</p>
                    <p class="mt-1 text-sm font-medium text-gray-800" x-text="lastSeenLabel || '—'"></p>
                </div>
            </div>

            <form class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 p-5 sm:p-8 space-y-5" @submit.prevent="save">
                <div class="flex flex-col sm:flex-row items-center gap-5">
                    <div class="relative">
                        <div class="w-24 h-24 rounded-full overflow-hidden bg-[#1172BA]/10 border-2 border-white shadow flex items-center justify-center text-2xl font-bold text-[#1172BA]">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" alt="" class="w-full h-full object-cover" x-on:error="onAvatarError()">
                            </template>
                            <template x-if="!avatarPreview">
                                <span x-text="initial"></span>
                            </template>
                        </div>
                        <button type="button" class="absolute bottom-0 right-0 w-9 h-9 rounded-full bg-[#1172BA] text-white flex items-center justify-center shadow" @click="$refs.avatarInput.click()" aria-label="Ubah foto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <input type="file" class="hidden" accept="image/jpeg,image/png,image/jpg" x-ref="avatarInput" @change="onAvatarChange">
                    </div>
                    <p class="text-xs text-gray-400 text-center sm:text-left">JPG/PNG maks. 2MB</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Lengkap</label>
                        <input type="text" x-model="form.name" required class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Alamat Email</label>
                        <input type="email" x-model="form.email" required class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor Telepon</label>
                        <input type="text" x-model="form.phone" class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kata Sandi Baru</label>
                        <div class="relative mt-2">
                            <input :type="showPassword ? 'text' : 'password'" x-model="form.password" placeholder="Kosongkan jika tidak diubah" class="w-full rounded-2xl border border-gray-200 px-4 py-3 pr-12 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15">
                            <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Alamat Pengiriman Default</label>
                    <textarea x-model="form.address" rows="3" class="mt-2 w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15"></textarea>
                </div>

                <button type="submit" :disabled="saving" class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-[#1172BA] text-white text-sm font-semibold hover:bg-[#0d5a94] disabled:opacity-60">
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </form>
        </div>
    </div>
</x-profile-shell>
@endsection
