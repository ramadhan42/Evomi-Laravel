{{-- Square account chat modal — same shape language as settings/wishlist/history --}}
<style>
.evomi-chat-modal{position:fixed;inset:0;z-index:230}
.evomi-chat-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
.evomi-chat-modal__frame{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem}
.evomi-chat-modal__panel{
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
  --chat-brand:#1172BA;
}
.evomi-chat-modal__header{
  flex-shrink:0;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:.75rem;
  padding:1.05rem 1.1rem .9rem;
  background:linear-gradient(135deg,#1172BA 0%,#1a7fc4 55%,#0e6aad 100%);
  color:#fff;
}
.evomi-chat-modal__kicker{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.evomi-chat-modal__title{margin-top:.1rem;font-size:1.1rem;font-weight:700;letter-spacing:-.02em;line-height:1.25}
.evomi-chat-modal__subtitle{margin-top:.35rem;font-size:12px;line-height:1.4;color:rgba(255,255,255,.88)}
.evomi-chat-modal__avatar{
  position:relative;display:inline-flex;align-items:center;justify-content:center;
  width:42px;height:42px;border-radius:14px;background:rgba(255,255,255,.16);flex-shrink:0;
}
.evomi-chat-modal__online{
  position:absolute;right:-2px;bottom:-2px;width:11px;height:11px;border-radius:999px;
  background:#34d399;border:2px solid #fff;
}
.evomi-chat-modal__close{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:999px;border:0;color:#fff;
  background:rgba(255,255,255,.14);flex-shrink:0;cursor:pointer;
}
.evomi-chat-modal__reload{
  display:inline-flex;align-items:center;justify-content:center;gap:.35rem;
  height:34px;padding:0 .75rem;border-radius:999px;border:1px solid rgba(255,255,255,.22);
  background:rgba(255,255,255,.12);color:#fff;font-size:11px;font-weight:700;cursor:pointer;flex-shrink:0;
}
.evomi-chat-modal__body{flex:1;min-height:0;position:relative;background:linear-gradient(180deg,#f8fafc 0%,#fff 28%)}
.evomi-chat-modal__thread{height:100%;overflow-y:auto;overscroll-behavior:contain;padding:.85rem 1rem 1rem;scroll-behavior:smooth}
.evomi-chat-modal__bubble-user{
  color:#fff;border-radius:18px 18px 6px 18px;
  background:linear-gradient(135deg,#1172BA 0%,#1a7fc4 55%,#0e6aad 100%);
  box-shadow:0 8px 18px rgba(17,114,186,.18);
}
.evomi-chat-modal__bubble-admin{
  color:#334155;background:#fff;border:1px solid #eef2f7;border-radius:18px 18px 18px 6px;
  box-shadow:0 6px 14px rgba(15,23,42,.04);
}
.evomi-chat-modal__footer{
  flex-shrink:0;border-top:1px solid #eef2f7;background:#fff;
  padding:.75rem .9rem .9rem;display:flex;flex-direction:column;gap:.55rem;
}
.evomi-chat-modal__chip{
  border:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:11px;line-height:1.2;
  padding:.4rem .7rem;border-radius:999px;cursor:pointer;
}
.evomi-chat-modal__chip:hover{background:#fff;border-color:#cbd5e1;color:#334155}
.evomi-chat-modal__composer{display:flex;align-items:flex-end;gap:.5rem}
.evomi-chat-modal__input{
  flex:1;min-width:0;min-height:48px;max-height:96px;border:1px solid #e5e7eb;border-radius:16px;
  padding:.7rem .95rem;font-size:13px;color:#0f172a;background:#f8fafc;outline:none;resize:none;line-height:1.45;
}
.evomi-chat-modal__input:focus{border-color:#1172ba;background:#fff;box-shadow:0 0 0 3px rgba(17,114,186,.12)}
.evomi-chat-modal__send{
  display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border:0;border-radius:16px;
  background:#1172ba;color:#fff;cursor:pointer;flex-shrink:0;box-shadow:0 8px 18px rgba(17,114,186,.22);
}
.evomi-chat-modal__send:disabled{opacity:.5;cursor:not-allowed;box-shadow:none}
.evomi-chat-modal__jump{
  position:absolute;left:50%;bottom:.75rem;transform:translateX(-50%);z-index:2;
  display:inline-flex;align-items:center;gap:.35rem;border:0;border-radius:999px;
  background:#0f172a;color:#fff;font-size:11px;font-weight:700;padding:.4rem .75rem;
  box-shadow:0 10px 24px rgba(15,23,42,.22);cursor:pointer;
}
</style>

<template x-teleport="body">
    <div
        class="evomi-chat-modal"
        x-show="$store.evomiChatModal.open"
        x-cloak
        :class="$store.evomiChatModal.open ? 'pointer-events-auto' : 'pointer-events-none'"
        @keydown.escape.window="$store.evomiChatModal.open && closeChatModal()"
    >
        <div
            class="evomi-chat-modal__backdrop"
            x-show="$store.evomiChatModal.open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeChatModal()"
        ></div>

        <div class="evomi-chat-modal__frame" x-show="$store.evomiChatModal.open" @click.self="closeChatModal()">
            <div
                class="evomi-chat-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ evomi_l('Pesan Anda', 'Your Messages') }}"
                x-data="evomiProfileChat"
                x-show="$store.evomiChatModal.open"
                x-transition:enter="ease-[cubic-bezier(0.22,1,0.36,1)] duration-420"
                x-transition:enter-start="opacity-0 scale-[0.96] translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-220"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.98]"
                @click.stop
                @evomi-chat-reload.window="load()"
            >
                <div class="evomi-chat-modal__header">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="evomi-chat-modal__avatar" aria-hidden="true">
                            @include('partials.icons.chat', ['class' => 'w-5 h-5'])
                            <span class="evomi-chat-modal__online"></span>
                        </span>
                        <div class="min-w-0">
                            <p class="evomi-chat-modal__kicker">Evomi Support</p>
                            <h2 class="evomi-chat-modal__title truncate">{{ evomi_l('Pesan Anda', 'Your Messages') }}</h2>
                            <p class="evomi-chat-modal__subtitle truncate">{{ evomi_l('Admin online · siap membantu', 'Admin online · ready to help') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            type="button"
                            class="evomi-chat-modal__reload"
                            @click="load()"
                            :disabled="loading || refreshing"
                        >
                            <svg class="w-3.5 h-3.5" :class="refreshing && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                            <span class="hidden sm:inline">{{ evomi_l('Muat ulang', 'Reload') }}</span>
                        </button>
                        <button type="button" class="evomi-chat-modal__close" @click="closeChatModal()" :aria-label="$L('Tutup', 'Close')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 12M4 4l8 8"/></svg>
                        </button>
                    </div>
                </div>

                <div class="evomi-chat-modal__body">
                    <div class="evomi-chat-modal__thread" x-ref="thread" @scroll="onThreadScroll()">
                        <div x-show="loading" x-cloak class="h-full min-h-[220px] flex flex-col items-center justify-center gap-3">
                            <div class="w-8 h-8 border-[3px] border-slate-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                            <p class="text-[12px] text-slate-400 font-medium">{{ evomi_l('Memuat percakapan...', 'Loading conversation...') }}</p>
                        </div>

                        <div x-show="!loading && messages.length === 0" x-cloak class="h-full min-h-[220px] flex flex-col items-center justify-center text-center px-3 py-8">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white mb-3 bg-[#1172BA]">
                                @include('partials.icons.chat', ['class' => 'w-6 h-6'])
                            </div>
                            <p class="text-[15px] font-bold text-slate-900 mb-1">{{ evomi_l('Belum ada percakapan', 'No conversation yet') }}</p>
                            <p class="text-[12px] text-slate-500 max-w-sm mb-4 leading-relaxed">{{ evomi_l('Tanyakan stok, pengiriman, atau rekomendasi aroma. Tim Evomi akan membalas di sini.', 'Ask about stock, shipping, or scent recommendations. The Evomi team will reply here.') }}</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <template x-for="hint in hints" :key="hint">
                                    <button type="button" class="evomi-chat-modal__chip" @click="useHint(hint)" x-text="hint"></button>
                                </template>
                            </div>
                        </div>

                        <template x-for="(msg, index) in messages" :key="msg.id">
                            <div>
                                <div x-show="showDayDivider(index)" class="flex justify-center my-3">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-white/95 border border-slate-100 px-2.5 py-1 rounded-full shadow-sm" x-text="dayLabel(msg.createdAt)"></span>
                                </div>

                                <div
                                    class="flex w-full"
                                    :class="[
                                        msg.type === 'user' ? 'justify-end' : 'justify-start',
                                        isConsecutive(index) ? 'mt-1' : 'mt-3'
                                    ]"
                                >
                                    <div class="flex max-w-[88%] gap-2" :class="msg.type === 'user' ? 'flex-row-reverse' : 'flex-row'">
                                        <div class="flex-shrink-0 mt-auto mb-4" x-show="!isConsecutive(index)">
                                            <div x-show="msg.type === 'user'" class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 shadow-sm">
                                                @include('partials.icons.user', ['class' => 'w-3.5 h-3.5'])
                                            </div>
                                            <div x-show="msg.type !== 'user'" class="w-7 h-7 rounded-full flex items-center justify-center text-white shadow-sm bg-[#1172BA]">
                                                @include('partials.icons.chat', ['class' => 'w-3.5 h-3.5'])
                                            </div>
                                        </div>
                                        <div class="w-7 flex-shrink-0" x-show="isConsecutive(index)"></div>

                                        <div class="flex flex-col relative min-w-0">
                                            <div class="flex items-center gap-2 mb-1 px-1" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'" x-show="!isConsecutive(index)">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="msg.type === 'user' ? $L('Anda', 'You') : $L('Admin Evomi', 'Evomi Admin')"></span>
                                                <span x-show="msg.isNew" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-500 text-white">{{ evomi_l('BARU', 'NEW') }}</span>
                                            </div>
                                            <div
                                                class="px-3.5 py-2.5 text-[13px] leading-relaxed"
                                                :class="msg.type === 'user' ? 'evomi-chat-modal__bubble-user' : 'evomi-chat-modal__bubble-admin'"
                                            >
                                                <p x-show="msg.subject && msg.type === 'user'" class="text-[10px] opacity-80 mb-1 font-medium">{{ evomi_l('Topik:', 'Topic:') }} <span x-text="msg.subject"></span></p>
                                                <p class="whitespace-pre-wrap" x-text="msg.text"></p>
                                                <div class="mt-1.5 flex items-center gap-1.5" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'">
                                                    <span class="text-[10px] opacity-70" x-text="msg.timeLabel"></span>
                                                    <template x-if="msg.type === 'user'">
                                                        <span class="inline-flex text-sky-100">
                                                            <svg x-show="msg.isReadByAdmin" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" transform="translate(4 0)" opacity="0.7"/></svg>
                                                            <svg x-show="!msg.isReadByAdmin" class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                            <p x-show="msg.pending" class="text-[10px] text-slate-400 mt-1 px-1" :class="msg.type === 'user' ? 'text-right' : ''">{{ evomi_l('Mengirim...', 'Sending...') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button
                        type="button"
                        class="evomi-chat-modal__jump"
                        x-show="showJumpLatest && !loading && messages.length"
                        x-cloak
                        @click="jumpLatest()"
                    >
                        {{ evomi_l('Pesan terbaru', 'Latest messages') }}
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                </div>

                <div class="evomi-chat-modal__footer">
                    <div class="flex flex-wrap gap-1.5" x-show="!loading && messages.length > 0">
                        <template x-for="hint in hints" :key="'c-'+hint">
                            <button type="button" class="evomi-chat-modal__chip" @click="useHint(hint)" x-text="hint"></button>
                        </template>
                    </div>
                    <p x-show="sendError" x-cloak class="text-[12px] text-rose-600 font-medium" x-text="sendError"></p>
                    <form class="evomi-chat-modal__composer" @submit.prevent="send()">
                        <textarea
                            x-model="draft"
                            x-ref="composer"
                            rows="2"
                            class="evomi-chat-modal__input"
                            placeholder="{{ evomi_l('Tulis pesan untuk admin Evomi...', 'Write a message to Evomi admin...') }}"
                            @keydown.enter.exact.prevent="send()"
                        ></textarea>
                        <button
                            type="submit"
                            class="evomi-chat-modal__send"
                            :disabled="sending || !draft.trim()"
                            :aria-label="$L('Kirim', 'Send')"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                        </button>
                    </form>
                    <p class="text-[10px] text-slate-400 font-medium">{{ evomi_l('Enter ↵ kirim · Shift+Enter baris baru', 'Enter ↵ send · Shift+Enter new line') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
