@extends('layouts.app')

@section('title', 'Pengiriman | Evomi')

@section('content')
@php
    $steps = [
        ['title' => 'Pesanan Diterima', 'desc' => 'Sistem kami memverifikasi detail pesanan Anda.'],
        ['title' => 'Proses Pengemasan', 'desc' => 'Tim kami menyiapkan parfum dengan keamanan ekstra.'],
        ['title' => 'Dalam Perjalanan', 'desc' => 'Kurir mengirimkan paket ke lokasi Anda.'],
        ['title' => 'Paket Diterima', 'desc' => 'Nikmati aroma baru dari Evomi!'],
    ];
@endphp

<div
    class="min-h-0 bg-white py-10 md:py-16 px-4 sm:px-6 md:px-12 lg:px-24 font-nohemi w-full"
    x-data="{ resi: '', error: '' }"
>
    <div class="max-w-4xl mx-auto text-center mb-12 md:mb-16">
        <h1 class="text-[28px] sm:text-[32px] md:text-[48px] font-bold text-gray-900 mb-3 md:mb-4">Informasi Pengiriman</h1>
        <p class="text-gray-600 text-sm md:text-base max-w-2xl mx-auto">
            Kami memastikan setiap tetes aroma Evomi sampai ke tangan Anda dengan aman dan tepat waktu.
        </p>
    </div>

    <h2 class="text-[14px] md:text-[20px] font-bold text-[#1172BA] mb-8 md:mb-12 text-center uppercase tracking-widest">Alur Pengiriman Kami</h2>
    <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5 md:gap-8 mb-12 md:mb-16">
        @foreach ($steps as $i => $step)
            <div class="flex flex-col items-center text-center p-5 md:p-6 bg-gray-50 rounded-3xl">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-white rounded-full shadow-sm text-[#1172BA] mb-5 flex items-center justify-center font-bold text-lg">{{ $i + 1 }}</div>
                <h3 class="font-semibold text-gray-900 text-sm md:text-base mb-2">{{ $step['title'] }}</h3>
                <p class="text-xs md:text-sm text-gray-600 leading-relaxed">{{ $step['desc'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
        <div class="p-6 md:p-8 border border-gray-100 rounded-[28px] md:rounded-[32px]">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Estimasi Waktu</h3>
            <ul class="space-y-3 text-sm text-gray-700">
                <li>• Jabodetabek: 1-2 hari kerja</li>
                <li>• Pulau Jawa: 2-3 hari kerja</li>
                <li>• Luar Pulau Jawa: 3-5 hari kerja</li>
            </ul>
        </div>

        <div class="p-6 md:p-8 bg-[#1172BA] text-white rounded-[28px] md:rounded-[32px]">
            <h3 class="text-lg font-bold mb-3">Lacak Pesanan Anda</h3>
            <p class="text-sm text-white/90 mb-5">Masukkan nomor resi pengiriman Anda. Jika resi belum tersedia, paket belum bisa dilacak.</p>
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text" x-model="resi" placeholder="Masukkan nomor resi..." class="w-full h-[48px] rounded-full px-4 text-gray-900 bg-white text-sm outline-none">
                <button
                    type="button"
                    class="h-[48px] px-6 rounded-full bg-white text-[#1172BA] font-bold hover:bg-gray-100 shrink-0"
                    @click="
                        if (!resi.trim()) { error = 'Masukkan nomor resi terlebih dahulu. Pesanan tanpa no resi belum bisa dilacak.'; return; }
                        error = '';
                        if (window.softNavigate) softNavigate('/pengiriman/' + encodeURIComponent(resi.trim()));
                        else window.location.href = '/pengiriman/' + encodeURIComponent(resi.trim());
                    "
                >Lacak</button>
            </div>
            <p class="mt-3 text-sm text-amber-100 bg-white/10 rounded-xl px-3 py-2" x-show="error" x-text="error" x-cloak></p>
        </div>
    </div>
</div>
@endsection
