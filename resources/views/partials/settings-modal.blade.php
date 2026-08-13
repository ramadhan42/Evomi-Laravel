{{-- Square profile settings modal (Arcanisia-like shape, Evomi UI) --}}
<style>
.evomi-settings-modal{position:fixed;inset:0;z-index:230}
.evomi-settings-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-settings-modal__frame{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem}
.evomi-settings-modal__panel{
  position:relative;
  display:flex;
  flex-direction:column;
  width:min(92vw,92vh,640px);
  height:min(92vw,92vh,640px);
  max-width:640px;
  max-height:640px;
  aspect-ratio:1/1;
  overflow:hidden;
  background:#fff;
  border-radius:24px;
  box-shadow:0 24px 80px rgba(15,23,42,.28);
}
.evomi-settings-modal__header{
  flex-shrink:0;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:.75rem;
  padding:1.1rem 1.15rem .95rem;
  background:linear-gradient(135deg,#1172BA 0%,#1a7fc4 55%,#0e6aad 100%);
  color:#fff;
}
.evomi-settings-modal__kicker{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.evomi-settings-modal__title{margin-top:.15rem;font-size:1.15rem;font-weight:700;letter-spacing:-.02em;line-height:1.25}
.evomi-settings-modal__subtitle{margin-top:.4rem;font-size:12px;line-height:1.45;color:rgba(255,255,255,.88)}
.evomi-settings-modal__close{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:999px;border:0;color:#fff;
  background:rgba(255,255,255,.14);flex-shrink:0;cursor:pointer;
}
.evomi-settings-modal__body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:1rem 1.15rem 1.2rem;background:linear-gradient(180deg,#f8fafc 0%,#fff 22%)}
.evomi-settings-modal__input,.evomi-settings-modal__textarea{
  width:100%;border:1px solid #e2e8f0;background:#fff;color:#0f172a;outline:none;font-size:13px;font-weight:500;
}
.evomi-settings-modal__input{height:44px;border-radius:14px;padding:0 .95rem 0 2.55rem}
.evomi-settings-modal__textarea{min-height:84px;border-radius:14px;padding:.75rem .95rem .75rem 2.55rem;resize:none;line-height:1.45}
.evomi-settings-modal__input:focus,.evomi-settings-modal__textarea:focus{border-color:#1172ba;box-shadow:0 0 0 3px rgba(17,114,186,.12)}
.evomi-settings-modal__submit{
  width:100%;height:46px;border:0;border-radius:14px;background:#1172ba;color:#fff;
  font-weight:700;font-size:13px;cursor:pointer;
}
.evomi-settings-modal__submit:disabled{opacity:.55;cursor:not-allowed}
</style>

<template x-teleport="body">
    <div
        class="evomi-settings-modal"
        x-show="$store.evomiSettingsModal.open"
        x-cloak
        :class="$store.evomiSettingsModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiSettingsModal.open && closeSettingsModal()"
    >
        <div
            class="evomi-settings-modal__backdrop"
            x-show="$store.evomiSettingsModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeSettingsModal()"
        ></div>

        <div class="evomi-settings-modal__frame" x-show="$store.evomiSettingsModal.open" @click.self="closeSettingsModal()">
            <div
                class="evomi-settings-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('Pengaturan Profil', 'Profile Settings') }}"
                x-show="$store.evomiSettingsModal.open"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
                x-data="evomiProfileSettings"
                @evomi-settings-reload.window="load()"
                @click.stop
            >
                <div class="evomi-settings-modal__header">
                    <div class="min-w-0 flex items-start gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.223.52.337.317.198.71.24 1.04.08l1.21-.59a1.125 1.125 0 0 1 1.45.42l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .674c-.01.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.449.42l-1.211-.59a1.14 1.14 0 0 0-1.04.078 7.94 7.94 0 0 1-.519.338c-.332.183-.582.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a7.957 7.957 0 0 1-.52-.337 1.14 1.14 0 0 0-1.04-.08l-1.21.59a1.125 1.125 0 0 1-1.45-.42l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a7.723 7.723 0 0 1 0-.675c.007-.378-.138-.75-.43-.991l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.45-.42l1.21.59c.33.16.723.118 1.04-.078.172-.114.346-.236.52-.337.332-.184.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <p class="evomi-settings-modal__kicker">Evomi</p>
                            <h2 class="evomi-settings-modal__title">{{ evomi_l('Pengaturan Profil', 'Profile Settings') }}</h2>
                            <p class="evomi-settings-modal__subtitle">{{ evomi_l('Perbarui foto, kontak, dan alamat pengiriman utama Anda.', 'Update your photo, contact info, and default shipping address.') }}</p>
                        </div>
                    </div>
                    <button type="button" class="evomi-settings-modal__close" @click="closeSettingsModal()" :aria-label="$L('Tutup', 'Close')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/>
                        </svg>
                    </button>
                </div>

                <div class="evomi-settings-modal__body">
                    <div x-show="loading" x-cloak class="py-16 flex flex-col items-center justify-center gap-3">
                        <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                        <p class="text-[12px] text-slate-400 font-medium">{{ evomi_l('Memuat profil...', 'Loading profile...') }}</p>
                    </div>

                    <form x-show="!loading" x-cloak @submit.prevent="save" class="space-y-3.5">
                        <div
                            x-show="status.message"
                            x-cloak
                            class="p-3 rounded-xl text-[12px] font-medium flex items-center gap-2 border"
                            :class="{
                                'bg-emerald-50 border-emerald-100 text-emerald-800': status.type === 'success',
                                'bg-rose-50 border-rose-100 text-rose-800': status.type === 'error',
                                'bg-white border-slate-200 text-slate-700': status.type === 'processing' || !status.type
                            }"
                        >
                            <div x-show="status.type === 'processing'" class="w-4 h-4 border-2 border-slate-200 border-t-[#1172BA] rounded-full animate-spin shrink-0"></div>
                            <span x-text="status.message"></span>
                        </div>

                        <div class="flex items-center gap-3.5">
                            <div class="relative shrink-0">
                                <div class="h-16 w-16 rounded-full border-2 border-white overflow-hidden flex items-center justify-center ring-2 ring-[#1172BA]/20 bg-[#1172BA]/10">
                                    <template x-if="avatarPreview">
                                        <img :src="avatarPreview" alt="" class="h-full w-full object-cover" x-on:error="onAvatarError()">
                                    </template>
                                    <template x-if="!avatarPreview">
                                        <span class="text-xl font-bold text-[#1172BA]" x-text="initial"></span>
                                    </template>
                                </div>
                                <button type="button" class="absolute -bottom-0.5 -right-0.5 text-white p-1.5 rounded-full bg-[#1172BA]" @click="$refs.avatarInput.click()" :aria-label="$L('Ganti foto', 'Change photo')">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                                </button>
                                <input type="file" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" x-ref="avatarInput" @change="onAvatarChange">
                            </div>
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold text-slate-900">{{ evomi_l('Foto Profil', 'Profile Photo') }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ evomi_l('JPG atau PNG, maks. 2MB.', 'JPG or PNG, max. 2MB.') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last login</p>
                                <p class="text-[12px] font-semibold text-slate-800 mt-0.5 truncate" x-text="lastLoginLabel"></p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last seen</p>
                                <p class="text-[12px] font-semibold text-slate-800 mt-0.5 truncate" x-text="lastSeenLabel"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 ml-1 mb-1.5">{{ evomi_l('Nama Lengkap', 'Full Name') }}</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg></span>
                                    <input type="text" class="evomi-settings-modal__input" required x-model="form.name">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 ml-1 mb-1.5">{{ evomi_l('Alamat Email', 'Email Address') }}</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg></span>
                                    <input type="email" class="evomi-settings-modal__input" required x-model="form.email">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 ml-1 mb-1.5">{{ evomi_l('Nomor Telepon', 'Phone Number') }}</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg></span>
                                    <input type="tel" class="evomi-settings-modal__input" maxlength="20" placeholder="08xxxxxxxxxx" x-model="form.phone">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 ml-1 mb-1.5">{{ evomi_l('Kata Sandi Baru', 'New Password') }}</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg></span>
                                    <input :type="showPassword ? 'text' : 'password'" class="evomi-settings-modal__input pr-10" autocomplete="new-password" minlength="8" placeholder="••••••••" x-model="form.password">
                                    <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400" @click="showPassword = !showPassword">
                                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 ml-1 mb-1.5">{{ evomi_l('Alamat Pengiriman Default', 'Default Shipping Address') }}</label>
                            <div class="relative">
                                <span class="absolute top-3 left-0 flex items-start pl-3 text-slate-400"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg></span>
                                <textarea class="evomi-settings-modal__textarea" rows="3" x-model="form.address" :placeholder="$L('Tuliskan alamat lengkap beserta kode pos...', 'Enter full address including postal code...')"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="evomi-settings-modal__submit" :disabled="saving || status.type === 'processing'">
                            <span x-text="saving || status.type === 'processing' ? $L('Menyimpan...', 'Saving...') : $L('Simpan Perubahan', 'Save Changes')"></span>
                        </button>
                    </form>
                </div>

                <div
                    x-show="toast"
                    x-cloak
                    x-transition
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 px-3.5 py-2 rounded-full bg-slate-900 text-white text-[12px] font-medium shadow-lg"
                    x-text="toast"
                ></div>
            </div>
        </div>
    </div>
</template>
