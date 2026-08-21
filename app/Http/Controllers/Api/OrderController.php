<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Models\Product;
use App\Models\Promo;
use App\Models\KurirTarif;
use App\Support\OrderNumber;
use App\Support\ShippingConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const UNIT_WEIGHT_GRAMS = ShippingConfig::UNIT_WEIGHT_GRAMS;

    /**
     * READ: Mengambil detail satu pesanan berdasarkan ID untuk user yang login
     */
    public function show($id, Request $request)
    {
        $user = $request->user();

        $order = Order::with('product')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ], 200);
    }

    /**
     * READ ALL: Mengambil semua pesanan dari semua user (Untuk Admin)
     */
    public function getAllOrders()
    {
        $orders = Order::with(['product', 'user'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua data pesanan berhasil diambil.',
            'data' => $orders,
        ], 200);
    }

    /**
     * DASHBOARD STATS: Ringkasan pendapatan — hanya pembayaran berhasil (success).
     * Pending / cancelled tidak dihitung.
     */
    public function getTotalRevenue()
    {
        $paid = Order::query()->successfulPayment();

        $totalProductPrice = (float) (clone $paid)->sum('total_price');
        $totalShippingRevenue = (float) (clone $paid)->sum('shipping_cost');
        $totalPromoDiscount = (float) (clone $paid)->sum('promo_discount');
        $totalQuantitySold = (int) (clone $paid)->sum('quantity');
        $totalOrdersCount = (clone $paid)->count();
        $totalRevenue = max(0, $totalProductPrice + $totalShippingRevenue - $totalPromoDiscount);

        return response()->json([
            'success' => true,
            'message' => 'Data ringkasan pendapatan admin (hanya pembayaran berhasil) berhasil dimuat.',
            'data' => [
                'total_revenue' => (int) $totalRevenue,
                'total_revenue_clean' => (int) max(0, $totalProductPrice - $totalPromoDiscount),
                'total_orders_count' => $totalOrdersCount,
                'total_items_sold' => $totalQuantitySold,
                'total_shipping_cost' => (int) $totalShippingRevenue,
                'total_promo_discount' => (int) $totalPromoDiscount,
                'currency' => 'IDR',
            ],
        ], 200);
    }

    /**
     * Authenticated checkout (cart / logged-in buy-now).
     */
    public function checkout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak valid. Harap login kembali.',
            ], 401);
        }

        $items = $request->input('items');
        $invoiceId = $request->input('invoice_id');
        $metodePembayaran = $request->input('payment_method', 'Cash on Delivery');
        $paymentStatus = Order::resolveCheckoutPaymentStatus(
            $metodePembayaran,
            $request->input('payment_status'),
        );
        $paymentChannel = strtolower((string) $request->input('payment_channel', ''));
        $paymentProvider = strtolower((string) $request->input('payment_provider', ''));
        if (! in_array($paymentChannel, ['qris', 'va', 'cod'], true)) {
            $paymentChannel = str_contains(strtolower($metodePembayaran), 'qris')
                ? 'qris'
                : (str_contains(strtolower($metodePembayaran), 'bank transfer') ? 'va' : 'cod');
        }
        if (! in_array($paymentProvider, ['midtrans', 'xendit'], true)) {
            $paymentProvider = null;
        }
        $paymentExpiresAt = null;
        if (
            $paymentStatus === Order::PAYMENT_PENDING
            && in_array($paymentChannel, ['qris', 'va', 'cod'], true)
        ) {
            $hours = max(1, min(48, (int) $request->input('payment_window_hours', Order::CANCEL_WINDOW_HOURS)));
            $paymentExpiresAt = now()->addHours($hours);
        }

        if (empty($items) || ! is_array($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Daftar pesanan kosong atau tidak valid',
            ], 400);
        }

        if (! $invoiceId) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice ID tidak valid',
            ], 400);
        }

        $invoiceId = OrderNumber::resolveCheckoutInvoiceId($invoiceId);

        $now = now();
        $createdOrders = [];
        $guestEmailFromRequest = $request->input('guest_email');
        $shippingCost = max(0, (float) $request->input('shipping_cost', 0));
        $promoDiscount = $this->checkoutPromoDiscount($items);

        if (ShippingConfig::isFreeShipping()) {
            $shippingCost = 0.0;
        } else {
            $shippingCost = $this->checkoutShippingCostFromTariffs(
                items: $items,
                recipientAddress: (string) $request->input('recipient_address', ''),
                kurirId: $request->filled('kurir_id') ? (int) $request->input('kurir_id') : null,
                weightGrams: $request->filled('shipping_weight_grams') ? (float) $request->input('shipping_weight_grams') : null,
                shippingOriginCity: $request->filled('shipping_origin_city') ? (string) $request->input('shipping_origin_city') : null,
                shippingCity: $request->filled('shipping_city') ? (string) $request->input('shipping_city') : null,
                fallbackShippingCost: $shippingCost,
            );
        }

        $recipientSeed = [
            'courier' => ShippingConfig::isFreeShipping()
                ? 'Gratis Ongkir'
                : $request->input('courier'),
            'recipient_name' => (string) $request->input('recipient_name', $user->name ?? 'Pelanggan'),
            'recipient_phone' => (string) $request->input('recipient_phone', ''),
            'recipient_address' => (string) $request->input('recipient_address', ''),
        ];

        $orderNote = $request->input('note');

        try {
            DB::transaction(function () use (
                $user,
                $items,
                $invoiceId,
                $now,
                $metodePembayaran,
                $paymentStatus,
                $paymentChannel,
                $paymentProvider,
                $paymentExpiresAt,
                $guestEmailFromRequest,
                $shippingCost,
                $promoDiscount,
                $recipientSeed,
                $orderNote,
                &$createdOrders
            ) {
                foreach ($items as $index => $item) {
                    $orderId = count($items) > 1 ? "{$invoiceId}-".($index + 1) : $invoiceId;
                    $productId = (int) ($item['product_id'] ?? 0);
                    $qty = (int) ($item['quantity'] ?? 0);

                    if ($productId <= 0 || $qty <= 0) {
                        throw new \RuntimeException('Item pesanan tidak valid.');
                    }

                    $product = Product::where('id', $productId)->lockForUpdate()->first();
                    if (! $product) {
                        throw new \RuntimeException("Produk #{$productId} tidak ditemukan.");
                    }
                    $product->decrementStock($qty);

                    // total_price = harga katalog × qty (sebelum promo)
                    // shipping + promo disimpan di baris pertama agar tidak dobel
                    $createdOrders[] = Order::create([
                        'id' => $orderId,
                        'user_id' => $user->id,
                        'guest_email' => $guestEmailFromRequest ?: $user->email,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'total_price' => $item['price'] * $qty,
                        'shipping_cost' => $index === 0 ? $shippingCost : 0,
                        'promo_discount' => $index === 0 ? $promoDiscount : 0,
                        'status' => 'menunggu_konfirmasi',
                        'metode_pembayaran' => $metodePembayaran,
                        'payment_status' => $paymentStatus,
                        'payment_channel' => $paymentChannel !== '' ? $paymentChannel : null,
                        'payment_provider' => $paymentProvider,
                        'payment_expires_at' => $paymentExpiresAt,
                        'note' => $index === 0 ? $orderNote : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                Cart::where('user_id', $user->id)->delete();

                $awaitingPay = $paymentStatus === Order::PAYMENT_PENDING
                    && in_array($paymentChannel, ['qris', 'va'], true);

                OrderTracking::ensureForInvoice($invoiceId, $createdOrders[0] ?? null, array_merge($recipientSeed, [
                    'status' => $awaitingPay ? 'Menunggu Pembayaran' : 'Menunggu Konfirmasi',
                ]));
            });

            $notifyEmail = $guestEmailFromRequest ?: $user->email;
            if ($notifyEmail && count($createdOrders) > 0) {
                $this->sendOrderPlacedMail(
                    $createdOrders[0],
                    $items,
                    $metodePembayaran,
                    (float) $createdOrders[0]->grand_total,
                    [
                        'name' => (string) $request->input('recipient_name', $user->name ?? 'Pelanggan'),
                        'phone' => (string) $request->input('recipient_phone', ''),
                        'address' => (string) $request->input('recipient_address', ''),
                        'courier' => ShippingConfig::isFreeShipping()
                            ? 'Gratis Ongkir'
                            : $request->input('courier'),
                    ],
                    $notifyEmail,
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil!',
                'data' => [
                    'order_id' => $invoiceId,
                    'payment_status' => $paymentStatus,
                    'payment_expires_at' => optional($paymentExpiresAt)?->toIso8601String(),
                    'payment_url' => in_array($paymentChannel, ['qris', 'va', 'cod'], true)
                        ? url('/pembayaran/'.$invoiceId)
                        : null,
                ],
            ], 200);

        } catch (\RuntimeException $e) {
            Log::warning('Checkout stock/validation:', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error Checkout:', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembuatan pesanan',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guest checkout (no auth). Supports buy-now and multi-item guest cart.
     */
    public function guestCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_email' => 'required|email|max:255',
            'invoice_id' => 'required|string|max:80',
            'payment_method' => 'required|string|max:80',
            'payment_status' => ['nullable', 'string', Rule::in(Order::PAYMENT_STATUSES)],
            'total' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'promo_discount' => 'nullable|numeric|min:0',
            'kurir_id' => 'nullable|integer|exists:kurirs,id',
            'shipping_origin_city' => 'nullable|string|max:120',
            'shipping_city' => 'nullable|string|max:120',
            'shipping_weight_grams' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1|max:20',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.title' => 'nullable|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:50',
            'recipient_address' => 'required|string|max:1000',
            'courier' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $items = $data['items'];
        $invoiceId = OrderNumber::resolveCheckoutInvoiceId($data['invoice_id']);
        $guestEmail = $data['guest_email'];
        $metodePembayaran = $data['payment_method'];
        $paymentStatus = Order::resolveCheckoutPaymentStatus(
            $metodePembayaran,
            $data['payment_status'] ?? null,
        );
        $paymentChannel = strtolower((string) $request->input('payment_channel', ''));
        $paymentProvider = strtolower((string) $request->input('payment_provider', ''));
        if (! in_array($paymentChannel, ['qris', 'va', 'cod'], true)) {
            $paymentChannel = str_contains(strtolower($metodePembayaran), 'qris')
                ? 'qris'
                : (str_contains(strtolower($metodePembayaran), 'bank transfer') ? 'va' : 'cod');
        }
        if (! in_array($paymentProvider, ['midtrans', 'xendit'], true)) {
            $paymentProvider = null;
        }
        $paymentExpiresAt = null;
        if (
            $paymentStatus === Order::PAYMENT_PENDING
            && in_array($paymentChannel, ['qris', 'va', 'cod'], true)
        ) {
            $hours = max(1, min(48, (int) $request->input('payment_window_hours', Order::CANCEL_WINDOW_HOURS)));
            $paymentExpiresAt = now()->addHours($hours);
        }
        $shippingCost = max(0, (float) ($data['shipping_cost'] ?? 0));
        $promoDiscount = $this->checkoutPromoDiscount($items);
        $now = now();

        if (ShippingConfig::isFreeShipping()) {
            $shippingCost = 0.0;
        } else {
            $shippingCost = $this->checkoutShippingCostFromTariffs(
                items: $items,
                recipientAddress: (string) ($data['recipient_address'] ?? ''),
                kurirId: array_key_exists('kurir_id', $data) && $data['kurir_id'] !== null ? (int) $data['kurir_id'] : null,
                weightGrams: array_key_exists('shipping_weight_grams', $data) && $data['shipping_weight_grams'] !== null
                    ? (float) $data['shipping_weight_grams']
                    : null,
                shippingOriginCity: array_key_exists('shipping_origin_city', $data) && $data['shipping_origin_city'] !== null
                    ? (string) $data['shipping_origin_city']
                    : null,
                shippingCity: array_key_exists('shipping_city', $data) && $data['shipping_city'] !== null ? (string) $data['shipping_city'] : null,
                fallbackShippingCost: $shippingCost,
            );
        }

        try {
            $createdOrders = [];

            DB::transaction(function () use (
                $data,
                $items,
                $invoiceId,
                $guestEmail,
                $metodePembayaran,
                $paymentStatus,
                $paymentChannel,
                $paymentProvider,
                $paymentExpiresAt,
                $shippingCost,
                $promoDiscount,
                $now,
                &$createdOrders
            ) {
                foreach ($items as $index => $item) {
                    $orderId = count($items) > 1 ? "{$invoiceId}-".($index + 1) : $invoiceId;
                    $productId = (int) $item['product_id'];
                    $qty = (int) $item['quantity'];

                    $product = Product::where('id', $productId)->lockForUpdate()->first();
                    if (! $product) {
                        throw new \RuntimeException("Produk #{$productId} tidak ditemukan.");
                    }
                    $product->decrementStock($qty);

                    $createdOrders[] = Order::create([
                        'id' => $orderId,
                        'user_id' => null,
                        'guest_email' => $guestEmail,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'total_price' => $item['price'] * $qty,
                        'shipping_cost' => $index === 0 ? $shippingCost : 0,
                        'promo_discount' => $index === 0 ? $promoDiscount : 0,
                        'status' => 'menunggu_konfirmasi',
                        'metode_pembayaran' => $metodePembayaran,
                        'payment_status' => $paymentStatus,
                        'payment_channel' => $paymentChannel !== '' ? $paymentChannel : null,
                        'payment_provider' => $paymentProvider,
                        'payment_expires_at' => $paymentExpiresAt,
                        'note' => $index === 0 ? ($data['note'] ?? null) : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $awaitingPay = $paymentStatus === Order::PAYMENT_PENDING
                    && in_array($paymentChannel, ['qris', 'va'], true);

                OrderTracking::ensureForInvoice($invoiceId, $createdOrders[0] ?? null, [
                    'status' => $awaitingPay ? 'Menunggu Pembayaran' : 'Menunggu Konfirmasi',
                    'courier' => ShippingConfig::isFreeShipping() ? 'Gratis Ongkir' : ($data['courier'] ?? null),
                    'recipient_name' => $data['recipient_name'],
                    'recipient_phone' => $data['recipient_phone'],
                    'recipient_address' => $data['recipient_address'],
                ]);
            });

            $mailItems = [];
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $mailItems[] = [
                    'product_id' => (int) $item['product_id'],
                    'title' => $item['title'] ?? ($product?->title ?? 'Produk Evomi'),
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ];
            }

            $this->sendOrderPlacedMail(
                $createdOrders[0],
                $mailItems,
                $metodePembayaran,
                    (float) $createdOrders[0]->grand_total,
                [
                    'name' => $data['recipient_name'],
                    'phone' => $data['recipient_phone'],
                    'address' => $data['recipient_address'],
                    'courier' => ShippingConfig::isFreeShipping() ? 'Gratis Ongkir' : ($data['courier'] ?? null),
                ],
                $guestEmail,
            );

            $frontend = rtrim(
                (string) (env('FRONTEND_URL') ?: env('APP_FRONTEND_URL') ?: env('APP_URL', 'http://localhost:3000')),
                '/'
            );

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil!',
                'data' => [
                    'order_id' => $invoiceId,
                    'payment_status' => $paymentStatus,
                    'payment_expires_at' => optional($paymentExpiresAt)?->toIso8601String(),
                    'payment_url' => in_array($paymentChannel, ['qris', 'va', 'cod'], true)
                        ? url('/pembayaran/'.$invoiceId)
                        : null,
                    'tracking_url_hint' => $frontend.'/pengiriman/'.$invoiceId,
                ],
            ], 200);
        } catch (\RuntimeException $e) {
            Log::warning('Guest checkout stock/validation:', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error Guest Checkout:', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembuatan pesanan',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  list<array{product_id?: mixed, title?: string, quantity: mixed, price: mixed}>  $items
     * @param  array{name: string, phone: string, address: string, courier?: string|null}  $recipient
     */
    private function sendOrderPlacedMail(
        Order $order,
        array $items,
        string $paymentMethod,
        float|int $total,
        array $recipient,
        string $email,
    ): void {
        try {
            $mailItems = array_map(function ($item) {
                $product = null;
                if (! empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                }

                $title = $item['title'] ?? null;
                if (! $title && $product) {
                    $title = $product->title;
                }

                $imageUrl = null;
                $imageLocalPath = null;
                $imagePath = $product?->image_1 ?: $product?->image_produk_belanja;
                if ($imagePath) {
                    if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                        $imageUrl = $imagePath;
                    } else {
                        $relative = ltrim($imagePath, '/');
                        $local = storage_path('app/public/'.$relative);
                        $imageUrl = rtrim((string) env('APP_URL', 'http://localhost:8000'), '/')
                            .'/storage/'
                            .$relative;

                        if (is_file($local)) {
                            $thumb = $this->makeEmailProductThumb($local, (int) ($product->id ?? 0));
                            $imageLocalPath = $thumb ?: $local;
                        }
                    }
                }

                return [
                    'title' => $title ?: 'Produk Evomi',
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                    'image_url' => $imageUrl,
                    'image_path' => $imageLocalPath,
                    'color' => is_string($product?->color) && trim($product->color) !== ''
                        ? trim($product->color)
                        : null,
                ];
            }, $items);

            Mail::to($email)->send(new OrderPlacedMail(
                $order,
                $recipient,
                $mailItems,
                $paymentMethod,
                $total,
            ));
        } catch (\Throwable $e) {
            Log::error('Order confirmation email failed', [
                'order_id' => $order->id,
                'email' => $email,
                'detail' => $e->getMessage(),
            ]);
        }
    }

    public function confirmReceipt($id, Request $request)
    {
        $user = $request->user();
        $order = Order::where('user_id', $user->id)->where('id', $id)->firstOrFail();

        $updated = Order::where('user_id', $user->id)
            ->where('created_at', $order->created_at)
            ->update(['status' => 'diterima']);

        $order->refresh();
        $this->syncTrackingFromOrderStatus($order);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan telah dikonfirmasi diterima.',
            'updated_count' => $updated,
        ]);
    }

    public function destroy($id, Request $request)
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json(['message' => 'Riwayat pesanan tidak ditemukan'], 404);
        }

        if (! auth()->user()?->is_admin && $order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Anda tidak diizinkan menghapus pesanan ini.'], 403);
        }

        // Delete only this order row (do not cascade by shared created_at batch).
        $orderId = (string) $order->id;
        $order->delete();
        OrderTracking::deleteIfOrderGone($orderId);

        return response()->json([
            'message' => 'Riwayat pesanan berhasil dihapus',
            'deleted_count' => 1,
        ], 200);
    }

    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['menunggu_konfirmasi', 'pengemasan', 'dalam_perjalanan', 'diterima', 'dibatalkan', 'selesai']),
            ],
            'metode_pembayaran' => 'sometimes|string|nullable',
            'payment_status' => ['sometimes', 'string', Rule::in(Order::PAYMENT_STATUSES)],
        ]);

        $order = Order::find($id);

        if (! $order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if (! auth()->user()?->is_admin) {
            return response()->json(['message' => 'Anda tidak diizinkan memperbarui pesanan ini.'], 403);
        }

        $previousPayment = $order->payment_status;
        $order->status = $request->status;

        if ($request->has('metode_pembayaran')) {
            $order->metode_pembayaran = $request->metode_pembayaran;
        }

        if ($request->filled('payment_status')) {
            $order->payment_status = $request->string('payment_status')->toString();
        } elseif ($request->status === 'dibatalkan') {
            $order->payment_status = Order::PAYMENT_CANCELLED;
        }
        // COD stays pending until admin explicitly marks payment success.
        // Do not auto-mark paid when packing / shipping.

        $order->save();

        $this->syncTrackingFromOrderStatus($order);

        if (
            $previousPayment === Order::PAYMENT_PENDING
            && $order->payment_status === Order::PAYMENT_SUCCESS
        ) {
            $this->appendPaymentConfirmedTimeline($order);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data pesanan berhasil diperbarui.',
            'data' => $order,
        ], 200);
    }

    /**
     * Keep OrderTracking in sync when admin updates fulfillment status from dashboard.
     */
    private function syncTrackingFromOrderStatus(Order $order): void
    {
        $invoiceRoot = Order::invoiceRoot((string) $order->id);
        $tracking = OrderTracking::ensureForInvoice($invoiceRoot, $order);

        $label = Order::trackingStatusLabel($order->status);
        $tracking->status = $label;

        $timeline = collect($tracking->timeline ?? [])->values()->all();
        $last = $timeline[0]['status'] ?? null;
        if ($last !== $label) {
            array_unshift($timeline, [
                'status' => $label,
                'date' => now()->toIso8601String(),
                'time' => now()->toIso8601String(),
                'description' => 'Diperbarui dari dashboard Evomi',
            ]);
            $tracking->timeline = array_slice($timeline, 0, 20);
        }

        $tracking->save();
    }

    private function appendPaymentConfirmedTimeline(Order $order): void
    {
        $invoiceRoot = Order::invoiceRoot((string) $order->id);
        $tracking = OrderTracking::where('order_id', $invoiceRoot)->first();
        if (! $tracking) {
            return;
        }

        $label = $order->isCodPayment()
            ? 'Pembayaran COD dikonfirmasi'
            : 'Pembayaran berhasil';
        $timeline = collect($tracking->timeline ?? [])->values()->all();
        $last = $timeline[0]['status'] ?? null;
        if ($last === $label) {
            return;
        }

        array_unshift($timeline, [
            'status' => $label,
            'date' => now()->toIso8601String(),
            'time' => now()->toIso8601String(),
            'description' => 'Dikonfirmasi dari dashboard Evomi',
        ]);
        $tracking->timeline = array_slice($timeline, 0, 20);
        $tracking->save();
    }

    /**
     * Satu potongan per checkout (keranjang), dihitung dari harga katalog × qty.
     */
    private function checkoutPromoDiscount(array $items): float
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $price = (float) (Product::query()->where('id', $productId)->value('price') ?? 0);
            $subtotal += $price * $qty;
        }

        return Promo::discountForSubtotal($subtotal);
    }

    private function inferShippingCity(?string $address): ?string
    {
        $s = mb_strtolower((string) $address);
        if (trim($s) === '') return null;

        $match = [
            'Jakarta' => ['jakarta', 'jakbar', 'jaksel', 'jakpus', 'jaktim', 'jakut'],
            'Bogor' => ['bogor'],
            'Depok' => ['depok'],
            'Tangerang' => ['tangerang', 'tangerang selatan', 'tangerang sel', 'cisauk', 'serpong'],
            'Bekasi' => ['bekasi'],
            'Bandung' => ['bandung'],
            'Surabaya' => ['surabaya'],
            'Yogyakarta' => ['yogyakarta', 'jogja'],
            'Semarang' => ['semarang'],
            'Medan' => ['medan'],
            'Makassar' => ['makassar'],
        ];

        foreach ($match as $city => $needles) {
            foreach ($needles as $n) {
                if ($n !== '' && mb_strpos($s, $n) !== false) {
                    return $city;
                }
            }
        }

        return null;
    }

    private function checkoutShippingCostFromTariffs(
        array $items,
        string $recipientAddress,
        ?int $kurirId,
        ?float $weightGrams,
        ?string $shippingOriginCity,
        ?string $shippingCity,
        float $fallbackShippingCost,
    ): float {
        if (! $kurirId || ! is_int($kurirId)) {
            return $fallbackShippingCost;
        }

        $originCity = $shippingOriginCity;
        if (! is_string($originCity) || trim($originCity) === '') {
            $originCity = ShippingConfig::DEFAULT_ORIGIN_CITY;
        }
        $originCity = is_string($originCity) ? trim($originCity) : ShippingConfig::DEFAULT_ORIGIN_CITY;

        $city = $shippingCity;
        if (! is_string($city) || trim($city) === '') {
            $city = $this->inferShippingCity($recipientAddress);
        }
        $city = is_string($city) ? trim($city) : '';

        $weight = $weightGrams;
        if (! is_numeric($weight) || ! is_finite((float) $weight) || (float) $weight <= 0) {
            $weight = 0.0;
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 0);
                if ($qty > 0) {
                    $weight += $qty * self::UNIT_WEIGHT_GRAMS;
                }
            }
        }

        $weight = (float) max(0, $weight);
        if ($originCity === '' || $city === '' || $weight <= 0) {
            return $fallbackShippingCost;
        }

        $tarif = KurirTarif::query()
            ->where('kurir_id', $kurirId)
            ->where('kota_asal', $originCity)
            ->where('kota_tujuan', $city)
            ->where('is_active', true)
            ->where('berat_min_gram', '<=', $weight)
            ->where('berat_max_gram', '>=', $weight)
            ->orderByDesc('berat_min_gram')
            ->first();

        if (! $tarif) {
            return $fallbackShippingCost;
        }

        return (float) $tarif->harga;
    }

    /**
     * Create a small JPEG thumbnail for embedding in order emails.
     */
    private function makeEmailProductThumb(string $sourcePath, int $productId): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $raw = @file_get_contents($sourcePath);
            if ($raw === false) {
                return null;
            }
            $src = @imagecreatefromstring($raw);
            if (! $src) {
                return null;
            }

            $sw = imagesx($src);
            $sh = imagesy($src);
            if ($sw < 1 || $sh < 1) {
                imagedestroy($src);

                return null;
            }

            $max = 200;
            $scale = min($max / $sw, $max / $sh, 1);
            $tw = max(1, (int) round($sw * $scale));
            $th = max(1, (int) round($sh * $scale));

            $dst = imagecreatetruecolor($tw, $th);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);

            $dir = storage_path('app/email-thumbs');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $out = $dir.'/product-'.($productId ?: md5($sourcePath)).'.jpg';
            imagejpeg($dst, $out, 78);
            imagedestroy($src);
            imagedestroy($dst);

            return is_file($out) ? $out : null;
        } catch (\Throwable $e) {
            Log::warning('Failed creating email product thumb', [
                'path' => $sourcePath,
                'detail' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
