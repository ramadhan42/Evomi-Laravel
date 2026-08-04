<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disclaimer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisclaimerController extends Controller
{
    // READ: Ambil semua disclaimer
    public function index()
    {
        $disclaimers = Disclaimer::orderBy('id', 'asc')->get();
        return response()->json(['success' => true, 'data' => $disclaimers], 200);
    }

    // READ: Ambil detail 1 disclaimer
    public function show($id)
    {
        $disclaimer = Disclaimer::find($id);

        if (!$disclaimer) {
            return response()->json(['success' => false, 'message' => 'Disclaimer tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $disclaimer], 200);
    }

    // CREATE: Tambah disclaimer baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $disclaimer = Disclaimer::create([
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disclaimer berhasil ditambahkan',
            'data' => $disclaimer,
        ], 201);
    }

    // UPDATE: Edit disclaimer
    public function update(Request $request, $id)
    {
        $disclaimer = Disclaimer::find($id);

        if (!$disclaimer) {
            return response()->json(['success' => false, 'message' => 'Disclaimer tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $disclaimer->update([
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disclaimer berhasil diupdate',
            'data' => $disclaimer,
        ], 200);
    }

    // DELETE: Hapus disclaimer
    public function destroy($id)
    {
        $disclaimer = Disclaimer::find($id);

        if (!$disclaimer) {
            return response()->json(['success' => false, 'message' => 'Disclaimer tidak ditemukan'], 404);
        }

        $disclaimer->delete();

        return response()->json(['success' => true, 'message' => 'Disclaimer berhasil dihapus'], 200);
    }
}
