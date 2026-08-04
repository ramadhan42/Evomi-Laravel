<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    // === TAMBAHKAN FUNGSI INI ===
    // Fungsi untuk mengambil semua data subscriber (Biasanya untuk Admin)
    public function index()
    {
        try {
            // Mengambil semua subscriber, diurutkan dari yang terbaru
            $subscribers = Subscriber::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengambil daftar subscriber.',
                'data' => $subscribers
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // ============================

    public function subscribe(Request $request)
    {
        // 1. Validasi input email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar pada buletin kami.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first('email'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 2. Simpan ke database
            $subscriber = Subscriber::create([
                'email' => $request->email,
            ]);

            // 3. Kembalikan response sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mendaftar buletin! Terima kasih telah berlangganan.',
                'data' => $subscriber
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}