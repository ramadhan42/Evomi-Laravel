<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_en',
        'description',
        'description_en',
        'color',
        'price',
        'personality_type',
        'personality_type_en',
        'top_note',
        'top_note_en',
        'middle_note',
        'middle_note_en',
        'base_note',
        'base_note_en',
        'olfactory_family',
        'olfactory_family_en',
        'sillage',
        'sillage_en',
        'projection',
        'projection_en',
        'longevity',
        'longevity_en',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_produk_belanja',
        'bottle_size',
        'perfume_type',
        'perfume_type_en',
        'gender',
        'gender_en',
        'quantity',
        'stock_status',
        'stock_status_en',
        'alamat_awal_pengiriman',
        'kondisi',
        'kondisi_en',
        'kategori',
        'kategori_en',
        'berat_satuan',
        'brand',
        'brand_en',
        'etalase',
        'etalase_en',
    ];

    /**
     * Sinkronkan label stok dari angka quantity.
     * @return array{stock_status: string, stock_status_en: string}
     */
    public static function statusFromQuantity(int $quantity): array
    {
        if ($quantity <= 0) {
            return [
                'stock_status' => 'habis',
                'stock_status_en' => 'Out of stock',
            ];
        }

        if ($quantity <= 10) {
            return [
                'stock_status' => 'minim',
                'stock_status_en' => 'Low stock',
            ];
        }

        return [
            'stock_status' => 'tersedia',
            'stock_status_en' => 'Available',
        ];
    }

    public function applyStockStatusFromQuantity(): void
    {
        $status = self::statusFromQuantity((int) $this->quantity);
        $this->stock_status = $status['stock_status'];
        $this->stock_status_en = $status['stock_status_en'];
    }

    /**
     * Kurangi stok setelah pembelian (row harus sudah di-lock).
     *
     * @throws \RuntimeException
     */
    public function decrementStock(int $qty): void
    {
        $qty = max(0, $qty);
        $available = (int) $this->quantity;

        if ($qty <= 0) {
            return;
        }

        if ($available < $qty) {
            $title = $this->title ?: ('#' . $this->id);
            throw new \RuntimeException(
                "Stok tidak cukup untuk \"{$title}\". Tersedia: {$available}, diminta: {$qty}."
            );
        }

        $this->quantity = $available - $qty;
        $this->applyStockStatusFromQuantity();
        $this->save();
    }

    /**
     * Kembalikan stok jika pesanan dibatalkan / pembayaran kedaluwarsa.
     */
    public function restoreStock(int $qty): void
    {
        $qty = max(0, $qty);
        if ($qty <= 0) {
            return;
        }

        $this->quantity = (int) $this->quantity + $qty;
        $this->applyStockStatusFromQuantity();
        $this->save();
    }
}