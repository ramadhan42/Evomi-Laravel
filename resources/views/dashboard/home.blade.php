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

    <template x-if="!loading && !error">
        <div class="space-y-6">
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
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Grafik Penjualan</h2>
                    <div class="flex-1 w-full min-h-[280px] relative">
                        <template x-if="chartData.length === 0">
                            <div class="w-full h-full min-h-[280px] flex items-center justify-center text-gray-400 text-sm">
                                Belum ada data penjualan.
                            </div>
                        </template>
                        <template x-if="chartData.length > 0">
                            <div class="h-full flex flex-col">
                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full flex-1 min-h-[220px]">
                                    <defs>
                                        <linearGradient id="evomiSalesGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stop-color="#10B981" stop-opacity="0.18"></stop>
                                            <stop offset="95%" stop-color="#10B981" stop-opacity="0"></stop>
                                        </linearGradient>
                                    </defs>
                                    <path :d="chartArea()" fill="url(#evomiSalesGrad)"></path>
                                    <path :d="chartPath()" fill="none" stroke="#10B981" stroke-width="1.5" vector-effect="non-scaling-stroke"></path>
                                </svg>
                                <div class="flex justify-between gap-1 pt-3 text-[10px] sm:text-xs text-gray-400">
                                    <template x-for="(point, idx) in chartData" :key="idx">
                                        <span class="truncate" x-text="point.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
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
    </template>
</div>
@endsection
