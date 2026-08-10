<?php

namespace App\Services\Midtrans;

use App\Models\PaymentSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MidtransClient
{
    public function __construct(private ?PaymentSetting $settings = null) {}

    public function settings(): PaymentSetting
    {
        return $this->settings ??= PaymentSetting::current();
    }

    /**
     * Create QRIS via Core API: POST /v2/charge
     *
     * @return array{
     *   transaction_id: string,
     *   order_id: string,
     *   qr_string: string,
     *   status?: string|null,
     *   actions?: array<int, mixed>,
     *   expiry_time?: string|null
     * }
     */
    public function createQrisCharge(array $payload): array
    {
        $settings = $this->settings();
        $serverKey = trim((string) ($settings->midtransServerKey() ?? ''));

        if (! $settings->usesMidtrans() || ! $settings->isConfigured() || $serverKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Midtrans belum dikonfigurasi. Isi kredensial di Pengaturan Pembayaran.'],
            ]);
        }

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->apiBaseUrl().'/v2/charge', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $rawMessage = data_get($exception->response?->json(), 'status_message')
                ?? data_get($exception->response?->json(), 'error_messages.0')
                ?? 'Gagal membuat QRIS Midtrans.';
            $message = is_string($rawMessage) ? $rawMessage : 'Gagal membuat QRIS Midtrans.';

            if (
                $exception->response?->status() === 401
                || str_contains(strtolower($message), 'unknown merchant')
            ) {
                $message = $this->isProductionEnvironment()
                    ? 'Server Key Production tidak dikenali Midtrans. Pastikan Anda menempel Server Key Production yang benar (Mid-server-...), atau matikan Mode production untuk Sandbox.'
                    : 'Server Key Sandbox tidak dikenali Midtrans. Tempel ulang Server Key Midtrans Sandbox, jangan pakai Client Key.';
            }

            throw ValidationException::withMessages([
                'payment' => [$message],
            ]);
        }

        $transactionId = $response['transaction_id'] ?? null;
        $orderId = $response['order_id'] ?? null;
        $qrString = $response['qr_string'] ?? null;
        $statusCode = (string) ($response['status_code'] ?? '');

        if (
            ! is_string($transactionId) || $transactionId === ''
            || ! is_string($orderId) || $orderId === ''
            || ! is_string($qrString) || $qrString === ''
        ) {
            throw new RuntimeException(
                is_string($response['status_message'] ?? null)
                    ? $response['status_message']
                    : 'Respons Midtrans QRIS tidak lengkap.'
            );
        }

        if ($statusCode !== '' && ! in_array($statusCode, ['200', '201'], true)) {
            throw ValidationException::withMessages([
                'payment' => [
                    is_string($response['status_message'] ?? null)
                        ? $response['status_message']
                        : 'Gagal membuat QRIS Midtrans.',
                ],
            ]);
        }

        return [
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'qr_string' => $qrString,
            'status' => is_string($response['transaction_status'] ?? null)
                ? $response['transaction_status']
                : null,
            'actions' => is_array($response['actions'] ?? null) ? $response['actions'] : [],
            'expiry_time' => is_string($response['expiry_time'] ?? null)
                ? $response['expiry_time']
                : null,
        ];
    }

    /**
     * Create Virtual Account / bank transfer via Core API: POST /v2/charge
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *   transaction_id: string,
     *   order_id: string,
     *   bank: string,
     *   va_number: string,
     *   status?: string|null,
     *   expiry_time?: string|null,
     *   payment_type?: string|null
     * }
     */
    public function createBankTransferCharge(array $payload): array
    {
        $settings = $this->settings();
        $serverKey = trim((string) ($settings->midtransServerKey() ?? ''));

        if (! $settings->usesMidtrans() || ! $settings->isConfigured() || $serverKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Midtrans belum dikonfigurasi. Isi kredensial di Pengaturan Pembayaran.'],
            ]);
        }

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->apiBaseUrl().'/v2/charge', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $rawMessage = data_get($exception->response?->json(), 'status_message')
                ?? data_get($exception->response?->json(), 'error_messages.0')
                ?? 'Gagal membuat Virtual Account Midtrans.';
            $message = is_string($rawMessage) ? $rawMessage : 'Gagal membuat Virtual Account Midtrans.';

            if (
                $exception->response?->status() === 401
                || str_contains(strtolower($message), 'unknown merchant')
            ) {
                $message = $this->isProductionEnvironment()
                    ? 'Server Key Production tidak dikenali Midtrans. Pastikan Server Key Production benar, atau matikan Mode production untuk Sandbox.'
                    : 'Server Key Sandbox tidak dikenali Midtrans. Tempel ulang Server Key Midtrans Sandbox.';
            }

            throw ValidationException::withMessages([
                'payment' => [$message],
            ]);
        }

        $transactionId = $response['transaction_id'] ?? null;
        $orderId = $response['order_id'] ?? null;
        $statusCode = (string) ($response['status_code'] ?? '');
        $paymentType = strtolower((string) ($response['payment_type'] ?? $payload['payment_type'] ?? ''));

        $bank = '';
        $vaNumber = '';

        if ($paymentType === 'echannel') {
            $bank = 'mandiri';
            $billerCode = (string) ($response['biller_code'] ?? '');
            $billKey = (string) ($response['bill_key'] ?? '');
            $vaNumber = trim($billerCode.' '.$billKey);
        } else {
            $vaList = is_array($response['va_numbers'] ?? null) ? $response['va_numbers'] : [];
            $firstVa = is_array($vaList[0] ?? null) ? $vaList[0] : [];
            $bank = strtolower((string) ($firstVa['bank'] ?? data_get($payload, 'bank_transfer.bank') ?? ''));
            $vaNumber = (string) ($firstVa['va_number'] ?? '');

            if ($vaNumber === '' && isset($response['permata_va_number'])) {
                $bank = 'permata';
                $vaNumber = (string) $response['permata_va_number'];
            }
        }

        if (
            ! is_string($transactionId) || $transactionId === ''
            || ! is_string($orderId) || $orderId === ''
            || $vaNumber === ''
        ) {
            throw new RuntimeException(
                is_string($response['status_message'] ?? null)
                    ? $response['status_message']
                    : 'Respons Virtual Account Midtrans tidak lengkap.'
            );
        }

        if ($statusCode !== '' && ! in_array($statusCode, ['200', '201'], true)) {
            throw ValidationException::withMessages([
                'payment' => [
                    is_string($response['status_message'] ?? null)
                        ? $response['status_message']
                        : 'Gagal membuat Virtual Account Midtrans.',
                ],
            ]);
        }

        return [
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'bank' => $bank !== '' ? $bank : 'bank',
            'va_number' => $vaNumber,
            'status' => is_string($response['transaction_status'] ?? null)
                ? $response['transaction_status']
                : null,
            'expiry_time' => is_string($response['expiry_time'] ?? null)
                ? $response['expiry_time']
                : null,
            'payment_type' => $paymentType !== '' ? $paymentType : 'bank_transfer',
            'biller_code' => is_string($response['biller_code'] ?? null) ? $response['biller_code'] : null,
            'bill_key' => is_string($response['bill_key'] ?? null) ? $response['bill_key'] : null,
        ];
    }

    /**
     * GET /v2/{order_id}/status
     *
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $orderId): array
    {
        $settings = $this->settings();
        $serverKey = trim((string) ($settings->midtransServerKey() ?? ''));

        if (! $settings->usesMidtrans() || $serverKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Midtrans belum dikonfigurasi.'],
            ]);
        }

        try {
            return Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->timeout(20)
                ->get($this->apiBaseUrl().'/v2/'.rawurlencode($orderId).'/status')
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'status_message')
                ?? 'Gagal mengecek status QRIS Midtrans.';

            throw ValidationException::withMessages([
                'payment' => [is_string($message) ? $message : 'Gagal mengecek status QRIS Midtrans.'],
            ]);
        }
    }

    /**
     * @return array{token: string, redirect_url?: string|null}
     */
    public function createSnapTransaction(array $payload): array
    {
        $settings = $this->settings();
        $serverKey = trim((string) ($settings->midtransServerKey() ?? ''));

        if (! $settings->usesMidtrans() || ! $settings->isConfigured() || $serverKey === '') {
            throw ValidationException::withMessages([
                'payment' => ['Midtrans belum dikonfigurasi. Isi kredensial di Pengaturan Pembayaran.'],
            ]);
        }

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->snapBaseUrl().'/snap/v1/transactions', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = data_get($exception->response?->json(), 'error_messages.0')
                ?? data_get($exception->response?->json(), 'status_message')
                ?? 'Gagal membuat transaksi Midtrans Snap.';

            throw ValidationException::withMessages([
                'payment' => [is_string($message) ? $message : 'Gagal membuat transaksi Midtrans Snap.'],
            ]);
        }

        $token = $response['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Respons Midtrans Snap tidak berisi token.');
        }

        return [
            'token' => $token,
            'redirect_url' => is_string($response['redirect_url'] ?? null)
                ? $response['redirect_url']
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyNotificationSignature(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) ($this->settings()->midtransServerKey() ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '' || $serverKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }

    private function apiBaseUrl(): string
    {
        return $this->isProductionEnvironment()
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    private function snapBaseUrl(): string
    {
        return $this->isProductionEnvironment()
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    /**
     * Prefer explicit Midtrans mode. Only force sandbox when keys clearly use SB- prefix.
     */
    public function isProductionEnvironment(): bool
    {
        $settings = $this->settings();
        $serverKey = trim((string) ($settings->midtransServerKey() ?? ''));
        $clientKey = trim((string) ($settings->midtransClientKey() ?? ''));

        $explicitSandbox =
            str_starts_with($serverKey, 'SB-Mid-server-')
            || str_starts_with($clientKey, 'SB-Mid-client-');

        if ($explicitSandbox) {
            return false;
        }

        return $settings->midtransIsProduction();
    }
}
