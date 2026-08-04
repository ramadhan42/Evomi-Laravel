@extends('layouts.app')

@section('title', 'Belanja | Evomi')

@section('content')
    {{-- Wrapper biru full: navbar spacer + hero menyatu tanpa strip putih --}}
    <div class="w-full min-h-0 flex flex-col items-center justify-center bg-[#1172BA]">
        <div class="w-full bg-[#1172BA]">
            @include('belanja.hero')
        </div>
        <div class="w-full bg-white">
            @include('belanja.products')
        </div>
    </div>
@endsection
