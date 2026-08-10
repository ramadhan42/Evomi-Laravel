<?php

namespace App\Services\Xendit;

use App\Models\PaymentSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class XenditClient
{
    public function __construct(private ?PaymentSetting $settings = null) {}

    public function settings(): PaymentSetting
    {
        return $this->settings ??= PaymentSetting::current();
    }

    /**
     * @return array{id: string, qr_string: string, status?: string|null, reference_id?: string|null}
     */
    public function createQrCode(array $payload): array
    {
        $settings = $this->settings();
        $secretKey = trim((string) ($settings->xenditSecretKey() ?? ''));

        if (! $settings->usesXendit() || ! $settings->isConfigured() || $secretKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Xendit belum dikonfigurasi. Isi kredensial di Pengaturan Pembayaran.'],
            ]);
        }

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->withHeaders(['api-version' => '2022-07-31'])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('https://api.xendit.co/qr_codes', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'message')
                ?? 'Gagal membuat QRIS Xendit.';

            throw ValidationException::withMessages([
                'payment' => [is_string($message) ? $message : 'Gagal membuat QRIS Xendit.'],
            ]);
        }

        $id = $response['id'] ?? null;
        $qrString = $response['qr_string'] ?? null;

        if (! is_string($id) || $id === '' || ! is_string($qrString) || $qrString === '') {
            throw new RuntimeException('Respons Xendit QR tidak lengkap.');
        }

        return [
            'id' => $id,
            'qr_string' => $qrString,
            'status' => is_string($response['status'] ?? null) ? $response['status'] : null,
            'reference_id' => is_string($response['reference_id'] ?? null) ? $response['reference_id'] : null,
        ];
    }

    /**
     * Create closed single-use Virtual Account.
     * POST https://api.xendit.co/callback_virtual_accounts
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *   id: string,
     *   account_number: string,
     *   bank_code: string,
     *   status?: string|null,
     *   external_id?: string|null,
     *   expiration_date?: string|null,
     *   expected_amount?: int|null
     * }
     */
    public function createVirtualAccount(array $payload): array
    {
        $settings = $this->settings();
        $secretKey = trim((string) ($settings->xenditSecretKey() ?? ''));

        if (! $settings->usesXendit() || ! $settings->isConfigured() || $secretKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Xendit belum dikonfigurasi. Isi kredensial di Pengaturan Pembayaran.'],
            ]);
        }

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('https://api.xendit.co/callback_virtual_accounts', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'message')
                ?? 'Gagal membuat Virtual Account Xendit.';

            throw ValidationException::withMessages([
                'payment' => [is_string($message) ? $message : 'Gagal membuat Virtual Account Xendit.'],
            ]);
        }

        $id = $response['id'] ?? null;
        $accountNumber = $response['account_number'] ?? null;
        $bankCode = $response['bank_code'] ?? ($payload['bank_code'] ?? null);

        if (! is_string($id) || $id === '' || ! is_string($accountNumber) || $accountNumber === '') {
            throw new RuntimeException('Respons Virtual Account Xendit tidak lengkap.');
        }

        return [
            'id' => $id,
            'account_number' => $accountNumber,
            'bank_code' => is_string($bankCode) ? strtoupper($bankCode) : '',
            'status' => is_string($response['status'] ?? null) ? $response['status'] : null,
            'external_id' => is_string($response['external_id'] ?? null) ? $response['external_id'] : null,
            'expiration_date' => is_string($response['expiration_date'] ?? null) ? $response['expiration_date'] : null,
            'expected_amount' => isset($response['expected_amount']) ? (int) $response['expected_amount'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getVirtualAccount(string $id): array
    {
        $settings = $this->settings();
        $secretKey = trim((string) ($settings->xenditSecretKey() ?? ''));

        if (! $settings->usesXendit() || $secretKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Xendit belum dikonfigurasi.'],
            ]);
        }

        try {
            return Http::withBasicAuth($secretKey, '')
                ->acceptJson()
                ->timeout(20)
                ->get('https://api.xendit.co/callback_virtual_accounts/'.$id)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'message')
                ?? 'Gagal mengecek status Virtual Account.';

            throw ValidationException::withMessages([
                'payment' => [is_string($message) ? $message : 'Gagal mengecek status Virtual Account.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getQrCode(string $id): array
    {
        $settings = $this->settings();
        $secretKey = trim((string) ($settings->xenditSecretKey() ?? ''));

        if (! $settings->usesXendit() || $secretKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Xendit belum dikonfigurasi.'],
            ]);
        }

        try {
            return Http::withBasicAuth($secretKey, '')
                ->withHeaders(['api-version' => '2022-07-31'])
                ->acceptJson()
                ->timeout(20)
                ->get('https://api.xendit.co/qr_codes/'.$id)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'message')
                ?? 'Gagal mengecek status QRIS.';

            throw ValidationException::withMessages([
                'payment' => [is_string($message) ? $message : 'Gagal mengecek status QRIS.'],
            ]);
        }
    }

    public function verifyCallbackToken(?string $callbackToken): bool
    {
        $expected = (string) ($this->settings()->xenditCallbackToken() ?? '');

        if ($expected === '' || $callbackToken === null || $callbackToken === '') {
            return false;
        }

        return hash_equals($expected, $callbackToken);
    }
}
