@extends('layouts.app')

@section('title', 'Kontak | Evomi')

@section('content')
<div
    class="min-h-0 bg-white py-10 md:py-16 px-4 sm:px-6 md:px-12 lg:px-24 font-nohemi w-full"
    x-data="evomiKontak()"
>
    <div class="max-w-5xl mx-auto text-center mb-10 md:mb-14">
        <h1 class="text-[28px] sm:text-[32px] md:text-[48px] font-bold text-gray-900 mb-3 md:mb-4">Hubungi Kami</h1>
        <p class="text-gray-600 text-sm md:text-base max-w-2xl mx-auto">
            Punya pertanyaan atau ingin berkolaborasi? Tim Evomi siap mendengarkan Anda.
        </p>
    </div>

    <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-10 md:gap-12">
        <form class="bg-gray-50 p-6 sm:p-8 md:p-10 rounded-[28px] md:rounded-[32px] space-y-5 md:space-y-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" x-model="form.name" placeholder="Nama Anda" required class="w-full h-[52px] px-5 rounded-full border border-gray-200 focus:border-[#1172BA] outline-none text-sm">
                <input type="email" x-model="form.email" placeholder="Email Anda" required class="w-full h-[52px] px-5 rounded-full border border-gray-200 focus:border-[#1172BA] outline-none text-sm">
            </div>
            <input type="text" x-model="form.subject" placeholder="Subjek" required class="w-full h-[52px] px-5 rounded-full border border-gray-200 focus:border-[#1172BA] outline-none text-sm">
            <textarea x-model="form.message" placeholder="Tulis pesan Anda di sini..." required class="w-full h-[150px] p-5 rounded-3xl border border-gray-200 focus:border-[#1172BA] outline-none resize-none text-sm"></textarea>

            <div
                x-show="status.message"
                x-cloak
                class="p-4 rounded-xl text-sm"
                :class="status.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                x-text="status.message"
            ></div>

            <button type="submit" :disabled="loading" class="w-full h-[56px] bg-[#1172BA] text-white rounded-full font-bold flex items-center justify-center gap-2 hover:bg-[#0e609d] disabled:opacity-60 transition-colors">
                <span x-text="loading ? 'Mengirim...' : 'Kirim Pesan'"></span>
            </button>
        </form>

        <div class="flex flex-col gap-6 md:gap-8">
            @foreach ([
                ['label' => 'Email', 'value' => 'hello@evomi.id'],
                ['label' => 'WhatsApp', 'value' => '+62 812-3456-7890'],
                ['label' => 'Kantor Pusat', 'value' => 'Jakarta, Indonesia'],
            ] as $info)
                <div class="flex items-start gap-4 p-5 md:p-6 border border-gray-100 rounded-3xl">
                    <div class="p-3 bg-blue-50 text-[#1172BA] rounded-2xl shrink-0 font-bold text-sm">{{ substr($info['label'], 0, 1) }}</div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $info['label'] }}</p>
                        <p class="text-sm text-gray-600 mt-1">{{ $info['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
