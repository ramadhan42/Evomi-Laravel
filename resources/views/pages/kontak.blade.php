@extends('layouts.app')

@section('content')
@php $cms = $cms ?? []; @endphp
<div
    class="min-h-0 bg-white py-10 md:py-16 px-4 sm:px-6 md:px-12 lg:px-24 font-nohemi w-full text-gray-900"
    x-data="evomiKontak()"
>
    <div class="max-w-3xl mx-auto text-center mb-12 md:mb-16">
        <h1 class="text-[28px] sm:text-[32px] md:text-[48px] font-bold text-gray-900 mb-4 md:mb-6">{{ $cms['title'] ?? 'Hubungi Kami' }}</h1>
        <p class="text-gray-500 text-[15px] md:text-[18px] max-w-2xl mx-auto leading-relaxed">
            {!! $cms['subtitle'] ?? 'Punya pertanyaan atau ingin berkolaborasi? Tim Evomi siap mendengarkan Anda.' !!}
        </p>
    </div>

    <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-10 md:gap-12">
        <form class="bg-gray-50 p-6 sm:p-8 md:p-10 rounded-[28px] md:rounded-[32px] space-y-5 md:space-y-6" @submit.prevent="submit">
            <div
                x-show="status.message"
                x-cloak
                class="p-4 rounded-xl text-sm font-medium"
                :class="status.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                x-text="status.message"
            ></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <input
                    type="text"
                    x-model="form.name"
                    placeholder="Nama Anda"
                    required
                    class="w-full h-[52px] px-5 rounded-full border border-gray-200 bg-white text-gray-900 placeholder:text-gray-400 focus:border-[#1172BA] outline-none text-sm"
                >
                <input
                    type="email"
                    x-model="form.email"
                    placeholder="Email Anda"
                    required
                    class="w-full h-[52px] px-5 rounded-full border border-gray-200 bg-white text-gray-900 placeholder:text-gray-400 focus:border-[#1172BA] outline-none text-sm"
                >
            </div>
            <input
                type="text"
                x-model="form.subject"
                placeholder="Subjek"
                required
                class="w-full h-[52px] px-5 rounded-full border border-gray-200 bg-white text-gray-900 placeholder:text-gray-400 focus:border-[#1172BA] outline-none text-sm"
            >
            <textarea
                x-model="form.message"
                placeholder="Tulis pesan Anda di sini..."
                required
                class="w-full h-[150px] p-5 rounded-3xl border border-gray-200 bg-white text-gray-900 placeholder:text-gray-400 focus:border-[#1172BA] outline-none resize-none text-sm"
            ></textarea>

            @include('partials.turnstile-field', ['theme' => 'light'])

            <button
                type="submit"
                :disabled="loading"
                class="w-full h-[56px] bg-[#1172BA] text-white rounded-full font-bold flex items-center justify-center gap-2 hover:bg-[#0e609d] disabled:opacity-70 disabled:cursor-not-allowed transition-colors"
            >
                <span x-text="loading ? 'Mengirim...' : 'Kirim Pesan'"></span>
                <svg x-show="!loading" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                <svg x-show="loading" x-cloak class="w-[18px] h-[18px] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </button>
        </form>

        <div class="flex flex-col gap-6 md:gap-8">
            <div class="flex items-start gap-4 p-5 md:p-6 border border-gray-100 rounded-3xl">
                <div class="p-3 bg-blue-50 text-[#1172BA] rounded-2xl shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900">{{ $cms['email_label'] ?? 'Email' }}</h4>
                    <p class="text-gray-600 mt-0.5">{{ $cms['email_value'] ?? 'hello@evomi.id' }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4 p-5 md:p-6 border border-gray-100 rounded-3xl">
                <div class="p-3 bg-blue-50 text-[#1172BA] rounded-2xl shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900">{{ $cms['phone_label'] ?? 'WhatsApp' }}</h4>
                    <p class="text-gray-600 mt-0.5">{{ $cms['phone_value'] ?? '+62 812-3456-7890' }}</p>
                </div>
            </div>

            <div class="flex items-start gap-4 p-5 md:p-6 border border-gray-100 rounded-3xl">
                <div class="p-3 bg-blue-50 text-[#1172BA] rounded-2xl shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900">{{ $cms['address_label'] ?? 'Kantor Pusat' }}</h4>
                    <p class="text-gray-600 mt-0.5">{{ $cms['address_value'] ?? 'Jakarta, Indonesia' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
