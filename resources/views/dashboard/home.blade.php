@extends('layouts.admin')

@section('title', 'Dashboard | Evomi')

@section('content')
<div x-data="evomiAdminHome" class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Overview</h1>
        <p class="text-gray-500 mt-1">Selamat datang kembali, lihat performa Evomi hari ini.</p>
    </div>

    <template x-if="loading">
        <div class="w-full min-h-[60vh] flex items-center justify-center">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div>
        </div>
    </template>

    <template x-if="!loading && error">
        <div class="bg-red-50 border border-red-100 text-red-700 rounded-2xl px-5 py-4 text-sm" x-text="error"></div>
    </template>

    {{-- x-show (bukan x-if) agar x-ref chart tetap ada di DOM saat paintSalesChart() --}}
    <div x-show="!loading && !error" x-cloak class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="{{ route('dashboard.page', 'products') }}" class="relative bg-white p-6 rounded-2xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-50/50 hover:shadow-[0_4px_25px_rgb(0,0,0,0.06)] hover:border-gray-200 transition-all">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Total Produk</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-2 tracking-tight" x-text="stats.totalProducts"></h3>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl shrink-0 text-gray-700">
                            @include('partials.admin-icon', ['name' => 'products', 'active' => false])
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">Aktif</span>
                    </div>
                </a>

                <a href="{{ route('dashboard.page', 'orders') }}" class="relative bg-white p-6 rounded-2xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-50/50 hover:shadow-[0_4px_25px_rgb(0,0,0,0.06)] hover:border-gray-200 transition-all">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-2 tracking-tight" x-text="stats.totalOrders"></h3>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl shrink-0 text-gray-700">
                            @include('partials.admin-icon', ['name' => 'orders', 'active' => false])
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">Semua status</span>
                    </div>
                </a>

                <a href="{{ route('dashboard.page', 'users') }}" class="relative bg-white p-6 rounded-2xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-50/50 hover:shadow-[0_4px_25px_rgb(0,0,0,0.06)] hover:border-gray-200 transition-all">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Pengguna Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-2 tracking-tight" x-text="stats.activeUsers"></h3>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl shrink-0 text-gray-700">
                            @include('partials.admin-icon', ['name' => 'users', 'active' => false])
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">Terdaftar</span>
                    </div>
                </a>

                <a href="{{ route('dashboard.page', 'orders') }}" class="relative bg-white p-6 rounded-2xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-50/50 hover:shadow-[0_4px_25px_rgb(0,0,0,0.06)] hover:border-gray-200 transition-all">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500">Total Pendapatan</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-2 tracking-tight" x-text="formatRupiah(stats.totalRevenue)"></h3>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700" aria-hidden="true"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">Pembayaran berhasil</span>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-2">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-50/50 min-h-[400px] flex flex-col">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Grafik Penjualan</h2>
                            <p class="text-xs text-gray-400 mt-1">
                                Tren pendapatan per
                                <span x-text="chartPeriodLabel"></span>
                                <span x-show="chartData.length" x-cloak>
                                    · total
                                    <span class="font-semibold text-gray-600" x-text="formatRupiah(stats.totalRevenue)"></span>
                                </span>
                            </p>
                        </div>
                        <div class="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1 shrink-0" role="tablist" aria-label="Periode grafik">
                            <template x-for="opt in chartPeriodOptions" :key="opt.id">
                                <button
                                    type="button"
                                    role="tab"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                    :class="chartPeriod === opt.id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                    :aria-selected="chartPeriod === opt.id"
                                    @click="setChartPeriod(opt.id)"
                                    x-text="opt.label"
                                ></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex-1 w-full min-h-[300px]">
                        {{-- SVG di-paint via JS (bukan x-html): x-html + template x-if sering gagal mount di soft-nav admin --}}
                        <div
                            class="relative w-full h-[300px] select-none"
                            x-ref="salesChartBox"
                            @mousemove="onChartMove($event)"
                            @mouseleave="onChartLeave()"
                        >
                            <div class="w-full h-full" x-ref="salesChartMount"></div>

                            <div
                                x-show="chartHover"
                                x-cloak
                                class="admin-chart-tooltip pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-[120%] rounded-2xl border border-slate-200/90 bg-slate-900 px-3.5 py-3 shadow-[0_18px_40px_-16px_rgba(15,23,42,0.55)] min-w-[10.5rem]"
                                :style="chartHover ? { left: chartHover.leftPct + '%', top: chartHover.topPct + '%' } : {}"
                            >
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400" x-text="chartHover?.name"></p>
                                <p class="mt-1.5 text-[15px] font-semibold text-white tabular-nums tracking-tight" x-text="chartHover?.label"></p>
                                <p class="mt-1 text-[11px] text-slate-400">Pendapatan</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-4" x-show="chartTable.length" x-cloak>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">
                                Ringkasan
                                <span class="font-medium text-gray-500" x-text="'per ' + chartPeriodLabel"></span>
                            </h3>
                            <span class="text-[11px] text-gray-400" x-text="salesPeriodUnitLabel(chartPeriod, chartTable.length)"></span>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-left text-[11px] uppercase tracking-wider text-gray-400">
                                    <tr>
                                        <th class="px-3.5 py-2.5 font-semibold">Periode</th>
                                        <th class="px-3.5 py-2.5 font-semibold text-right">Pendapatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in chartTable" :key="row.dayKey">
                                        <tr class="border-t border-gray-50">
                                            <td class="px-3.5 py-2.5 text-gray-700" x-text="row.name"></td>
                                            <td class="px-3.5 py-2.5 text-right font-semibold tabular-nums text-gray-900" x-text="row.totalLabel"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 bg-gray-50/80">
                                        <td class="px-3.5 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Total</td>
                                        <td class="px-3.5 py-2.5 text-right text-sm font-bold tabular-nums text-gray-900" x-text="formatRupiah(stats.totalRevenue)"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 sm:p-6 rounded-2xl shadow-[0_2px_20px_rgb(0,0,0,0.04)] border border-gray-50/50 min-h-[400px] flex flex-col">
                    <h2 class="text-lg font-semibold text-gray-900 mb-5">Pesanan Terbaru</h2>

                    <template x-if="recentOrders.length === 0">
                        <div class="flex-1 flex items-center justify-center text-sm text-gray-400">Belum ada pesanan.</div>
                    </template>

                    <div class="space-y-3 flex-1" x-show="recentOrders.length > 0">
                        <template x-for="order in recentOrders" :key="order.id">
                            <a
                                href="{{ route('dashboard.page', 'orders') }}"
                                class="block w-full rounded-2xl border border-gray-100 bg-gray-50/40 p-3.5 text-left transition-colors hover:border-gray-200 hover:bg-white hover:shadow-sm"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 rounded-xl border border-gray-200 bg-white flex items-center justify-center overflow-hidden shrink-0">
                                        <template x-if="order.imageUrl">
                                            <img :src="order.imageUrl" :alt="order.productTitle" class="w-full h-full object-contain p-1" x-on:error="$el.style.display='none'">
                                        </template>
                                        <template x-if="!order.imageUrl">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider break-all leading-snug" x-text="order.id"></p>
                                        <p class="text-sm font-bold text-gray-900 mt-1 leading-snug break-words" x-text="order.productTitle"></p>
                                        <p class="text-xs text-gray-500 mt-1 leading-snug break-words">
                                            Oleh: <span x-text="order.buyerName"></span>
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border" :class="order.statusClass" x-text="order.statusLabel"></span>
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-md border" :class="order.paymentClass" x-text="order.paymentLabel"></span>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                            <span class="text-gray-400" x-text="order.orderDate"></span>
                                            <span class="font-semibold text-gray-900" x-text="order.totalLabel"></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
    </div>
</div>
@endsection
