<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kurir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KurirController extends Controller
{
    /**
     * Public & admin: daftar kurir.
     * Query ?all=1 (admin) → termasuk nonaktif.
     */
    public function index(Request $request)
    {
        $query = Kurir::query()->orderBy('nama')->orderBy('jenis');

        $includeInactive = $request->boolean('all') && $request->user()?->is_admin;
        if (!$includeInactive) {
            $query->active();
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ], 200);
    }

    public function show($id)
    {
        $kurir = Kurir::find($id);

        if (!$kurir) {
            return response()->json(['success' => false, 'message' => 'Kurir tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $kurir], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'jenis' => 'required|string|max:100',
            'harga' => 'required|numeric|min:0',
            'destinasi' => 'required|string|max:255',
            'estimasi_hari' => 'nullable|integer|min:1|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $payload = $request->only(['nama', 'jenis', 'harga', 'destinasi', 'estimasi_hari', 'is_active']);
        if (!isset($payload['estimasi_hari'])) {
            $payload['estimasi_hari'] = 3;
        }
        if (!isset($payload['is_active'])) {
            $payload['is_active'] = true;
        }

        $kurir = Kurir::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Kurir berhasil ditambahkan',
            'data' => $kurir,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $kurir = Kurir::find($id);

        if (!$kurir) {
            return response()->json(['success' => false, 'message' => 'Kurir tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'jenis' => 'sometimes|required|string|max:100',
            'harga' => 'sometimes|required|numeric|min:0',
            'destinasi' => 'sometimes|required|string|max:255',
            'estimasi_hari' => 'nullable|integer|min:1|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kurir->update($request->only([
            'nama',
            'jenis',
            'harga',
            'destinasi',
            'estimasi_hari',
            'is_active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Kurir berhasil diupdate',
            'data' => $kurir->fresh(),
        ], 200);
    }

    public function destroy($id)
    {
        $kurir = Kurir::find($id);

        if (!$kurir) {
            return response()->json(['success' => false, 'message' => 'Kurir tidak ditemukan'], 404);
        }

        $kurir->delete();

        return response()->json(['success' => true, 'message' => 'Kurir berhasil dihapus'], 200);
    }
}
