@extends('layouts.app')

@section('title', evomi_l('Pesan Anda | Evomi', 'Your Messages | Evomi'))

@section('content')
<x-profile-shell>
    <div
        x-data="evomiProfileChat"
        class="profile-page-card"
        style="--chat-brand: #1172BA"
    >
        <div class="relative px-5 sm:px-7 py-5 text-white shrink-0" style="background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 55%, #0e6aad 100%)">
            <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 12% 20%, rgba(255,255,255,0.35), transparent 40%), radial-gradient(circle at 90% 0%, rgba(255,255,255,0.18), transparent 35%)"></div>
            <div class="relative flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="relative w-11 h-11 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z"/></svg>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-white"></span>
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight truncate">{{ evomi_l('Pesan Anda', 'Your Messages') }}</h1>
                            <svg class="w-4 h-4 text-amber-200 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
                        </div>
                        <p class="text-[12px] sm:text-sm text-white/80 font-medium">{{ evomi_l('Admin online · siap membantu', 'Admin online · ready to help') }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-white/15 hover:bg-white/25 border border-white/20 px-3 py-1.5 text-[11px] font-semibold transition disabled:opacity-60"
                    @click="load()"
                    :disabled="loading || refreshing"
                >
                    <svg class="w-3.5 h-3.5" :class="refreshing && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                    {{ evomi_l('Muat ulang', 'Reload') }}
                </button>
            </div>
        </div>

        <div class="relative flex-1 min-h-0 bg-white">
            <div
                class="h-full overflow-y-auto px-4 sm:px-5 py-4 space-y-1 scroll-smooth"
                x-ref="thread"
                @scroll="onThreadScroll()"
            >
                <div x-show="loading" x-cloak class="h-full min-h-[280px] flex flex-col items-center justify-center gap-3">
                    <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-400">{{ evomi_l('Memuat percakapan...', 'Loading conversation...') }}</p>
                </div>

                <div x-show="!loading && messages.length === 0" x-cloak class="h-full min-h-[280px] flex flex-col items-center justify-center text-center px-4 py-10">
                    <div class="w-16 h-16 rounded-[22px] flex items-center justify-center text-white mb-4 bg-[#1172BA]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                    </div>
                    <p class="text-base font-bold text-gray-800 mb-1">{{ evomi_l('Belum ada percakapan', 'No conversation yet') }}</p>
                    <p class="text-sm text-gray-500 max-w-md mb-6 leading-relaxed">{{ evomi_l('Tanyakan stok, pengiriman, atau rekomendasi aroma. Tim Evomi akan membalas di sini.', 'Ask about stock, shipping, or scent recommendations. The Evomi team will reply here.') }}</p>
                    <div class="flex flex-wrap justify-center gap-2 max-w-lg">
                        <template x-for="hint in hints" :key="hint">
                            <button type="button" class="text-left text-[12px] font-medium px-3.5 py-2 rounded-full bg-white border border-gray-200 text-gray-700 hover:border-[var(--chat-brand)] hover:text-[var(--chat-brand)] shadow-sm transition" @click="useHint(hint)" x-text="hint"></button>
                        </template>
                    </div>
                </div>

                <template x-for="(msg, index) in messages" :key="msg.id">
                    <div>
                        <div x-show="showDayDivider(index)" class="flex justify-center my-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 bg-white/90 border border-gray-100 px-3 py-1 rounded-full shadow-sm" x-text="dayLabel(msg.createdAt)"></span>
                        </div>

                        <div
                            class="flex w-full"
                            :class="[
                                msg.type === 'user' ? 'justify-end' : 'justify-start',
                                isConsecutive(index) ? 'mt-1.5' : 'mt-4'
                            ]"
                        >
                            <div class="flex max-w-[85%] sm:max-w-[72%] gap-2.5" :class="msg.type === 'user' ? 'flex-row-reverse' : 'flex-row'">
                                <div class="flex-shrink-0 mt-auto mb-5" x-show="!isConsecutive(index)">
                                    <div x-show="msg.type === 'user'" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                    </div>
                                    <div x-show="msg.type !== 'user'" class="w-8 h-8 rounded-full flex items-center justify-center text-white shadow-sm bg-[#1172BA]">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z"/></svg>
                                    </div>
                                </div>
                                <div class="w-8 flex-shrink-0" x-show="isConsecutive(index)"></div>

                                <div class="flex flex-col relative min-w-0">
                                    <div class="flex items-center gap-2 mb-1 px-1" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'" x-show="!isConsecutive(index)">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="msg.type === 'user' ? $L('Anda', 'You') : $L('Admin Evomi', 'Evomi Admin')"></span>
                                        <span x-show="msg.isNew" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-500 text-white">{{ evomi_l('BARU', 'NEW') }}</span>
                                    </div>
                                    <div
                                        class="px-4 py-2.5 text-sm leading-relaxed shadow-sm"
                                        :class="msg.type === 'user'
                                            ? 'text-white rounded-2xl rounded-br-md'
                                            : 'bg-white border border-gray-100 text-gray-800 rounded-2xl rounded-bl-md'"
                                        :style="msg.type === 'user' ? 'background: linear-gradient(135deg, #1172BA 0%, #1a7fc4 55%, #0e6aad 100%)' : ''"
                                    >
                                        <p x-show="msg.subject && msg.type === 'user'" class="text-[10px] opacity-80 mb-1 font-medium">{{ evomi_l('Topik:', 'Topic:') }} <span x-text="msg.subject"></span></p>
                                        <p class="whitespace-pre-wrap" x-text="msg.text"></p>
                                        <div class="mt-1.5 flex items-center gap-1.5" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'">
                                            <span class="text-[10px] opacity-70" x-text="msg.timeLabel"></span>
                                            <template x-if="msg.type === 'user'">
                                                <span class="inline-flex text-sky-200">
                                                    <svg x-show="msg.isReadByAdmin" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" transform="translate(4 0)" opacity="0.7"/></svg>
                                                    <svg x-show="!msg.isReadByAdmin" class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <p x-show="msg.pending" class="text-[10px] text-gray-400 mt-1 px-1" :class="msg.type === 'user' ? 'text-right' : ''">{{ evomi_l('Mengirim...', 'Sending...') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button
                type="button"
                x-show="showJumpLatest && !loading && messages.length"
                x-cloak
                @click="jumpLatest()"
                class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 inline-flex items-center gap-1.5 rounded-full bg-slate-900 text-white text-[11px] font-semibold px-3 py-1.5 shadow-lg"
            >
                {{ evomi_l('Pesan terbaru', 'Latest messages') }}
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>
        </div>

        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 p-4 sm:p-5 space-y-3">
            <div class="flex flex-wrap gap-2" x-show="!loading && messages.length > 0">
                <template x-for="hint in hints" :key="'c-'+hint">
                    <button type="button" class="text-[11px] px-3 py-1 rounded-full bg-white text-gray-600 border border-gray-200 hover:border-[#1172BA] hover:text-[#1172BA] transition" @click="useHint(hint)" x-text="hint"></button>
                </template>
            </div>
            <p x-show="sendError" x-cloak class="text-xs text-rose-600 font-medium" x-text="sendError"></p>
            <form class="flex gap-2 items-end" @submit.prevent="send">
                <textarea
                    x-model="draft"
                    x-ref="composer"
                    rows="2"
                    placeholder="{{ evomi_l('Tulis pesan untuk admin Evomi...', 'Write a message to Evomi admin...') }}"
                    class="flex-1 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15 resize-none min-h-[52px]"
                    @keydown.enter.exact.prevent="send()"
                ></textarea>
                <button
                    type="submit"
                    :disabled="sending || !draft.trim()"
                    class="shrink-0 w-12 h-12 rounded-2xl bg-[#1172BA] text-white flex items-center justify-center disabled:opacity-50 hover:opacity-90 transition"
                    :aria-label="$L('Kirim', 'Send')"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                </button>
            </form>
            <p class="text-[10px] text-gray-400 font-medium">{{ evomi_l('Enter ↵ kirim · Shift+Enter baris baru', 'Enter ↵ send · Shift+Enter new line') }}</p>
        </div>
    </div>
</x-profile-shell>
@endsection
