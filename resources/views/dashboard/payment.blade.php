@extends('layouts.admin')

@section('title', 'Pembayaran | Evomi Admin')

@section('content')
<div x-data="evomiAdminPayment" class="mx-auto w-full max-w-4xl space-y-4 pb-12">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-extrabold tracking-tight text-gray-900" x-text="t('payment','title')"></h1>
            <p class="mt-0.5 text-sm text-gray-500" x-text="t('payment','subtitle')"></p>
        </div>
        <button
            type="button"
            @click="save()"
            :disabled="saving || loading"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50"
        >
            <svg x-show="!saving" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            <span x-text="saving ? common().saving : common().save_changes"></span>
        </button>
    </div>

    <div
        x-show="notice"
        x-cloak
        x-transition
        class="rounded-xl px-3.5 py-2.5 text-sm font-medium"
        :class="notice?.type === 'success'
            ? 'border border-emerald-100 bg-emerald-50 text-emerald-700'
            : 'border border-rose-100 bg-rose-50 text-rose-700'"
        x-text="notice?.text"
    ></div>

    <div x-show="loading" class="flex h-40 items-center justify-center rounded-2xl border border-gray-100 bg-white">
        <div class="w-7 h-7 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
    </div>

    <div x-show="!loading" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50/70 p-3 sm:p-4">
            <div class="grid grid-cols-1 xs:grid-cols-3 sm:grid-cols-3 gap-2">
                <template x-for="opt in providers" :key="opt.id">
                    <button
                        type="button"
                        @click="form.provider = opt.id"
                        class="admin-select-card relative flex items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition"
                        :class="form.provider === opt.id
                            ? 'admin-select-card-active border-gray-900 bg-gray-900 text-white shadow-sm'
                            : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                    >
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            :class="form.provider === opt.id ? 'bg-white/10' : 'bg-gray-100 text-gray-500'"
                        >
                            <template x-if="opt.id === 'manual'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                            </template>
                            <template x-if="opt.id === 'midtrans'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                            </template>
                            <template x-if="opt.id === 'xendit'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </template>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold leading-tight" x-text="opt.title"></span>
                            <span
                                class="admin-select-card-muted mt-0.5 block text-[11px]"
                                :class="form.provider === opt.id ? 'text-white/65' : 'text-gray-400'"
                                x-text="providerHint(opt)"
                            ></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            <div
                x-show="form.provider === 'manual'"
                class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 px-4 py-5 text-sm leading-relaxed text-gray-500"
                x-text="t('payment','manual_note')"
            ></div>

            <div x-show="form.provider === 'midtrans'" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-lg px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                            :class="configured.midtrans
                                ? 'border border-emerald-100 bg-emerald-50 text-emerald-700'
                                : 'border border-amber-100 bg-amber-50 text-amber-700'"
                            x-text="configured.midtrans ? t('payment','configured','Configured','Configured') : t('payment','incomplete','Belum lengkap','Incomplete')"
                        ></span>
                        <span class="text-[11px] text-gray-400">Webhook: /api/payments/midtrans/notification</span>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700">
                        <input type="checkbox" x-model="form.midtrans.is_production" class="rounded border-gray-300">
                        <span x-text="t('payment','production')"></span>
                    </label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block min-w-0">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500">Merchant ID</span>
                        <input x-model="form.midtrans.merchant_id" placeholder="Gxxxxxxxxxx / M001..." class="mt-1.5 w-full h-10 px-3 border border-gray-200 rounded-xl text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10">
                    </label>
                    <label class="block min-w-0">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500">Client Key</span>
                        <input x-model="form.midtrans.client_key" placeholder="SB-Mid-client-... / Mid-client-..." autocomplete="off" class="mt-1.5 w-full h-10 px-3 border border-gray-200 rounded-xl text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10">
                    </label>
                    <label class="block min-w-0 sm:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500">Server Key</span>
                        <div class="relative mt-1.5">
                            <input :type="show.midtransServer ? 'text' : 'password'" x-model="form.midtrans.server_key" placeholder="SB-Mid-server-... / Mid-server-..." autocomplete="off" class="w-full h-10 px-3 pr-10 border border-gray-200 rounded-xl text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10">
                            <button type="button" class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-50" @click="show.midtransServer = !show.midtransServer" :aria-label="show.midtransServer ? 'Hide' : 'Show'">
                                <svg x-show="!show.midtransServer" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-show="show.midtransServer" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </label>
                </div>
            </div>

            <div x-show="form.provider === 'xendit'" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-lg px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                            :class="configured.xendit
                                ? 'border border-emerald-100 bg-emerald-50 text-emerald-700'
                                : 'border border-amber-100 bg-amber-50 text-amber-700'"
                            x-text="configured.xendit ? t('payment','configured','Configured','Configured') : t('payment','incomplete','Belum lengkap','Incomplete')"
                        ></span>
                        <span class="text-[11px] text-gray-400">Webhook: /api/payments/xendit/callback</span>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700">
                        <input type="checkbox" x-model="form.xendit.is_production" class="rounded border-gray-300">
                        <span x-text="t('payment','production')"></span>
                    </label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block min-w-0">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500">Business / Merchant ID</span>
                        <input x-model="form.xendit.merchant_id" class="mt-1.5 w-full h-10 px-3 border border-gray-200 rounded-xl text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10">
                        <p class="mt-1 text-[11px] text-gray-400" x-text="t('payment','optional')"></p>
                    </label>
                    <label class="block min-w-0">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500">Callback Token</span>
                        <input x-model="form.xendit.callback_token" autocomplete="off" class="mt-1.5 w-full h-10 px-3 border border-gray-200 rounded-xl text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10">
                    </label>
                    <label class="block min-w-0 sm:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500">Secret Key</span>
                        <div class="relative mt-1.5">
                            <input :type="show.xenditSecret ? 'text' : 'password'" x-model="form.xendit.secret_key" autocomplete="off" class="w-full h-10 px-3 pr-10 border border-gray-200 rounded-xl text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10">
                            <button type="button" class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-50" @click="show.xenditSecret = !show.xenditSecret">
                                <svg x-show="!show.xenditSecret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-show="show.xenditSecret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
