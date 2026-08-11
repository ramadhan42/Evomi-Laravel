<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderTrackingController extends Controller
{
    /**
     * Normalize timeline entries so clients always receive `time` (accepts legacy `date`).
     *
     * @param  mixed  $timeline
     * @return array<int, array{status: mixed, time: mixed, description: mixed}>
     */
    private function normalizeTimeline($timeline): array
    {
        return collect($timeline ?? [])->map(function ($item) {
            $item = is_array($item) ? $item : (array) $item;

            return [
                'status' => $item['status'] ?? '',
                'time' => $item['time'] ?? $item['date'] ?? null,
                'description' => $item['description'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * Resolve product for a tracking row (order_id may be invoice or line item id).
     */
    private function resolveOrderProduct(string $orderId): ?array
    {
        $order = Order::with('product')
            ->where('id', $orderId)
            ->first();

        if (! $order) {
            $order = Order::with('product')
                ->where('id', 'like', $orderId.'-%')
                ->orderBy('id')
                ->first();
        }

        $product = $order?->product;
        if (! $product) {
            return null;
        }

        return [
            'id' => $product->id,
            'title' => $product->title,
            'image_1' => $product->image_1,
            'image_2' => $product->image_2 ?? null,
            'gambar_1' => $product->gambar_1 ?? null,
            'gambar_2' => $product->gambar_2 ?? null,
            'image_produk_belanja' => $product->image_produk_belanja ?? null,
        ];
    }

    /**
     * Storefront: daftar pesanan + tracking milik user login (untuk sidebar Lacak Pesanan).
     */
    public function mine(Request $request)
    {
        $user = $request->user();
        $locale = \App\Support\LocaleResolver::normalize($request->query('locale', 'id'));

        $orders = Order::with('product')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('payment_status', Order::PAYMENT_SUCCESS)
                    ->orWhereNotIn('status', ['dibatalkan']);
            })
            ->orderByDesc('created_at')
            ->get();

        $groups = [];
        foreach ($orders as $order) {
            $idStr = (string) $order->id;
            $invoiceRoot = preg_match('/^(INV-\d+-\d+)(?:-\d+)?$/', $idStr, $m) ? $m[1] : $idStr;
            if (! isset($groups[$invoiceRoot])) {
                $groups[$invoiceRoot] = [];
            }
            $groups[$invoiceRoot][] = $order;
        }

        $data = [];
        foreach ($groups as $invoiceRoot => $lines) {
            /** @var Order $first */
            $first = $lines[0];
            $tracking = OrderTracking::where('order_id', $invoiceRoot)->first();
            if (! $tracking) {
                $tracking = OrderTracking::where('order_id', (string) $first->id)->first();
            }

            $product = $first->product;
            $localized = $product
                ? \App\Support\ProductLocalizer::localize($product, $locale)
                : null;

            $statusKey = strtolower((string) ($first->status ?: ''));
            $trackStatus = (string) ($tracking?->status ?: '');

            $data[] = [
                'id' => $invoiceRoot,
                'order_id' => (string) $first->id,
                'invoice' => $invoiceRoot,
                'code' => $this->shortOrderCode($invoiceRoot),
                'title' => $localized['title'] ?? ($product?->title ?? 'Produk Evomi'),
                'image' => $product?->image_2
                    ?: $product?->image_1
                    ?: $product?->image_produk_belanja,
                'accent' => $product?->color ?: '#1172BA',
                'personality_type' => $product?->personality_type,
                'status' => $statusKey,
                'status_label' => $this->storefrontStatusLabel($statusKey, $trackStatus),
                'status_tone' => $this->storefrontStatusTone($statusKey),
                'progress_step' => $this->storefrontProgressStep($statusKey),
                'courier' => $tracking?->courier ?: null,
                'tracking_number' => $tracking?->tracking_number ?: null,
                'estimated_delivery' => $tracking?->estimated_delivery
                    ? $tracking->estimated_delivery->translatedFormat('d F Y')
                    : null,
                'destination' => $this->shortDestination($tracking?->recipient_address),
                'recipient' => [
                    'name' => $tracking?->recipient_name,
                    'phone' => $tracking?->recipient_phone,
                    'address' => $tracking?->recipient_address,
                ],
                'timeline' => $this->normalizeTimeline($tracking?->timeline),
                'created_at' => optional($first->created_at)?->toIso8601String(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function shortOrderCode(string $invoiceRoot): string
    {
        if (preg_match('/(\d{6,})$/', preg_replace('/\D+/', '', $invoiceRoot) ?: '', $m)) {
            return 'EVN-'.substr($m[1], -6);
        }

        return 'EVN-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $invoiceRoot) ?: '000000', -6));
    }

    private function shortDestination(?string $address): string
    {
        $address = trim((string) $address);
        if ($address === '') {
            return '—';
        }
        $parts = preg_split('/\s*,\s*/', $address) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));
        if (count($parts) >= 2) {
            return $parts[count($parts) - 2] ?: $parts[count($parts) - 1];
        }

        return \Illuminate\Support\Str::limit($address, 28, '…');
    }

    private function storefrontStatusLabel(string $statusKey, string $trackStatus): string
    {
        return match ($statusKey) {
            'diterima', 'selesai' => 'Terkirim',
            'dalam_perjalanan' => 'Dalam Perjalanan',
            'pengemasan' => 'Dikemas',
            'menunggu_konfirmasi' => 'Diproses',
            'dibatalkan' => 'Dibatalkan',
            default => $trackStatus !== '' ? $trackStatus : 'Diproses',
        };
    }

    private function storefrontStatusTone(string $statusKey): string
    {
        return match ($statusKey) {
            'diterima', 'selesai' => 'success',
            'dalam_perjalanan' => 'info',
            'pengemasan', 'menunggu_konfirmasi' => 'warning',
            'dibatalkan' => 'danger',
            default => 'muted',
        };
    }

    /**
     * 0 = Diterima, 1 = Dikemas, 2 = Dikirim, 3 = Transit, 4 = Terkirim
     */
    private function storefrontProgressStep(string $statusKey): int
    {
        return match ($statusKey) {
            'menunggu_konfirmasi' => 0,
            'pengemasan' => 1,
            'dalam_perjalanan' => 3,
            'diterima', 'selesai' => 4,
            default => 0,
        };
    }

    /**
     * 1. READ (All): Daftar pelacakan disinkron dari data pesanan (invoice).
     * Orphan tracking (pesanan sudah dihapus) dibersihkan otomatis.
     */
    public function index()
    {
        OrderTracking::purgeOrphans();

        $orders = Order::with(['product', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $groups = [];
        foreach ($orders as $order) {
            $invoiceRoot = Order::invoiceRoot((string) $order->id);
            if (! isset($groups[$invoiceRoot])) {
                $groups[$invoiceRoot] = [];
            }
            $groups[$invoiceRoot][] = $order;
        }

        $trackings = collect($groups)->map(function (array $lines, string $invoiceRoot) {
            /** @var Order $first */
            $first = $lines[0];
            $tracking = OrderTracking::ensureForInvoice($invoiceRoot, $first);

            // Keep editable tracking status aligned with current order fulfillment status.
            $orderLabel = Order::trackingStatusLabel($first->status);
            if (
                $first->payment_status === Order::PAYMENT_PENDING
                && in_array((string) $first->payment_channel, ['qris', 'va'], true)
            ) {
                $orderLabel = 'Menunggu Pembayaran';
            }
            if ((string) $tracking->status !== $orderLabel && in_array((string) $tracking->status, [
                '',
                'Diproses',
                'Menunggu Konfirmasi',
                'Menunggu Pembayaran',
                'Pesanan Diterima',
                'Dikemas',
                'Dalam Perjalanan',
                'Terkirim',
                'Dibatalkan',
            ], true)) {
                $tracking->status = $orderLabel;
                $tracking->save();
            }

            $row = $tracking->toArray();
            $row['order_id'] = $invoiceRoot;
            $row['timeline'] = $this->normalizeTimeline($tracking->timeline);
            $row['product'] = $this->resolveOrderProduct($invoiceRoot);
            $row['order_status'] = $first->status;
            $row['payment_status'] = $first->payment_status;
            $row['guest_email'] = $first->guest_email;
            $row['customer_name'] = $first->user?->name;
            $row['created_at'] = optional($first->created_at)?->toIso8601String();

            return $row;
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua pelacakan pesanan berhasil diambil.',
            'data' => $trackings,
        ], 200);
    }

    /**
     * 2. CREATE: Menyimpan data pelacakan baru (atau update jika invoice sudah ada).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'tracking_number' => 'nullable|string',
            'status' => 'required|string',
            'estimated_delivery' => 'nullable|date',
            'courier' => 'nullable|string',
            'recipient_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'recipient_address' => 'required|string',
            'timeline' => 'nullable|array', // Pastikan timeline diterima sebagai array
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoiceRoot = Order::invoiceRoot((string) $request->input('order_id'));
        if (! Order::existsForInvoice($invoiceRoot)) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan. Data pelacakan harus mengikuti data pesanan.',
            ], 404);
        }

        $payload = $request->all();
        $payload['order_id'] = $invoiceRoot;
        if (array_key_exists('timeline', $payload)) {
            $payload['timeline'] = $this->normalizeTimeline($payload['timeline']);
        }

        $tracking = OrderTracking::where('order_id', $invoiceRoot)->first();
        if ($tracking) {
            $tracking->fill(collect($payload)->except(['order_id'])->all());
            if (! empty($payload['timeline'])) {
                $tracking->timeline = $payload['timeline'];
            }
            $tracking->save();
        } else {
            $tracking = OrderTracking::create($payload);
        }

        $data = $tracking->toArray();
        $data['timeline'] = $this->normalizeTimeline($tracking->timeline);

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil dibuat.',
            'data' => $data,
        ], 201);
    }

    /**
     * 3. READ (Detail): Ambil detail pelacakan berdasarkan nomor resi atau nomor pesanan.
     */
    public function show($resi)
    {
        $resi = trim(urldecode((string) $resi));

        if ($resi === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor resi / nomor pesanan wajib diisi untuk melacak pesanan.',
            ], 422);
        }

        $tracking = OrderTracking::findByResiOrOrder($resi);

        if (! $tracking) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor resi / nomor pesanan tidak ditemukan. Periksa kembali, atau coba lagi nanti.',
            ], 404);
        }

        $invoiceRoot = Order::invoiceRoot((string) $tracking->order_id);
        $order = Order::with('product')
            ->where(function ($q) use ($invoiceRoot) {
                $q->where('id', $invoiceRoot)
                    ->orWhere('id', 'like', $invoiceRoot.'-%');
            })
            ->orderBy('id')
            ->first();

        $product = $order?->product;
        $statusKey = strtolower((string) ($order?->status ?: ''));
        $trackStatus = (string) ($tracking->status ?: '');
        $displayCode = $tracking->tracking_number ?: $tracking->order_id;

        return response()->json([
            'success' => true,
            'data' => [
                'orderId' => $tracking->order_id,
                'resi' => $displayCode,
                'trackingNumber' => $tracking->tracking_number ?: null,
                'courier' => $tracking->courier ?: 'Belum ditentukan',
                'estimatedDelivery' => $tracking->estimated_delivery
                    ? $tracking->estimated_delivery->translatedFormat('d F Y')
                    : 'Belum ada estimasi',
                'estimatedDeliveryRaw' => $tracking->estimated_delivery
                    ? $tracking->estimated_delivery->toDateString()
                    : null,
                'currentStatus' => $tracking->status ?: 'Menunggu pengiriman',
                'hasShipped' => ! empty($tracking->tracking_number),
                'recipient' => [
                    'name' => $tracking->recipient_name,
                    'phone' => $tracking->recipient_phone,
                    'address' => $tracking->recipient_address,
                ],
                'timeline' => $this->normalizeTimeline($tracking->timeline),
                // Storefront sidebar shape (guest + logged-in parity)
                'id' => $invoiceRoot,
                'order_id' => (string) ($order?->id ?: $tracking->order_id),
                'invoice' => $invoiceRoot,
                'code' => $this->shortOrderCode($invoiceRoot),
                'title' => $product?->title ?: 'Produk Evomi',
                'image' => $product?->image_2
                    ?: $product?->image_1
                    ?: $product?->image_produk_belanja,
                'accent' => $product?->color ?: '#1172BA',
                'personality_type' => $product?->personality_type,
                'status' => $statusKey,
                'status_label' => $this->storefrontStatusLabel($statusKey, $trackStatus),
                'status_tone' => $this->storefrontStatusTone($statusKey),
                'progress_step' => $this->storefrontProgressStep($statusKey),
                'tracking_number' => $tracking->tracking_number ?: null,
                'estimated_delivery' => $tracking->estimated_delivery
                    ? $tracking->estimated_delivery->translatedFormat('d F Y')
                    : null,
                'destination' => $this->shortDestination($tracking->recipient_address),
                'created_at' => optional($order?->created_at ?? $tracking->created_at)?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * 4. UPDATE: Memperbarui data pelacakan dan menambahkan riwayat ke timeline
     */
    public function update(Request $request, $orderId)
    {
        $invoiceRoot = Order::invoiceRoot((string) $orderId);

        if (! Order::existsForInvoice($invoiceRoot)) {
            OrderTracking::deleteIfOrderGone($invoiceRoot);

            return response()->json([
                'success' => false,
                'message' => 'Pesanan sudah tidak ada. Data pelacakan tidak dapat diedit.',
            ], 404);
        }

        $tracking = OrderTracking::ensureForInvoice($invoiceRoot);

        // Validasi data yang diupdate (order_id di-ignore unique-nya untuk data ini sendiri)
        $validator = Validator::make($request->all(), [
            'order_id' => 'sometimes|required|string|unique:order_trackings,order_id,'.$tracking->id,
            'tracking_number' => 'nullable|string',
            'status' => 'sometimes|required|string',
            'estimated_delivery' => 'nullable|date',
            'courier' => 'nullable|string',
            'recipient_name' => 'sometimes|required|string',
            'recipient_phone' => 'sometimes|required|string',
            'recipient_address' => 'sometimes|required|string',
            'timeline' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $request->except(['order_id']);
        if (array_key_exists('timeline', $payload)) {
            $payload['timeline'] = $this->normalizeTimeline($payload['timeline']);
        }

        $tracking->update($payload);
        $tracking->refresh();

        $data = $tracking->toArray();
        $data['timeline'] = $this->normalizeTimeline($tracking->timeline);

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil diperbarui.',
            'data' => $data,
        ], 200);
    }

    /**
     * 5. DELETE: Menghapus data pelacakan
     */
    public function destroy($orderId)
    {
        $invoiceRoot = Order::invoiceRoot((string) $orderId);
        $tracking = OrderTracking::where('order_id', $invoiceRoot)->first()
            ?: OrderTracking::where('order_id', $orderId)->first();

        if (! $tracking) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelacakan tidak ditemukan.',
            ], 404);
        }

        $tracking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil dihapus.',
        ], 200);
    }
}