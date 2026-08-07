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
     * 1. READ (All): Mengambil daftar semua pelacakan pesanan
     */
    public function index()
    {
        $trackings = OrderTracking::latest()->get()->map(function (OrderTracking $tracking) {
            $row = $tracking->toArray();
            $row['timeline'] = $this->normalizeTimeline($tracking->timeline);
            $row['product'] = $this->resolveOrderProduct((string) $tracking->order_id);

            return $row;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua pelacakan pesanan berhasil diambil.',
            'data' => $trackings
        ], 200);
    }

    /**
     * 2. CREATE: Menyimpan data pelacakan baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|unique:order_trackings,order_id',
            'tracking_number' => 'nullable|string',
            'status' => 'required|string',
            'estimated_delivery' => 'nullable|date',
            'courier' => 'nullable|string',
            'recipient_name' => 'required|string',
            'recipient_phone' => 'required|string',
            'recipient_address' => 'required|string',
            'timeline' => 'nullable|array' // Pastikan timeline diterima sebagai array
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
        if (array_key_exists('timeline', $payload)) {
            $payload['timeline'] = $this->normalizeTimeline($payload['timeline']);
        }

        $tracking = OrderTracking::create($payload);
        $data = $tracking->toArray();
        $data['timeline'] = $this->normalizeTimeline($tracking->timeline);

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil dibuat.',
            'data' => $data
        ], 201);
    }

    /**
     * 3. READ (Detail): Ambil detail pelacakan berdasarkan nomor resi
     */
    public function show($resi)
    {
        $resi = trim(urldecode((string) $resi));

        if ($resi === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor resi wajib diisi untuk melacak pesanan.',
            ], 422);
        }

        $tracking = OrderTracking::whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '')
            ->where('tracking_number', $resi)
            ->first();

        if (!$tracking) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor resi tidak ditemukan. Pastikan resi sudah diinput admin, atau coba lagi nanti.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'orderId' => $tracking->order_id,
                'resi' => $tracking->tracking_number,
                'courier' => $tracking->courier ?: 'Belum ditentukan',
                'estimatedDelivery' => $tracking->estimated_delivery
                    ? $tracking->estimated_delivery->translatedFormat('d F Y')
                    : 'Belum ada estimasi',
                'estimatedDeliveryRaw' => $tracking->estimated_delivery
                    ? $tracking->estimated_delivery->toDateString()
                    : null,
                'currentStatus' => $tracking->status ?: 'Menunggu pengiriman',
                'hasShipped' => !empty($tracking->tracking_number),
                'recipient' => [
                    'name' => $tracking->recipient_name,
                    'phone' => $tracking->recipient_phone,
                    'address' => $tracking->recipient_address,
                ],
                'timeline' => $this->normalizeTimeline($tracking->timeline),
            ]
        ], 200);
    }

    /**
     * 4. UPDATE: Memperbarui data pelacakan dan menambahkan riwayat ke timeline
     */
    public function update(Request $request, $orderId)
    {
        $tracking = OrderTracking::where('order_id', $orderId)->first();

        if (!$tracking) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelacakan tidak ditemukan.'
            ], 404);
        }

        // Validasi data yang diupdate (order_id di-ignore unique-nya untuk data ini sendiri)
        $validator = Validator::make($request->all(), [
            'order_id' => 'sometimes|required|string|unique:order_trackings,order_id,' . $tracking->id,
            'tracking_number' => 'nullable|string',
            'status' => 'sometimes|required|string',
            'estimated_delivery' => 'nullable|date',
            'courier' => 'nullable|string',
            'recipient_name' => 'sometimes|required|string',
            'recipient_phone' => 'sometimes|required|string',
            'recipient_address' => 'sometimes|required|string',
            'timeline' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $request->all();
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
            'data' => $data
        ], 200);
    }

    /**
     * 5. DELETE: Menghapus data pelacakan
     */
    public function destroy($orderId)
    {
        $tracking = OrderTracking::where('order_id', $orderId)->first();

        if (!$tracking) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelacakan tidak ditemukan.'
            ], 404);
        }

        $tracking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil dihapus.'
        ], 200);
    }
}