@extends('layouts.app')

@section('title', $product['title'] . ' | Evomi')

@section('content')
    <div class="bg-white flex flex-col justify-center items-center w-full overflow-visible relative z-0">
        @include('belanja.detail')
    </div>
@endsection
