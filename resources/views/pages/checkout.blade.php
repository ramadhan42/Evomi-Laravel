@extends('layouts.app')

@section('title', 'Checkout | Evomi')

@section('content')
<section
    class="bg-[#F0F3F7] w-full min-h-screen pt-4 pb-16 relative"
    x-data="evomiCheckout()"
    x-init="boot()"
>
    <div
        x-show="loading"
        x-cloak
        class="absolute inset-0 z-20 flex flex-col items-center justify-center min-h-[70vh] bg-[#F0F3F7]"
    >
        <svg class="w-10 h-10 animate-spin mb-4" :style="{ color: brand }" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-500 font-parkinsans text-sm">Menyiapkan pesanan…</p>
    </div>

    <div
        x-show="!loading && fatalError"
        x-cloak
        class="max-w-4xl mx-auto px-4 py-12 text-center"
    >
        <p class="text-red-500 mb-4 font-nohemi" x-text="fatalError"></p>
        <a
            href="{{ route('belanja') }}"
            class="inline-flex px-5 py-2.5 text-white rounded-xl text-sm font-semibold"
            :style="{ backgroundColor: brand }"
            data-soft-nav
        >Kembali</a>
    </div>

    <div
        x-show="!loading && !fatalError"
        x-cloak
        class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 relative z-10"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
    >
        <div class="mb-4 md:mb-5">
            <h1 class="text-2xl md:text-[28px] font-bold font-nohemi tracking-tight" :style="{ color: brand }">Checkout</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 md:gap-4 items-stretch">
            {{-- LEFT --}}
            <div class="lg:col-span-8 flex flex-col gap-3 md:gap-4 min-h-0">
                {{-- Alamat --}}
                <div class="bg-white rounded-xl border border-gray-100 p-4 md:p-5 shadow-sm shrink-0">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-white shrink-0"
                                :style="{ backgroundColor: brand }"
                            >
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Alamat Pengiriman</p>
                                <p class="text-[11px] text-gray-400 font-parkinsans">Pastikan alamat penerima sudah benar</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            x-show="!editingAddress && hasAddress"
                            @click="startEditAddress()"
                            class="shrink-0 px-3.5 py-1.5 rounded-lg border text-sm font-semibold transition hover:bg-gray-50"
                            :style="{ color: brand, borderColor: brand + '55' }"
                        >Ubah</button>
                    </div>

                    <div x-show="!editingAddress && hasAddress" class="rounded-xl bg-gray-50/80 border border-gray-100 p-3.5 md:p-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <span
                                class="mt-0.5 text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md shrink-0"
                                :style="{ color: brand, backgroundColor: brand + '14' }"
                            >Rumah</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900" x-text="form.name"></p>
                                <p class="text-[13px] text-gray-600 mt-1 leading-relaxed" x-text="form.address"></p>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[12px] text-gray-500 font-parkinsans">
                                    <span x-text="form.phone"></span>
                                    <span x-show="form.email" class="text-gray-300">·</span>
                                    <span x-show="form.email" x-text="form.email"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="editingAddress || !hasAddress" x-cloak class="space-y-3 font-parkinsans">
                        <p x-show="!hasAddress" class="text-sm text-gray-500">Isi alamat pengiriman untuk melanjutkan.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Nama penerima</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/></svg>
                                    <input type="text" x-model="draft.name" placeholder="Nama lengkap" class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2" :style="{ '--tw-ring-color': brand + '40' }">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Email</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                    <input type="email" x-model="draft.email" placeholder="email@kamu.com" class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2" :style="{ '--tw-ring-color': brand + '40' }">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">No. HP</label>
                                <div class="relative max-w-md">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                    <input type="tel" x-model="draft.phone" placeholder="08xxxxxxxxxx" class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2" :style="{ '--tw-ring-color': brand + '40' }">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1.5">Alamat lengkap</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                <textarea x-model="draft.address" rows="3" placeholder="Jalan, nomor, RT/RW, kelurahan, kecamatan, kota, kode pos" class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-900 outline-none resize-none focus:ring-2" :style="{ '--tw-ring-color': brand + '40' }"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end pt-1">
                            <button type="button" x-show="hasAddress" @click="cancelAddressEdit()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                            <button type="button" @click="saveAddress()" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" :style="{ backgroundColor: brand }">Simpan Alamat</button>
                        </div>
                    </div>
                </div>

                {{-- Evomi Official / Detail Pesanan --}}
                <div class="bg-white rounded-xl border border-gray-100 p-4 md:p-5 shadow-sm flex-1 flex flex-col min-h-0">
                    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 text-white font-nohemi font-bold text-sm"
                                :style="{ backgroundColor: brand }"
                            >E</div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-sm font-bold text-gray-900">Evomi Official</h2>
                                    <svg class="w-4 h-4 shrink-0" :style="{ color: brand }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                </div>
                                <p class="text-[11px] text-gray-400 font-parkinsans">Toko resmi Evomi · Pengiriman terjamin</p>
                            </div>
                        </div>
                        <span
                            class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full"
                            :style="{ color: brand, backgroundColor: brand + '14' }"
                        >Official</span>
                    </div>

                    <div class="space-y-4 flex-1">
                        <template x-for="item in items" :key="item.id">
                            <div class="flex gap-3 sm:gap-4 items-start">
                                <div
                                    class="w-16 h-16 sm:w-[72px] sm:h-[72px] rounded-lg overflow-hidden border border-gray-100 shrink-0 flex items-center justify-center"
                                    :style="{ backgroundColor: brand + '12' }"
                                >
                                    <img :src="item.image" :alt="item.title" class="w-full h-full object-contain p-1.5">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 leading-snug" x-text="item.title"></h3>
                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-bold" :style="{ color: brand }" x-text="formatPrice(itemUnitPrice(item))"></p>
                                            <p class="text-[11px] text-gray-400 mt-0.5" x-text="item.quantity + ' × ' + formatPrice(itemUnitPrice(item)) + ' = ' + formatPrice(itemUnitPrice(item) * item.quantity)"></p>
                                        </div>
                                        <div class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                                            <button type="button" @click="updateQty(item.id, -1)" :disabled="item.quantity <= 1" class="w-7 h-7 rounded-md text-gray-600 hover:bg-white text-sm font-bold disabled:opacity-40">−</button>
                                            <span class="w-8 text-center text-sm font-semibold text-gray-800" x-text="item.quantity"></span>
                                            <button type="button" @click="updateQty(item.id, 1)" :disabled="item.quantity >= Math.max(1, item.stock || 1)" class="w-7 h-7 rounded-md text-gray-600 hover:bg-white text-sm font-bold disabled:opacity-40">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 rounded-xl border p-3.5" :style="{ borderColor: brand + '28', backgroundColor: brand + '08' }">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-gray-900">
                                        <span x-text="courierLabel || 'Metode pengiriman'"></span>
                                        <span class="font-semibold" :style="{ color: brand }" x-text="' (' + formatPrice(shippingCost) + ')'"></span>
                                    </p>
                                    <span
                                        x-show="paymentMethod === 'cod'"
                                        x-cloak
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded border bg-white"
                                        :style="{ color: brand, borderColor: brand + '55' }"
                                    >COD</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" x-text="shippingEtaLabel"></p>
                                <p class="text-[11px] text-gray-400 mt-0.5" x-show="selectedKurir?.destinasi" x-text="selectedKurir?.destinasi" x-cloak></p>
                            </div>
                            <div class="relative shrink-0">
                                <select
                                    class="appearance-none text-xs font-semibold pl-2 pr-6 py-1.5 rounded-lg border bg-white cursor-pointer outline-none max-w-[160px] text-gray-800"
                                    :style="{ borderColor: brand + '40' }"
                                    :value="selectedKurir?.id || ''"
                                    @change="selectKurirById($event.target.value)"
                                >
                                    <template x-for="kurir in kurirs" :key="'ship-' + kurir.id">
                                        <option :value="kurir.id" x-text="kurir.nama + ' ' + (kurir.jenis || '')"></option>
                                    </template>
                                </select>
                                <svg class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <label class="flex items-center justify-between gap-2 text-sm text-gray-600 mb-1.5">
                            <span class="font-medium">Tambah catatan</span>
                            <span class="text-[11px] text-gray-400" x-text="orderNote.length + '/200'"></span>
                        </label>
                        <input
                            type="text"
                            maxlength="200"
                            x-model="orderNote"
                            placeholder="Contoh: tolong bungkus kado"
                            class="w-full px-3 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-900 outline-none focus:ring-2 bg-white"
                            :style="{ '--tw-ring-color': brand + '40' }"
                        >
                    </div>
                </div>
            </div>

            {{-- RIGHT: satu card setinggi kolom kiri --}}
            <div class="lg:col-span-4 flex flex-col min-h-0">
                <div class="bg-white rounded-xl border border-gray-100 p-4 md:p-5 shadow-sm flex flex-col flex-1 h-full">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-bold text-gray-900">Metode Pembayaran</h2>
                        <span class="text-xs font-semibold" :style="{ color: brand }">Lihat Semua</span>
                    </div>

                    <div class="space-y-2">
                        <button
                            type="button"
                            @click="paymentMethod = 'cod'"
                            class="w-full flex items-center gap-3 p-3 rounded-xl border transition text-left"
                            :class="paymentMethod === 'cod' ? 'border-transparent' : 'border-gray-100 hover:border-gray-200'"
                            :style="paymentMethod === 'cod' ? { borderColor: brand, backgroundColor: brand + '0A' } : {}"
                        >
                            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" :style="{ backgroundColor: paymentMethod === 'cod' ? brand : '#F3F4F6', color: paymentMethod === 'cod' ? '#fff' : '#9CA3AF' }">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-bold text-gray-900">Cash on Delivery</span>
                                <span class="block text-[11px] text-gray-500 truncate">Bayar saat barang sampai</span>
                            </span>
                            <span
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                :class="paymentMethod === 'cod' ? 'border-transparent' : 'border-gray-300'"
                                :style="paymentMethod === 'cod' ? { backgroundColor: brand, borderColor: brand } : {}"
                            >
                                <svg x-show="paymentMethod === 'cod'" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="paymentMethod = 'qris'"
                            class="w-full flex items-center gap-3 p-3 rounded-xl border transition text-left"
                            :class="paymentMethod === 'qris' ? 'border-transparent' : 'border-gray-100 hover:border-gray-200'"
                            :style="paymentMethod === 'qris' ? { borderColor: brand, backgroundColor: brand + '0A' } : {}"
                        >
                            <span class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" :style="{ backgroundColor: paymentMethod === 'qris' ? brand : '#F3F4F6', color: paymentMethod === 'qris' ? '#fff' : '#9CA3AF' }">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/></svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-bold text-gray-900">QRIS</span>
                                <span class="block text-[11px] text-gray-500 truncate">Bayar dengan QRIS</span>
                            </span>
                            <span
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                :class="paymentMethod === 'qris' ? 'border-transparent' : 'border-gray-300'"
                                :style="paymentMethod === 'qris' ? { backgroundColor: brand, borderColor: brand } : {}"
                            >
                                <svg x-show="paymentMethod === 'qris'" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </span>
                        </button>
                    </div>

                    <div class="h-px bg-gray-100 my-4"></div>

                    <h2 class="text-sm font-bold text-gray-900 mb-4">Ringkasan Belanja</h2>

                    <div class="space-y-2.5 text-sm font-parkinsans">
                        <div class="flex justify-between text-gray-600">
                            <span x-text="'Total Harga (' + itemCount + ' barang)'"></span>
                            <span class="font-medium text-gray-800" x-text="formatPrice(productSubtotal)"></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Total Ongkos Kirim</span>
                            <span class="font-medium text-gray-800" x-text="formatPrice(shippingCost)"></span>
                        </div>
                        <div class="flex justify-between text-[#CA3500]" x-show="promoDiscount > 0" x-cloak>
                            <span>Promo</span>
                            <span class="font-medium" x-text="'−' + formatPrice(promoDiscount)"></span>
                        </div>
                        <div class="h-px bg-gray-100 my-2"></div>
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-900">Total Tagihan</span>
                            <span class="text-lg font-bold font-nohemi" :style="{ color: brand }" x-text="formatPrice(total)"></span>
                        </div>
                    </div>

                    <div class="mt-auto pt-5">
                        <p class="text-[12px] text-red-500 mb-3" x-show="formError" x-text="formError" x-cloak></p>
                        <button
                            type="button"
                            @click="submitCheckout()"
                            :disabled="processing"
                            class="w-full text-white px-5 py-3.5 rounded-xl font-bold text-[15px] transition active:scale-[0.99] disabled:opacity-50 flex items-center justify-center gap-2 shadow-sm"
                            :style="{ backgroundColor: brand, boxShadow: '0 8px 20px ' + brand + '33' }"
                        >
                            <svg x-show="!processing" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            <svg x-show="processing" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="processing ? 'Memproses…' : 'Bayar Sekarang'"></span>
                        </button>
                        <p class="mt-3 text-[11px] text-gray-400 leading-relaxed text-center">
                            Dengan melanjutkan, kamu menyetujui syarat &amp; ketentuan Evomi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="modal.open" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="absolute inset-0" @click="closeModal()"></div>
            <div class="relative bg-white w-full max-w-[380px] rounded-[24px] shadow-2xl p-7 text-center">
                <div
                    class="mx-auto mb-4 w-16 h-16 rounded-full flex items-center justify-center"
                    :class="modal.type === 'success' ? 'bg-green-50 text-green-500' : 'bg-red-50 text-red-500'"
                >
                    <template x-if="modal.type === 'success'">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="modal.type !== 'success'">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                </div>
                <h3 class="font-nohemi text-[20px] font-bold text-[#0F172A] mb-2" x-text="modal.title"></h3>
                <p class="font-parkinsans text-[14px] text-[#64748B] mb-6" x-text="modal.message"></p>
                <button
                    type="button"
                    @click="closeModal()"
                    class="w-full py-3 rounded-xl font-semibold text-white"
                    :style="{ backgroundColor: brand }"
                    x-text="modal.type === 'success' ? 'Selesai' : 'Tutup'"
                ></button>
            </div>
        </div>
    </template>
</section>
@endsection
