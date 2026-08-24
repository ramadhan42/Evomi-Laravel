<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kurir;
use App\Models\KurirTarif;
use App\Support\ShippingConfig;
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

    /**
     * Public: Quote ongkir berdasarkan kota asal + kota tujuan + berat (tanpa integrasi API).
     *
     * Query:
     * - origin_city: kota asal / gudang (default: Cisauk)
     * - city / destination_city: nama kota tujuan (contoh: Jakarta)
     * - weight_grams: total berat paket dalam gram (inclusive)
     */
    public function quote(Request $request)
    {
        $originCity = trim((string) $request->query(
            'origin_city',
            $request->query('kota_asal', ShippingConfig::DEFAULT_ORIGIN_CITY),
        ));
        $destinationCity = trim((string) $request->query(
            'city',
            $request->query('destination_city', ''),
        ));
        $weightGrams = (float) $request->query('weight_grams', 0);

        if ($originCity === '' || $destinationCity === '' || ! is_finite($weightGrams) || $weightGrams <= 0) {
            return response()->json([
                'success' => true,
                'data' => [],
            ], 200);
        }

        $tarifs = KurirTarif::query()
            ->where('kota_asal', $originCity)
            ->where('kota_tujuan', $destinationCity)
            ->where('is_active', true)
            ->where('berat_min_gram', '<=', $weightGrams)
            ->where('berat_max_gram', '>=', $weightGrams)
            ->with(['kurir' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $data = $tarifs
            ->filter(fn (KurirTarif $t) => (bool) $t->kurir)
            ->map(function (KurirTarif $t) use ($originCity) {
                return [
                    // id dipakai oleh frontend sebagai id pilihan kurir.
                    'id' => (int) $t->kurir_id,
                    'kurir_tarif_id' => (int) $t->id,
                    'nama' => (string) ($t->kurir?->nama ?? ''),
                    'jenis' => (string) ($t->kurir?->jenis ?? ''),
                    'harga' => (float) $t->harga,
                    'admin_subsidy' => 0.0, // disiapkan untuk nanti (opsional)
                    'customer_harga' => (float) $t->harga, // disiapkan untuk nanti (opsional)
                    'estimasi_hari' => (int) ($t->estimasi_hari ?: ($t->kurir?->estimasi_hari ?? 3)),
                    'kota_asal' => (string) ($t->kota_asal ?: $originCity),
                    'kota_tujuan' => (string) $t->kota_tujuan,
                    'destinasi' => ($t->kota_asal ?: $originCity).' → '.$t->kota_tujuan,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /* ---------- KurirTarif (Admin) ---------- */

    public function tarifIndex(Request $request)
    {
        // endpoint khusus admin untuk CRUD tarif ongkir
        $query = KurirTarif::query()
            ->with(['kurir' => function ($q) {
                $q->select('id', 'nama', 'jenis');
            }]);

        $data = $query
            ->orderByDesc('id')
            ->get()
            ->map(function (KurirTarif $t) {
                return [
                    'id' => (int) $t->id,
                    'kurir_id' => (int) $t->kurir_id,
                    'nama' => (string) ($t->kurir?->nama ?? ''),
                    'jenis' => (string) ($t->kurir?->jenis ?? ''),
                    'kota_asal' => (string) ($t->kota_asal ?: ShippingConfig::DEFAULT_ORIGIN_CITY),
                    'kota_tujuan' => (string) $t->kota_tujuan,
                    'berat_min_gram' => (int) $t->berat_min_gram,
                    'berat_max_gram' => (int) $t->berat_max_gram,
                    'harga' => (float) $t->harga,
                    'estimasi_hari' => (int) $t->estimasi_hari,
                    'is_active' => (bool) $t->is_active,
                ];
            })
            ->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function tarifStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kurir_id' => 'required|integer|exists:kurirs,id',
            'kota_asal' => 'nullable|string|max:120',
            'kota_tujuan' => 'required|string|max:120',
            'berat_min_gram' => 'required|integer|min:0',
            'berat_max_gram' => 'required|integer|min:0',
            'harga' => 'required|numeric|min:0',
            'estimasi_hari' => 'nullable|integer|min:1|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        if ($payload['berat_min_gram'] > $payload['berat_max_gram']) {
            return response()->json([
                'success' => false,
                'message' => 'berat_min_gram harus <= berat_max_gram.',
            ], 422);
        }

        $tarif = KurirTarif::create([
            'kurir_id' => (int) $payload['kurir_id'],
            'kota_asal' => (string) ($payload['kota_asal'] ?? ShippingConfig::DEFAULT_ORIGIN_CITY),
            'kota_tujuan' => (string) $payload['kota_tujuan'],
            'berat_min_gram' => (int) $payload['berat_min_gram'],
            'berat_max_gram' => (int) $payload['berat_max_gram'],
            'harga' => (float) $payload['harga'],
            'estimasi_hari' => (int) ($payload['estimasi_hari'] ?? 3),
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarif ongkir berhasil ditambahkan',
            'data' => $tarif,
        ], 201);
    }

    public function tarifUpdate(Request $request, $id)
    {
        $tarif = KurirTarif::find($id);
        if (! $tarif) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'kurir_id' => 'sometimes|required|integer|exists:kurirs,id',
            'kota_asal' => 'sometimes|nullable|string|max:120',
            'kota_tujuan' => 'sometimes|required|string|max:120',
            'berat_min_gram' => 'sometimes|required|integer|min:0',
            'berat_max_gram' => 'sometimes|required|integer|min:0',
            'harga' => 'sometimes|required|numeric|min:0',
            'estimasi_hari' => 'nullable|integer|min:1|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $request->only([
            'kurir_id',
            'kota_asal',
            'kota_tujuan',
            'berat_min_gram',
            'berat_max_gram',
            'harga',
            'estimasi_hari',
            'is_active',
        ]);

        $nextMin = array_key_exists('berat_min_gram', $payload) ? (int) $payload['berat_min_gram'] : $tarif->berat_min_gram;
        $nextMax = array_key_exists('berat_max_gram', $payload) ? (int) $payload['berat_max_gram'] : $tarif->berat_max_gram;
        if ($nextMin > $nextMax) {
            return response()->json([
                'success' => false,
                'message' => 'berat_min_gram harus <= berat_max_gram.',
            ], 422);
        }

        $tarif->update([
            'kurir_id' => array_key_exists('kurir_id', $payload) ? (int) $payload['kurir_id'] : $tarif->kurir_id,
            'kota_asal' => array_key_exists('kota_asal', $payload)
                ? (string) ($payload['kota_asal'] ?: ShippingConfig::DEFAULT_ORIGIN_CITY)
                : $tarif->kota_asal,
            'kota_tujuan' => array_key_exists('kota_tujuan', $payload) ? (string) $payload['kota_tujuan'] : $tarif->kota_tujuan,
            'berat_min_gram' => $nextMin,
            'berat_max_gram' => $nextMax,
            'harga' => array_key_exists('harga', $payload) ? (float) $payload['harga'] : $tarif->harga,
            'estimasi_hari' => array_key_exists('estimasi_hari', $payload) && $payload['estimasi_hari'] !== null
                ? (int) $payload['estimasi_hari']
                : $tarif->estimasi_hari,
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : $tarif->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarif ongkir berhasil diperbarui',
            'data' => $tarif->fresh(),
        ], 200);
    }

    public function tarifDestroy($id)
    {
        $tarif = KurirTarif::find($id);
        if (! $tarif) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif tidak ditemukan',
            ], 404);
        }

        $tarif->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarif ongkir berhasil dihapus',
        ], 200);
    }

    public function shippingSettings()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'free_shipping' => ShippingConfig::isFreeShipping(),
            ],
        ], 200);
    }

    public function updateShippingSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'free_shipping' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        ShippingConfig::setFreeShipping((bool) $request->boolean('free_shipping'));

        return response()->json([
            'success' => true,
            'message' => $request->boolean('free_shipping')
                ? 'Gratis ongkir diaktifkan'
                : 'Gratis ongkir dinonaktifkan',
            'data' => [
                'free_shipping' => ShippingConfig::isFreeShipping(),
            ],
        ], 200);
    }
}
