@extends('layouts.app')

@section('title', 'Pesan Anda | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileChat" class="space-y-6">
        <div class="profile-brand-header rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white flex items-center justify-between gap-4" style="background: linear-gradient(135deg, #1172BA 0%, #0d5a94 100%)">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold">Pesan Anda</h1>
                <p class="mt-1 text-white/80 text-sm">Admin online · siap membantu</p>
            </div>
            <button type="button" class="px-4 py-2 rounded-full bg-white/15 text-white text-sm font-semibold hover:bg-white/25" @click="load()">Refresh</button>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden flex flex-col min-h-[420px]">
            <div class="flex-1 p-4 sm:p-5 space-y-3 overflow-y-auto max-h-[480px]" x-ref="thread">
                <div x-show="loading" x-cloak class="py-12 flex justify-center">
                    <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
                </div>

                <div x-show="!loading && messages.length === 0" x-cloak class="py-12 text-center text-sm text-gray-400">
                    Belum ada pesan. Tulis pertanyaan Anda di bawah.
                </div>

                <template x-for="msg in messages" :key="msg.id">
                    <div class="flex" :class="msg.type === 'user' ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm"
                            :class="msg.type === 'user' ? 'bg-[#1172BA] text-white rounded-br-md' : 'bg-gray-100 text-gray-800 rounded-bl-md'"
                        >
                            <p class="whitespace-pre-wrap" x-text="msg.text"></p>
                            <p class="mt-1 text-[10px] opacity-70" x-text="msg.timeLabel"></p>
                        </div>
                    </div>
                </template>
            </div>

            <form class="border-t border-gray-100 p-4 space-y-3" @submit.prevent="send">
                <div class="flex flex-wrap gap-2">
                    <template x-for="hint in hints" :key="hint">
                        <button type="button" class="text-[11px] px-3 py-1 rounded-full bg-gray-50 text-gray-600 border border-gray-100 hover:bg-gray-100" @click="draft = hint" x-text="hint"></button>
                    </template>
                </div>
                <div class="flex gap-2">
                    <textarea
                        x-model="draft"
                        rows="2"
                        placeholder="Tulis pesan..."
                        class="flex-1 rounded-2xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-[#1172BA] focus:ring-2 focus:ring-[#1172BA]/15"
                        @keydown.enter.exact.prevent="send()"
                    ></textarea>
                    <button type="submit" :disabled="sending || !draft.trim()" class="shrink-0 px-5 rounded-2xl bg-[#1172BA] text-white text-sm font-semibold disabled:opacity-50">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</x-profile-shell>
@endsection
