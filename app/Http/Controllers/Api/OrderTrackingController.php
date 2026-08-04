<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderTrackingController extends Controller
{
    /**
     * 1. READ (All): Mengambil daftar semua pelacakan pesanan
     */
    public function index()
    {
        $trackings = OrderTracking::latest()->get();

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

        $tracking = OrderTracking::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil dibuat.',
            'data' => $tracking
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

        $timeline = collect($tracking->timeline ?? [])->map(function ($item) {
            $rawTime = $item['time'] ?? $item['date'] ?? null;

            return [
                'status' => $item['status'] ?? '',
                'time' => $rawTime,
                'description' => $item['description'] ?? null,
            ];
        })->values()->all();

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
                'timeline' => $timeline,
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

        // Lakukan update data
        $tracking->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data pelacakan berhasil diperbarui.',
            'data' => $tracking
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