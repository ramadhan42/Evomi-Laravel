@extends('layouts.admin')

@section('title', 'Profil Admin | Evomi Admin')

@section('content')
<div x-data="evomiAdminProfile" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-[#1172BA]/10 flex items-center justify-center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1172BA" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span x-text="t('profile','title')">Profil Saya</span>
            </h1>
            <p class="text-gray-500 mt-1.5 text-sm max-w-xl" x-text="t('profile','subtitle')"></p>
        </div>
        <button
            type="button"
            @click="openEdit()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-black transition shadow-sm"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            <span x-text="t('profile','edit_button')"></span>
        </button>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>

    <div x-show="!loading" class="bg-white rounded-2xl shadow-[0_2px_24px_rgb(0,0,0,0.05)] border border-gray-100 overflow-hidden">
        <div class="relative h-36 sm:h-40 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[#1172BA] via-[#0d5f9e] to-slate-900"></div>
            <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_20%_50%,white_0%,transparent_50%)]"></div>
            <div class="absolute top-4 right-4 flex flex-wrap gap-2 justify-end">
                <span
                    x-show="isAdmin"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-white/15 text-white border border-white/25"
                >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span x-text="t('profile','admin_user')"></span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-emerald-500/20 text-emerald-50 border border-emerald-300/30">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span x-text="t('profile','verified')"></span>
                </span>
            </div>
        </div>

        <div class="px-6 sm:px-8 pb-8 relative z-10">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4 sm:gap-5">
                <div class="relative shrink-0 -mt-14 sm:-mt-12 z-20">
                    <div class="h-28 w-28 rounded-2xl bg-white p-1 shadow-xl ring-4 ring-white overflow-hidden">
                        <img
                            x-show="avatarPreview"
                            :src="avatarPreview"
                            :alt="form.nama_lengkap || form.name"
                            class="h-full w-full object-cover rounded-xl"
                            x-on:error="$el.style.display='none'"
                        >
                        <div
                            x-show="!avatarPreview"
                            class="h-full w-full rounded-xl bg-gradient-to-br from-[#1172BA] to-slate-800 flex items-center justify-center text-white text-3xl font-bold"
                            x-text="(form.name || 'A').charAt(0).toUpperCase()"
                        ></div>
                    </div>
                    <span
                        x-show="isAdmin"
                        class="absolute -bottom-1 -right-1 w-8 h-8 rounded-xl bg-gray-900 text-white flex items-center justify-center shadow-lg border-2 border-white"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.9 5.8H20l-4.7 3.4 1.8 5.8L12 14.6 6.9 18l1.8-5.8L4 8.8h6.1z"/></svg>
                    </span>
                </div>
                <div class="flex-1 min-w-0 relative z-10 sm:pt-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 truncate" x-text="form.nama_lengkap || form.name || '—'"></h2>
                    <p class="text-gray-500 text-sm mt-0.5">@<span x-text="form.name || '—'"></span></p>
                    <p class="text-xs text-gray-400 mt-1 font-medium">ID #<span x-text="meta.id || '—'"></span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-8">
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','member_since')"></p>
                    <p class="mt-1.5 text-sm font-semibold text-gray-900" x-text="formatDate(meta.created_at) || t('profile','not_set')"></p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','last_login')"></p>
                    <p class="mt-1.5 text-sm font-semibold text-gray-900" x-text="presence(meta.last_login_at)"></p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','last_seen')"></p>
                    <p class="mt-1.5 text-sm font-semibold text-gray-900" x-text="presence(meta.last_seen_at)"></p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','email_label')"></p>
                    <p class="mt-1.5 text-sm font-semibold text-gray-900 truncate" x-text="form.email || t('profile','not_set')"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                <section class="rounded-2xl border border-gray-100 bg-gray-50/60 p-5 sm:p-6">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-5 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-white border border-gray-100 flex items-center justify-center shadow-sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1172BA" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <span x-text="t('profile','contact_info')"></span>
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-gray-400"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','name_label')"></p>
                                <p class="text-gray-900 font-medium" x-text="form.nama_lengkap || form.name || t('profile','not_set')"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-gray-400"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.68 2.35a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.75.32 1.54.55 2.35.68A2 2 0 0 1 22 16.92z"/></svg></span>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','phone_label')"></p>
                                <p class="text-gray-900 font-medium" x-text="form.phone || t('profile','not_set')"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-gray-400"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('profile','email_label')"></p>
                                <p class="text-gray-900 font-medium truncate" x-text="form.email || t('profile','not_set')"></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-gray-50/60 p-5 sm:p-6 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-5 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-white border border-gray-100 flex items-center justify-center shadow-sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1172BA" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </span>
                        <span x-text="t('profile','address_label')"></span>
                    </h3>
                    <div class="flex-1 rounded-xl bg-white border border-gray-100 p-4 text-sm text-gray-700 leading-relaxed">
                        <template x-if="form.alamat_lengkap">
                            <span x-text="form.alamat_lengkap"></span>
                        </template>
                        <template x-if="!form.alamat_lengkap">
                            <span class="text-gray-400 italic" x-text="t('profile','no_address')"></span>
                        </template>
                    </div>
                    <div class="mt-5 pt-5 border-t border-gray-200/80">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3" x-text="t('profile','account_overview')"></p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-xs font-semibold text-gray-600">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1172BA" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <span x-text="isAdmin ? t('profile','admin_user') : t('profile','user_role')"></span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold bg-emerald-50 border-emerald-100 text-emerald-700">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span x-text="t('profile','verified')"></span>
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Edit modal — Next.js parity --}}
<template x-teleport="body">
    <div
        x-show="modalOpen"
        x-cloak
        class="admin-modal-root"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="closeModal()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            class="admin-modal-panel max-w-lg"
            role="document"
            @click.stop
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.96] translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 scale-[0.98]"
        >
            <div class="admin-modal-panel__header">
                <div>
                    <h2 class="text-lg font-bold text-gray-900" x-text="t('profile','modal_edit')"></h2>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="t('profile','subtitle')"></p>
                </div>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700" @click="closeModal()" :disabled="saving" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4 max-h-[70vh]">
                    <div class="flex flex-col items-center gap-2">
                        <label class="cursor-pointer relative group">
                            <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center text-xl font-bold text-gray-500 ring-2 ring-white shadow-md">
                                <img x-show="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" alt="" x-on:error="$el.style.display='none'">
                                <span x-show="!avatarPreview" x-text="(form.name || '?').charAt(0).toUpperCase()"></span>
                            </div>
                            <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-lg bg-[#1172BA] text-white flex items-center justify-center shadow border-2 border-white">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            </span>
                            <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" @change="onAvatar($event)">
                        </label>
                        <p class="text-[11px] text-gray-400" x-text="t('profile','avatar_hint')"></p>
                    </div>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('profile','username_label')"></span>
                        <input x-model="form.name" required class="admin-field-input focus:border-[#1172BA] focus:ring-[#1172BA]/15">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('profile','name_label')"></span>
                        <input x-model="form.nama_lengkap" class="admin-field-input focus:border-[#1172BA] focus:ring-[#1172BA]/15">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('profile','email_label')"></span>
                        <input type="email" x-model="form.email" required class="admin-field-input focus:border-[#1172BA] focus:ring-[#1172BA]/15">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('profile','phone_label')"></span>
                        <input x-model="form.phone" class="admin-field-input focus:border-[#1172BA] focus:ring-[#1172BA]/15">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('profile','address_label')"></span>
                        <textarea x-model="form.alamat_lengkap" rows="3" class="admin-field-textarea focus:border-[#1172BA] focus:ring-[#1172BA]/15"></textarea>
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('profile','password_optional')"></span>
                        <input type="password" x-model="form.password" :placeholder="t('profile','password_hint')" class="admin-field-input focus:border-[#1172BA] focus:ring-[#1172BA]/15">
                    </label>
                </div>
                <div class="admin-modal-panel__footer bg-gray-50/80">
                    <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition" @click="closeModal()" :disabled="saving" x-text="common().cancel"></button>
                    <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60">
                        <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span x-text="saving ? common().saving : common().save_changes"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
</div>
@endsection