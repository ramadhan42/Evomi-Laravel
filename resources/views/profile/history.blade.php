@extends('layouts.app')

@section('title', 'Riwayat Belanja | Evomi')

@section('content')
<x-profile-shell>
    <div x-data="evomiProfileHistory" class="space-y-6">
        <div class="profile-brand-header rounded-2xl sm:rounded-3xl p-6 sm:p-8 text-white" style="background: linear-gradient(135deg, #1172BA 0%, #0d5a94 100%)">
            <h1 class="text-2xl sm:text-3xl font-bold">Riwayat Belanja</h1>
            <p class="mt-1 text-white/80 text-sm"><span x-text="groups.length"></span> pesanan</p>
        </div>

        <div x-show="loading" x-cloak class="py-16 flex justify-center">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-[#1172BA] rounded-full animate-spin"></div>
        </div>

        <div x-show="!loading && error" x-cloak class="rounded-2xl bg-red-50 border border-red-100 text-red-700 px-4 py-3 text-sm" x-text="error"></div>

        <div x-show="!loading && groups.length === 0" x-cloak class="rounded-3xl border border-dashed border-gray-200 py-16 text-center">
            <p class="text-gray-500">Belum ada riwayat belanja.</p>
            <a href="{{ route('belanja') }}" data-soft-nav class="inline-flex mt-4 px-5 py-2.5 rounded-full bg-[#1172BA] text-white text-sm font-semibold">Mulai Belanja</a>
        </div>

        <div x-show="!loading && groups.length > 0" x-cloak class="space-y-4">
            <template x-for="group in pagedGroups" :key="group.groupId">
                <div class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <a :href="'/profile/history/' + group.groupId" data-soft-nav class="w-16 h-16 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shrink-0">
                            <img :src="group.imageUrl" :alt="group.productTitle" class="w-full h-full object-cover" x-on:error="$el.style.display='none'">
                        </a>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider" x-text="group.invoice"></p>
                                    <a :href="'/profile/history/' + group.groupId" data-soft-nav class="font-semibold text-gray-900 text-sm hover:text-[#1172BA]" x-text="group.productTitle"></a>
                                    <p class="text-xs text-gray-500 mt-1" x-text="group.dateLabel + ' · Qty ' + group.quantity"></p>
                                </div>
                                <p class="text-sm font-bold text-gray-900" x-text="group.totalLabel"></p>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border" :class="group.statusClass" x-text="group.statusLabel"></span>
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border" :class="group.paymentClass" x-text="group.paymentLabel"></span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a :href="'/profile/history/' + group.groupId" data-soft-nav class="text-xs font-semibold px-3 py-1.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50">Detail</a>
                                <button
                                    type="button"
                                    x-show="group.canConfirm"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-emerald-500 text-white"
                                    @click="confirmGroup(group)"
                                >Diterima</button>
                                <button
                                    type="button"
                                    x-show="group.canDelete"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-xl text-red-500 border border-red-100"
                                    @click="removeGroup(group)"
                                >Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-center gap-3 pt-2" x-show="pageCount > 1">
                <button type="button" class="px-3 py-1.5 rounded-xl border text-sm" :disabled="page <= 1" @click="page--" >Prev</button>
                <span class="text-sm text-gray-500" x-text="page + ' / ' + pageCount"></span>
                <button type="button" class="px-3 py-1.5 rounded-xl border text-sm" :disabled="page >= pageCount" @click="page++" >Next</button>
            </div>
        </div>
    </div>
</x-profile-shell>
@endsection
