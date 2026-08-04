<?php

namespace App\Models;

use App\PaymentProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    public const SINGLETON_ID = 1;

    protected $fillable = [
        'provider',
        'is_production',
        // legacy shared columns (kept for migration compatibility)
        'merchant_id',
        'client_key',
        'server_key',
        // Midtrans
        'midtrans_is_production',
        'midtrans_merchant_id',
        'midtrans_client_key',
        'midtrans_server_key',
        // Xendit
        'xendit_is_production',
        'xendit_merchant_id',
        'xendit_callback_token',
        'xendit_secret_key',
        'updated_by',
    ];

    protected $attributes = [
        'provider' => 'manual',
        'is_production' => false,
        'midtrans_is_production' => false,
        'xendit_is_production' => false,
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => self::SINGLETON_ID],
            [
                'provider' => PaymentProvider::Manual->value,
                'is_production' => false,
                'midtrans_is_production' => false,
                'xendit_is_production' => false,
            ],
        );
    }

    public function usesMidtrans(): bool
    {
        return $this->provider === PaymentProvider::Midtrans;
    }

    public function usesXendit(): bool
    {
        return $this->provider === PaymentProvider::Xendit;
    }

    public function usesManual(): bool
    {
        return $this->provider === PaymentProvider::Manual;
    }

    public function midtransMerchantId(): ?string
    {
        return $this->filledOrNull($this->midtrans_merchant_id);
    }

    public function midtransClientKey(): ?string
    {
        return $this->filledOrNull($this->midtrans_client_key);
    }

    public function midtransServerKey(): ?string
    {
        return $this->filledOrNull($this->midtrans_server_key);
    }

    public function midtransIsProduction(): bool
    {
        return (bool) $this->midtrans_is_production;
    }

    public function xenditMerchantId(): ?string
    {
        return $this->filledOrNull($this->xendit_merchant_id);
    }

    public function xenditCallbackToken(): ?string
    {
        return $this->filledOrNull($this->xendit_callback_token);
    }

    public function xenditSecretKey(): ?string
    {
        return $this->filledOrNull($this->xendit_secret_key);
    }

    public function xenditIsProduction(): bool
    {
        return (bool) $this->xendit_is_production;
    }

    public function isMidtransConfigured(): bool
    {
        return filled($this->midtransClientKey()) && filled($this->midtransServerKey());
    }

    public function isXenditConfigured(): bool
    {
        return filled($this->xenditCallbackToken()) && filled($this->xenditSecretKey());
    }

    public function isConfigured(): bool
    {
        if ($this->usesManual()) {
            return true;
        }

        if ($this->usesMidtrans()) {
            return $this->isMidtransConfigured();
        }

        if ($this->usesXendit()) {
            return $this->isXenditConfigured();
        }

        return false;
    }

    public function maskSecret(?string $key): ?string
    {
        if (! filled($key)) {
            return null;
        }

        $value = (string) $key;
        $suffix = strlen($value) <= 4 ? $value : substr($value, -4);

        return '****'.$suffix;
    }

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'is_production' => 'boolean',
            'midtrans_is_production' => 'boolean',
            'xendit_is_production' => 'boolean',
            'server_key' => 'encrypted',
            'midtrans_server_key' => 'encrypted',
            'xendit_secret_key' => 'encrypted',
        ];
    }

    private function filledOrNull(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return (string) $value;
    }
}
