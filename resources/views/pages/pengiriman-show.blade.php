@extends('layouts.app')

@section('title', 'Lacak Pengiriman | Evomi')

@section('content')
@php
    $tracking = $tracking ?? null;
@endphp

<div class="min-h-0 bg-gray-50 py-10 md:py-14 px-4 sm:px-6 md:px-12 font-nohemi w-full">
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('pengiriman') }}" data-soft-nav class="inline-flex items-center gap-2 text-sm text-[#1172BA] mb-6">← Kembali ke Pengiriman</a>
        <h1 class="text-[28px] md:text-[36px] font-bold text-gray-900 mb-8">Lacak Pengiriman</h1>

        @if ($tracking)
            <div class="bg-white rounded-[28px] border border-gray-100 p-6 md:p-8 shadow-sm mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">No. Resi</p>
                        <p class="text-lg font-bold text-gray-900">{{ $tracking['resi'] }}</p>
                    </div>
                    <span class="inline-flex self-start rounded-full bg-[#E8F4FC] text-[#1172BA] px-4 py-1.5 text-sm font-semibold">{{ $tracking['currentStatus'] }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                    <p><span class="text-gray-400">Kurir:</span> {{ $tracking['courier'] }}</p>
                    <p><span class="text-gray-400">Estimasi tiba:</span> {{ $tracking['estimatedDelivery'] }}</p>
                    <p class="sm:col-span-2"><span class="text-gray-400">Penerima:</span> {{ $tracking['recipient']['name'] }} · {{ $tracking['recipient']['address'] }}</p>
                </div>
            </div>

            <div class="bg-white rounded-[28px] border border-gray-100 p-6 md:p-8 shadow-sm">
                <h2 class="font-bold text-gray-900 mb-6">Timeline</h2>
                <div class="space-y-5">
                    @foreach ($tracking['timeline'] as $i => $item)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <span class="h-3 w-3 rounded-full {{ $i === 0 ? 'bg-[#1172BA]' : 'bg-gray-300' }}"></span>
                                @if (! $loop->last)
                                    <span class="w-px flex-1 bg-gray-200 my-1"></span>
                                @endif
                            </div>
                            <div class="pb-2">
                                <p class="font-semibold text-gray-900 text-sm">{{ $item['status'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $item['date'] }} · {{ $item['time'] }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white rounded-[28px] border border-dashed border-gray-200 p-8 text-center">
                <h2 class="font-semibold text-gray-900">Resi tidak ditemukan</h2>
                <p class="mt-2 text-sm text-gray-600">Periksa kembali nomor resi, atau coba lagi nanti.</p>
                <a href="{{ route('pengiriman') }}" data-soft-nav class="inline-flex mt-6 rounded-full bg-[#1172BA] text-white px-5 py-2.5 text-sm font-semibold">Coba Lacak Lagi</a>
            </div>
        @endif
    </div>
</div>
@endsection
