@extends('layouts.admin')

@section('title', 'Pesan | Evomi Admin')

@section('content')
<div x-data="evomiAdminMessages" class="space-y-6 pb-12">
    <div>
        <h1 class="text-3xl font-bold text-gray-900" x-text="t('messages','title')">Pesan</h1>
        <p class="text-gray-500 mt-1" x-text="t('messages','subtitle_chat')"></p>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    <div x-show="!loading && !error" class="grid grid-cols-1 lg:grid-cols-3 gap-4 min-h-[30rem]">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] flex flex-col overflow-hidden">
            <div class="p-4 border-b border-gray-50">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" x-model="search" :placeholder="t('messages','search_users')" class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-400 placeholder:text-gray-400">
                </div>
            </div>
            <div class="flex-1 overflow-y-auto max-h-[32rem]">
                <template x-for="c in filtered" :key="c.email">
                    <button
                        type="button"
                        @click="openThread(c.email)"
                        class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50/80 transition-colors flex items-start gap-3"
                        :class="selectedEmail === c.email ? 'bg-gray-50' : ''"
                    >
                        <span class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 font-bold flex items-center justify-center shrink-0" x-text="(c.name || c.email || '?').charAt(0).toUpperCase()"></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-gray-900 text-sm truncate" x-text="c.name || c.email"></span>
                                <span x-show="c.unread_count > 0" class="shrink-0 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-gray-900 text-white text-[10px] font-bold" x-text="c.unread_count"></span>
                            </span>
                            <span class="block text-xs text-gray-400 truncate" x-text="c.email"></span>
                            <span class="block text-xs text-gray-500 mt-1 truncate" x-text="c.last_message || t('messages','no_chat_yet')"></span>
                        </span>
                    </button>
                </template>
                <p x-show="!filtered.length" class="px-4 py-10 text-sm text-gray-400 text-center" x-text="t('messages','empty')"></p>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-[0_2px_20px_rgb(0,0,0,0.04)] flex flex-col overflow-hidden">
            <template x-if="!selectedEmail">
                <div class="flex-1 flex items-center justify-center text-sm text-gray-400 p-8 text-center" x-text="t('messages','pick_user')"></div>
            </template>
            <template x-if="selectedEmail">
                <div class="flex flex-col h-full min-h-[30rem]">
                    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-50 bg-gray-50/50">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 truncate" x-text="conversations.find(c => c.email === selectedEmail)?.name || selectedEmail"></p>
                            <p class="text-xs text-gray-400 truncate" x-text="selectedEmail"></p>
                        </div>
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 bg-white text-rose-600 text-xs font-semibold hover:bg-rose-50" @click="removeThread(selectedEmail)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            <span x-text="t('messages','delete_chat')"></span>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-5 space-y-3 max-h-[26rem] bg-gray-50/30">
                        <div x-show="threadLoading" class="py-8 flex justify-center"><div class="w-6 h-6 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
                        <template x-for="m in thread" :key="m.id || (m.created_at + m.message)">
                            <div class="rounded-2xl px-4 py-3 text-sm max-w-[85%] shadow-sm" :class="m.is_admin || m.from_admin ? 'ml-auto bg-gray-900 text-white' : 'bg-white text-gray-800 border border-gray-100'">
                                <p class="whitespace-pre-line" x-text="m.message || m.body"></p>
                                <p class="text-[10px] mt-1 opacity-60" x-text="m.created_at || m.time || ''"></p>
                            </div>
                        </template>
                        <p x-show="!threadLoading && !thread.length" class="text-sm text-gray-400 text-center py-8" x-text="t('messages','empty_thread')"></p>
                    </div>
                    <div class="p-4 border-t border-gray-50 space-y-3">
                        <textarea x-model="reply" rows="3" :placeholder="t('messages','chat_placeholder')" class="w-full p-4 rounded-xl border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-gray-900"></textarea>
                        <div class="flex justify-end">
                            <button type="button" :disabled="sending || !reply.trim()" @click="send()" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60" x-text="sending ? t('messages','sending') : t('messages','send')"></button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
