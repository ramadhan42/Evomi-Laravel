<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoController extends Controller
{
    /**
     * Public: ?active=1 → hanya promo berlaku hari ini.
     * Admin list: semua promo (tanpa filter).
     */
    public function index(Request $request)
    {
        $query = Promo::query()->orderByDesc('id');

        if ($request->boolean('active')) {
            $query->active();
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ], 200);
    }

    public function show($id)
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $promo], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'harga_promo' => 'required|numeric|min:0',
            'persentase_promo' => 'nullable|numeric|min:0|max:100',
            'tanggal_berlaku_promo' => 'required|date',
            'tanggal_berakhir_promo' => 'required|date|after_or_equal:tanggal_berlaku_promo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $promo = Promo::create([
            'harga_promo' => $request->input('harga_promo'),
            'persentase_promo' => $request->input('persentase_promo'),
            'tanggal_berlaku_promo' => $request->input('tanggal_berlaku_promo'),
            'tanggal_berakhir_promo' => $request->input('tanggal_berakhir_promo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil ditambahkan',
            'data' => $promo,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'harga_promo' => 'sometimes|required|numeric|min:0',
            'persentase_promo' => 'nullable|numeric|min:0|max:100',
            'tanggal_berlaku_promo' => 'sometimes|required|date',
            'tanggal_berakhir_promo' => 'sometimes|required|date|after_or_equal:tanggal_berlaku_promo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $promo->update($request->only([
            'harga_promo',
            'persentase_promo',
            'tanggal_berlaku_promo',
            'tanggal_berakhir_promo',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diupdate',
            'data' => $promo->fresh(),
        ], 200);
    }

    public function destroy($id)
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dihapus',
        ], 200);
    }
}
