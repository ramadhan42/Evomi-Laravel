@extends('layouts.app')

@section('title', evomi_l('Biodata Diri | Evomi', 'Personal Info | Evomi'))

@php
    // Baris biodata: kunci form, label, tipe kolom, dan ikonnya. Ditulis sekali
    // di sini supaya urutan dan gaya tiap baris tidak perlu diulang di markup.
    $barisBiodata = [
        [
            'key' => 'name',
            'label' => evomi_l('Nama', 'Name'),
            'type' => 'text',
            'placeholder' => evomi_l('Nama lengkap Anda', 'Your full name'),
        ],
        [
            'key' => 'email',
            'label' => evomi_l('Email', 'Email'),
            'type' => 'email',
            'placeholder' => 'nama@email.com',
        ],
        [
            'key' => 'phone',
            'label' => evomi_l('Nomor Telepon', 'Phone Number'),
            'type' => 'tel',
            'placeholder' => '08xxxxxxxxxx',
        ],
        [
            'key' => 'address',
            'label' => evomi_l('Alamat Pengiriman', 'Shipping Address'),
            'type' => 'textarea',
            'placeholder' => evomi_l('Alamat lengkap beserta kode pos', 'Full address including postal code'),
        ],
    ];
@endphp

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileSettings" class="profile-page-card evomi-soft-enter">
        <div x-show="loading" x-cloak class="profile-page-card__loader absolute inset-0 z-10">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium text-sm">{{ evomi_l('Memuat biodata...', 'Loading personal info...') }}</p>
        </div>

        <div x-show="!loading" x-cloak class="profile-page-card__body">
            <div class="shrink-0 px-5 sm:px-7 py-4 border-b border-gray-100 bg-white" data-soft-enter="up">
                <h1 class="text-base sm:text-lg font-bold text-gray-900">{{ evomi_l('Biodata Diri', 'Personal Info') }}</h1>
            </div>

            <div class="profile-page-card__scroll px-5 sm:px-7 py-5 sm:py-6 bg-white" data-soft-enter="up">
                {{-- Hanya galat yang tampil di sini. Keberhasilan dilaporkan lewat
                     modal, jadi tidak ada spanduk yang muncul lalu hilang dan
                     membuat isi biodata bergeser naik-turun. --}}
                <div
                    x-show="status.message"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="mb-5 px-4 py-3 rounded-xl border border-rose-100 bg-rose-50 text-sm font-medium text-rose-800 flex items-start gap-3"
                >
                    <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    <span x-text="status.message"></span>
                </div>

                <form @submit.prevent="save" class="flex flex-col lg:flex-row lg:items-start gap-6 lg:gap-9">
                    {{-- Kolom foto --}}
                    <div class="w-full lg:w-[232px] shrink-0">
                        <div class="rounded-xl border border-gray-200 p-4 flex flex-col items-center text-center">
                            <div class="h-[128px] w-[128px] rounded-xl overflow-hidden bg-[#1172BA]/10 flex items-center justify-center">
                                <template x-if="avatarPreview">
                                    <img :src="avatarPreview" alt="{{ evomi_l('Foto profil', 'Profile photo') }}" class="h-full w-full object-cover" x-on:error="onAvatarError()">
                                </template>
                                <template x-if="!avatarPreview">
                                    <span class="text-4xl font-bold text-[#1172BA]" x-text="initial"></span>
                                </template>
                            </div>

                            <button
                                type="button"
                                @click="$refs.avatarInput.click()"
                                class="mt-4 w-full rounded-lg border border-[#1172BA] px-4 py-2 text-sm font-bold text-[#1172BA] transition-colors hover:bg-[#1172BA]/5 active:scale-[0.99]"
                            >{{ evomi_l('Pilih Foto', 'Choose Photo') }}</button>

                            <input type="file" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" x-ref="avatarInput" @change="onAvatarChange">

                            <p class="mt-3 text-[11px] leading-relaxed text-gray-500">
                                {{ evomi_l('Besar file maksimum 2MB. Ekstensi yang diperbolehkan: JPG, JPEG, PNG.', 'Maximum file size 2MB. Allowed extensions: JPG, JPEG, PNG.') }}
                            </p>

                            <p x-show="avatarFile" x-cloak class="mt-2 text-[11px] font-semibold text-[#1172BA]">
                                {{ evomi_l('Foto baru siap disimpan', 'New photo ready to save') }}
                            </p>
                        </div>
                    </div>

                    {{-- Kolom biodata --}}
                    <div class="flex-1 min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 pb-3 mb-1 border-b border-gray-100">{{ evomi_l('Ubah Biodata', 'Edit Personal Info') }}</h2>

                        @foreach ($barisBiodata as $baris)
                            <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4 py-3.5 border-b border-gray-50">
                                <span class="w-full sm:w-[152px] shrink-0 pt-1.5 text-[13px] text-gray-500">{{ $baris['label'] }}</span>

                                <div class="flex-1 min-w-0 flex items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="biodata-reveal" :class="{ 'is-open': !isEditing('{{ $baris['key'] }}') }">
                                            <div>
                                                <p class="pt-1.5 pb-0.5 text-sm font-semibold text-gray-900 break-words whitespace-pre-line" x-text="displayValue('{{ $baris['key'] }}')"></p>
                                            </div>
                                        </div>

                                        <div class="biodata-reveal" :class="{ 'is-open': isEditing('{{ $baris['key'] }}') }">
                                            <div>
                                                @if ($baris['type'] === 'textarea')
                                                    <textarea
                                                        x-model="form.{{ $baris['key'] }}"
                                                        rows="3"
                                                        placeholder="{{ $baris['placeholder'] }}"
                                                        class="biodata-input resize-none leading-relaxed"
                                                    ></textarea>
                                                @else
                                                    <input
                                                        type="{{ $baris['type'] }}"
                                                        x-model="form.{{ $baris['key'] }}"
                                                        placeholder="{{ $baris['placeholder'] }}"
                                                        class="biodata-input"
                                                    >
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        @click="toggleEdit('{{ $baris['key'] }}')"
                                        class="shrink-0 pt-1.5 text-[13px] font-bold text-[#1172BA] hover:underline underline-offset-2"
                                        x-text="isEditing('{{ $baris['key'] }}') ? $L('Tutup', 'Close') : $L('Ubah', 'Change')"
                                    ></button>
                                </div>
                            </div>
                        @endforeach

                        {{-- Kata sandi: nilainya tidak pernah ditampilkan --}}
                        <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4 py-3.5 border-b border-gray-50">
                            <span class="w-full sm:w-[152px] shrink-0 pt-1.5 text-[13px] text-gray-500">{{ evomi_l('Kata Sandi', 'Password') }}</span>

                            <div class="flex-1 min-w-0 flex items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="biodata-reveal" :class="{ 'is-open': !isEditing('password') }">
                                        <div>
                                            <p class="pt-1.5 pb-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#1172BA]/8 px-2.5 py-1 text-[12px] font-bold text-[#1172BA]">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    <span x-text="passwordStatusLabel"></span>
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="biodata-reveal" :class="{ 'is-open': isEditing('password') }">
                                        <div>
                                            {{-- Pembungkus relatif hanya melingkupi kolom isian. Sebelumnya
                                                 keterangan di bawahnya ikut terlingkupi, sehingga ikon mata
                                                 yang memakai inset-y-0 tercentang ke tengah kolom + keterangan
                                                 dan terlihat melenceng dari kotaknya. --}}
                                            <div class="relative">
                                                <input
                                                    :type="showPassword ? 'text' : 'password'"
                                                    x-model="form.password"
                                                    minlength="8"
                                                    autocomplete="new-password"
                                                    placeholder="{{ evomi_l('Minimal 8 karakter', 'At least 8 characters') }}"
                                                    class="biodata-input pr-11"
                                                >
                                                <button
                                                    type="button"
                                                    @click="showPassword = !showPassword"
                                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 transition-colors hover:text-gray-800"
                                                    :aria-label="showPassword ? $L('Sembunyikan kata sandi', 'Hide password') : $L('Tampilkan kata sandi', 'Show password')"
                                                >
                                                    <svg x-show="!showPassword" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                    <svg x-show="showPassword" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                                </button>
                                            </div>
                                            <p class="mt-1.5 pb-0.5 text-[11px] text-gray-500">{{ evomi_l('Wajib diisi selama baris ini terbuka. Tutup baris ini bila tidak jadi mengubah kata sandi.', 'Required while this row is open. Close it if you are not changing your password.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="toggleEdit('password')"
                                    class="shrink-0 pt-1.5 text-[13px] font-bold text-[#1172BA] hover:underline underline-offset-2"
                                    x-text="isEditing('password') ? $L('Tutup', 'Close') : $L('Ubah', 'Change')"
                                ></button>
                            </div>
                        </div>

                        {{-- Aktivitas akun --}}
                        <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-4 py-3.5">
                            <span class="w-full sm:w-[152px] shrink-0 text-[13px] text-gray-500">{{ evomi_l('Aktivitas', 'Activity') }}</span>
                            <div class="flex-1 min-w-0 text-[13px] text-gray-700">
                                <p><span class="text-gray-500">{{ evomi_l('Login terakhir', 'Last login') }}:</span> <span class="font-semibold" x-text="lastLoginLabel"></span></p>
                                <p class="mt-1"><span class="text-gray-500">{{ evomi_l('Terakhir aktif', 'Last seen') }}:</span> <span class="font-semibold" x-text="lastSeenLabel"></span></p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="saving"
                                class="w-full sm:w-auto rounded-lg bg-[#1172BA] px-8 py-2.5 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span x-text="saving ? $L('Menyimpan...', 'Saving...') : $L('Simpan', 'Save')"></span>
                            </button>
                        </div>
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

        {{-- Modal sukses: di tengah layar, menutup sendiri setelah 6 detik,
             dan menyebutkan apa saja yang berubah supaya bukan sekadar "berhasil". --}}
        <template x-teleport="body">
            <div
                x-show="successModal.open"
                x-cloak
                @keydown.escape.window="closeSuccessModal()"
                class="fixed inset-0 z-[140] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
            >
                <div
                    x-show="successModal.open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                    @click="closeSuccessModal()"
                    aria-hidden="true"
                ></div>

                <div
                    x-show="successModal.open"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative w-full max-w-[360px] overflow-hidden rounded-3xl bg-white p-7 text-center shadow-2xl"
                >
                    <div class="mx-auto mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-full bg-[#1172BA]/10">
                        <svg class="h-9 w-9 text-[#1172BA]" viewBox="0 0 52 52" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle class="evomi-check-ring" cx="26" cy="26" r="22" />
                            <path class="evomi-check-mark" d="M15 27l8 8 15-16" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-gray-900">{{ evomi_l('Berhasil Disimpan', 'Saved Successfully') }}</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ evomi_l('Biodata Anda sudah diperbarui.', 'Your personal info has been updated.') }}</p>

                    <template x-if="successModal.changes.length">
                        <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-3 text-left">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ evomi_l('Yang diperbarui', 'What changed') }}</p>
                            <ul class="mt-2 space-y-1.5">
                                <template x-for="(item, i) in successModal.changes" :key="item">
                                    <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-[#1172BA]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        <span x-text="item"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <button
                        type="button"
                        @click="closeSuccessModal()"
                        class="mt-6 w-full rounded-xl bg-[#1172BA] py-3 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-[0.99]"
                    >{{ evomi_l('Selesai', 'Done') }}</button>

                    {{-- Garis waktu penutupan otomatis, sekaligus penanda bahwa
                         modalnya akan hilang sendiri tanpa perlu ditunggu. --}}
                    <span
                        x-show="successModal.open"
                        class="evomi-modal-timebar absolute bottom-0 left-0 h-1 bg-[#1172BA]/30"
                        aria-hidden="true"
                    ></span>
                </div>
            </div>
        </template>
    </div>
</x-profile-shell>
@endsection
