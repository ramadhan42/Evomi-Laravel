@extends('layouts.admin')

@section('title', 'Semua User | Evomi Admin')

@section('content')
<div x-data="evomiAdminUsers" class="space-y-6 pb-12">
    <div>
        <h1 class="text-3xl font-bold text-gray-900" x-text="t('users','title')">Semua Pengguna</h1>
        <p class="text-gray-500 mt-1" x-text="t('users','subtitle')"></p>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('users','search_ph')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[960px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="common().user"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('users','col_address')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('users','col_joined')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('users','col_last_seen')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().status"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="pagedItems().length === 0">
                        <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400" x-text="search ? t('users','empty_search') : t('users','empty')"></td></tr>
                    </template>
                    <template x-for="u in pagedItems()" :key="u.id">
                        <tr class="hover:bg-gray-50/40">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center font-bold text-gray-600 shrink-0">
                                        <img x-show="resolveAvatarUrl(u.avatar_profile || u.avatar)" :src="resolveAvatarUrl(u.avatar_profile || u.avatar)" class="w-full h-full object-cover" alt="" x-on:error="$el.style.display='none'">
                                        <span x-show="!resolveAvatarUrl(u.avatar_profile || u.avatar)" x-text="(u.name || '?').charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="u.name || '-'"></p>
                                        <p class="text-xs text-gray-400 truncate" x-text="u.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-[260px]">
                                <p class="truncate" x-text="u.alamat_lengkap || t('users','no_address')"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="u.phone || '-'"></p>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="formatDate(u.created_at)"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="presence(u.last_seen_at || u.last_seen)"></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider border" :class="u.is_admin ? 'bg-gray-900 text-white border-gray-900' : 'bg-gray-100 text-gray-600 border-gray-200'" x-text="u.is_admin ? t('users','admin') : t('users','member')"></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" class="admin-btn-icon" :title="t('users','view_detail')" @click="openView(u)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button type="button" class="admin-btn-icon" :title="t('users','edit_user')" @click="openEdit(u)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="p-2 rounded-lg border border-gray-200 bg-white transition-colors"
                                        :class="canDelete(u) ? 'text-gray-400 hover:text-red-600 hover:bg-red-50' : 'text-gray-200 cursor-not-allowed'"
                                        :disabled="!canDelete(u)"
                                        :title="deleteTitle(u)"
                                        @click="remove(u)"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['countExpr' => "filteredItems().length + ' ' + t('users','users_word')"])
    </div>

    {{-- View modal --}}
<template x-teleport="body">
    <div x-show="viewOpen" x-cloak class="admin-modal-root" role="dialog" aria-modal="true" @keydown.escape.window="closeView()"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="admin-modal-panel max-w-lg" role="document" @click.stop>
            <div class="admin-modal-panel__header">
                <h2 class="text-lg font-bold text-gray-900" x-text="t('users','detail_title')"></h2>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400" @click="closeView()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="admin-modal-panel__body space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center text-xl font-bold text-gray-500 shadow-sm">
                        <img x-show="viewUser && resolveAvatarUrl(viewUser.avatar_profile || viewUser.avatar)" :src="viewUser ? resolveAvatarUrl(viewUser.avatar_profile || viewUser.avatar) : ''" class="w-full h-full object-cover" alt="" x-on:error="$el.style.display='none'">
                        <span x-show="viewUser && !resolveAvatarUrl(viewUser.avatar_profile || viewUser.avatar)" x-text="((viewUser?.name) || '?').charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-base font-bold text-gray-900 truncate" x-text="viewUser?.name || '-'"></p>
                        <p class="text-sm text-gray-500 truncate" x-text="viewUser?.email || '-'"></p>
                        <span class="mt-1 inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border" :class="viewUser?.is_admin ? 'bg-gray-900 text-white border-gray-900' : 'bg-gray-100 text-gray-600 border-gray-200'" x-text="viewUser?.is_admin ? t('users','admin') : t('users','member')"></span>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('users','user_id')"></dt><dd class="mt-0.5 font-mono text-gray-900" x-text="viewUser?.id"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('users','phone')"></dt><dd class="mt-0.5 text-gray-900" x-text="viewUser?.phone || '-'"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('users','joined_date')"></dt><dd class="mt-0.5 text-gray-900" x-text="formatDate(viewUser?.created_at)"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('users','last_seen')"></dt><dd class="mt-0.5 text-gray-900" x-text="presence(viewUser?.last_seen_at || viewUser?.last_seen)"></dd></div>
                    <div class="col-span-2"><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('users','full_address')"></dt><dd class="mt-0.5 text-gray-900" x-text="viewUser?.alamat_lengkap || t('users','no_address_registered')"></dd></div>
                </dl>
            </div>
            <div class="admin-modal-panel__footer">
                <button type="button" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold hover:bg-gray-50" @click="closeView()" x-text="common().close"></button>
            </div>
        </div>
    </div>
</template>

    {{-- Edit modal --}}
<template x-teleport="body">
    <div x-show="modalOpen" x-cloak class="admin-modal-root" role="dialog" aria-modal="true" @keydown.escape.window="closeModal()"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="admin-modal-panel max-w-lg" role="document" @click.stop>
            <div class="admin-modal-panel__header">
                <h2 class="text-lg font-bold text-gray-900" x-text="t('users','edit_title')"></h2>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400" @click="closeModal()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4">
                    <div class="flex flex-col items-center gap-2">
                        <label class="cursor-pointer relative">
                            <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center text-xl font-bold text-gray-500 shadow-md">
                                <img x-show="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" alt="" x-on:error="$el.style.display='none'">
                                <span x-show="!avatarPreview" x-text="(form.name || '?').charAt(0).toUpperCase()"></span>
                            </div>
                            <input type="file" accept="image/*" class="hidden" @change="onAvatar($event)">
                        </label>
                        <p class="text-[11px] text-gray-400" x-text="t('users','edit_avatar_hint')"></p>
                    </div>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('users','field_name')"></span>
                        <input x-model="form.name" required class="admin-field-input">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="common().email"></span>
                        <input type="email" x-model="form.email" required class="admin-field-input">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('users','full_name')"></span>
                        <input x-model="form.nama_lengkap" class="admin-field-input">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('users','phone')"></span>
                        <input x-model="form.phone" class="admin-field-input">
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('users','full_address')"></span>
                        <textarea x-model="form.alamat_lengkap" rows="3" class="admin-field-textarea"></textarea>
                    </label>
                    <label class="block">
                        <span class="admin-field-label" x-text="t('users','password_optional')"></span>
                        <input type="password" x-model="form.password" :placeholder="t('users','password_placeholder')" class="admin-field-input">
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" x-model="form.is_admin" true-value="1" false-value="0" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        <span x-text="t('users','toggle_admin')"></span>
                    </label>
                </div>
                <div class="admin-modal-panel__footer">
                    <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100" @click="closeModal()" x-text="common().cancel"></button>
                    <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60" x-text="saving ? common().saving : common().save"></button>
                </div>
            </form>
        </div>
    </div>
</template>
</div>
@endsection