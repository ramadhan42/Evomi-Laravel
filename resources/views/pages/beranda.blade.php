@extends('layouts.app')

@section('title', 'Evomi | Premium Fragrance & Perfume')

@section('content')
    <div class="bg-[#1172ba] w-full min-h-screen flex flex-col">
        @include('beranda.hero')
        @include('beranda.second')

        <div id="about">
            @include('beranda.third')
        </div>

        @include('beranda.divider', [
            'src' => 'section 3/vector-divider.svg',
            'variant' => 'after-third',
        ])
        @include('beranda.fifth')
        @include('beranda.divider', [
            'src' => 'section 6/divider.svg',
            'variant' => 'after-fifth',
        ])
        @include('beranda.sixth')
        @include('beranda.seventh')
    </div>
@endsection
