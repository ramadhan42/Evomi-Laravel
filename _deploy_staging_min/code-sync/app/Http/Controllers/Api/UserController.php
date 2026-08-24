<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactReply;
use App\Models\Order;
use App\Models\User;
use App\Support\LocaleResolver;
use App\Support\ProductLocalizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * READ: Mengambil data profil user yang sedang login.
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil.',
            'data' => $request->user(),
        ], 200);
    }

    /**
     * READ ALL: Mengambil semua user yang terdaftar (Untuk Admin)
     */
    public function getAllUsers()
    {
        // Mengambil semua user kecuali data yang sensitif (password, token, dll otomatis tersembunyi)
        $users = User::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Semua data user berhasil diambil.',
            'data' => $users,
        ], 200);
    }

    /**
     * UPDATE: Memperbarui data profil user (termasuk nama & alamat lengkap).
     */
    // File: app/Http/Controllers/Api/UserController.php
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_lengkap' => ['nullable', 'string', 'max:255'],
            'alamat_lengkap' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'], // Tambahkan validasi phone
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'avatar_profile' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // Validasi gambar
        ]);

        // Handle File Upload
        if ($request->hasFile('avatar_profile')) {
            if ($user->avatar_profile && ! str_starts_with((string) $user->avatar_profile, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_profile);
            }
            $path = $request->file('avatar_profile')->store('avatars', 'public');
            $validated['avatar_profile'] = $path;
        }

        if (! empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user->fresh(),
        ], 200);
    }

    /**
     * DELETE: Menghapus akun user yang sedang login secara permanen.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Hapus token auth aktif terlebih dahulu (Jika menggunakan Laravel Sanctum)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Hapus user dari database
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun Anda telah berhasil dihapus secara permanen.',
        ], 200);
    }

    /**
     * UPDATE: Memperbarui user berdasarkan ID (Untuk Admin)
     */
    public function updateByAdmin(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nama_lengkap' => ['nullable', 'string', 'max:255'],
            'alamat_lengkap' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'avatar_profile' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_admin' => ['sometimes'],
        ]);

        if ($request->hasFile('avatar_profile')) {
            $path = $request->file('avatar_profile')->store('avatars', 'public');
            $validated['avatar_profile'] = $path;
        }

        if (! empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->has('is_admin')) {
            $validated['is_admin'] = $request->boolean('is_admin');
        } else {
            unset($validated['is_admin']);
        }

        // Jangan biarkan admin menurunkan role dirinya sendiri
        if (
            array_key_exists('is_admin', $validated)
            && auth()->check()
            && auth()->id() === $user->id
            && ! $validated['is_admin']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus status admin dari akun sendiri.',
            ], 403);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diperbarui.',
            'data' => $user->fresh(),
        ], 200);
    }

    /**
     * DELETE: Menghapus user berdasarkan ID (Untuk Admin)
     */
    public function destroyByAdmin($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        // Mencegah hapus admin / diri sendiri
        if ($user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Akun admin tidak dapat dihapus.',
            ], 403);
        }

        if (auth()->check() && auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bisa menghapus akun Anda sendiri dari halaman ini.',
            ], 403);
        }

        // Hapus token auth aktif (jika ada)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Hapus user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus oleh Admin.',
        ], 200);
    }

    /**
     * Mengambil riwayat belanja user
     */
    public function shoppingHistory(Request $request)
    {
        $user = $request->user();
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $history = Order::with('product')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(
            ProductLocalizer::mapWithProduct($history, $locale)
        );
    }

    /**
     * Lightweight badge counts for navbar / profile menu.
     */
    public function badges(Request $request)
    {
        $user = $request->user();

        // Join instead of whereHas to avoid correlated subquery cost on Hostinger
        $unread = ContactReply::query()
            ->join('contact_messages', 'contact_replies.contact_message_id', '=', 'contact_messages.id')
            ->where('contact_messages.email', $user->email)
            ->where(function ($q) {
                $q->where('contact_replies.is_read_by_user', false)
                    ->orWhereNull('contact_replies.is_read_by_user');
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $user->carts()->count(),
                'wishlist' => $user->wishlists()->count(),
                'history' => $user->orders()->count(),
                'payments' => $user->orders()
                    ->awaitingAnyPayment()
                    ->get()
                    ->map(fn (Order $o) => Order::invoiceRoot((string) $o->id))
                    ->unique()
                    ->count(),
                'unread' => $unread,
            ],
        ]);
    }
}
