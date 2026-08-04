@extends('layouts.admin')

@section('title', ($pageTitle ?? 'Dashboard') . ' | Evomi')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="text-gray-500 mt-1">Modul admin Evomi — halaman ini segera hadir.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-50/50 shadow-[0_2px_20px_rgb(0,0,0,0.04)] p-8 sm:p-12 text-center">
        <div class="mx-auto h-14 w-14 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-500 mb-5">
            @include('partials.admin-icon', ['name' => $pageKey ?? 'dashboard', 'active' => false])
        </div>
        <h2 class="text-xl font-bold text-gray-900">Halaman ini segera hadir</h2>
        <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
            Sidebar sudah mengikuti dashboard Next.js Evomi. CRUD penuh untuk
            <span class="font-semibold text-gray-700">{{ $pageTitle }}</span>
            akan dihubungkan ke API admin yang sudah ada pada tahap berikutnya.
        </p>
        <a
            href="{{ route('dashboard') }}"
            class="inline-flex mt-6 items-center justify-center px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 transition-colors"
        >
            Kembali ke Overview
        </a>
    </div>
</div>
@endsection
