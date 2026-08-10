<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Services\Midtrans\MidtransClient;
use App\Services\OrderPaymentService;
use App\Services\Xendit\XenditClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentGatewayController extends Controller
{
    /**
     * POST /api/payments/xendit/qr
     * Body: { reference_id, amount, expires_at? }
     */
    public function createXenditQr(Request $request, XenditClient $xendit)
    {
        $validator = Validator::make($request->all(), [
            'reference_id' => 'required|string|max:80',
            'amount' => 'required|numeric|min:1',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $expiresAt = $data['expires_at']
            ?? now()->addHours(24)->toIso8601String();

        try {
            $qr = $xendit->createQrCode([
                'reference_id' => $data['reference_id'],
                'type' => 'DYNAMIC',
                'currency' => 'IDR',
                'amount' => (int) round((float) $data['amount']),
                'expires_at' => $expiresAt,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $qr['id'],
                    'qr_string' => $qr['qr_string'],
                    'status' => $qr['status'] ?? null,
                    'reference_id' => $qr['reference_id'] ?? $data['reference_id'],
                    'invoice_id' => $data['reference_id'],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal membuat QRIS.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Xendit QR create failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QRIS Xendit.',
            ], 500);
        }
    }

    /** GET /api/payments/xendit/qr/{id} */
    public function showXenditQr(string $id, XenditClient $xendit)
    {
        try {
            $qr = $xendit->getQrCode($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $qr['id'] ?? $id,
                    'status' => $qr['status'] ?? null,
                    'reference_id' => $qr['reference_id'] ?? null,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal cek status QRIS.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Xendit QR status failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status QRIS.',
            ], 500);
        }
    }

    /**
     * POST /api/payments/midtrans/qris
     * Core API charge: POST /v2/charge with payment_type=qris
     * Body: { order_id, amount, customer_name?, customer_email?, customer_phone?, item_name?, item_id? }
     */
    public function createMidtransQris(Request $request, MidtransClient $midtrans)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|max:80',
            'amount' => 'required|numeric|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'item_name' => 'nullable|string|max:255',
            'item_id' => 'nullable|string|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $amount = (int) round((float) $data['amount']);
        $fullName = trim((string) ($data['customer_name'] ?? ''));
        $nameParts = preg_split('/\s+/', $fullName, 2) ?: [];
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? '';

        try {
            $qris = $midtrans->createQrisCharge([
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $data['order_id'],
                    'gross_amount' => $amount,
                ],
                'item_details' => [[
                    'id' => (string) ($data['item_id'] ?? 'evomi-order'),
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => $data['item_name'] ?? 'Pesanan Evomi',
                ]],
                'customer_details' => array_filter([
                    'first_name' => $firstName !== '' ? $firstName : 'Customer',
                    'last_name' => $lastName !== '' ? $lastName : null,
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['customer_phone'] ?? null,
                ]),
                'qris' => [
                    'acquirer' => 'gopay',
                ],
                'custom_expiry' => [
                    'expiry_duration' => 24,
                    'unit' => 'hour',
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $qris['transaction_id'],
                    'transaction_id' => $qris['transaction_id'],
                    'order_id' => $qris['order_id'],
                    'qr_string' => $qris['qr_string'],
                    'status' => $qris['status'] ?? null,
                    'expiry_time' => $qris['expiry_time'] ?? null,
                    'invoice_id' => $data['order_id'],
                    'is_production' => $midtrans->isProductionEnvironment(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal membuat QRIS Midtrans.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Midtrans QRIS create failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QRIS Midtrans.',
            ], 500);
        }
    }

    /** GET /api/payments/midtrans/qris/{orderId} — status by order_id */
    public function showMidtransQris(string $orderId, MidtransClient $midtrans)
    {
        try {
            $status = $midtrans->getTransactionStatus($orderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $status['transaction_id'] ?? $orderId,
                    'order_id' => $status['order_id'] ?? $orderId,
                    'status' => $status['transaction_status'] ?? null,
                    'fraud_status' => $status['fraud_status'] ?? null,
                    'payment_type' => $status['payment_type'] ?? null,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal cek status QRIS Midtrans.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Midtrans QRIS status failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status QRIS Midtrans.',
            ], 500);
        }
    }

    /**
     * POST /api/payments/midtrans/va
     * Body: { order_id, amount, bank, customer_name?, customer_email?, customer_phone?, item_name?, item_id? }
     */
    public function createMidtransVa(Request $request, MidtransClient $midtrans)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|max:80',
            'amount' => 'required|numeric|min:1',
            'bank' => 'required|string|in:bca,bni,bri,mandiri,permata',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'item_name' => 'nullable|string|max:255',
            'item_id' => 'nullable|string|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $amount = (int) round((float) $data['amount']);
        $bank = strtolower($data['bank']);
        $fullName = trim((string) ($data['customer_name'] ?? ''));
        $nameParts = preg_split('/\s+/', $fullName, 2) ?: [];
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? '';

        $basePayload = [
            'transaction_details' => [
                'order_id' => $data['order_id'],
                'gross_amount' => $amount,
            ],
            'item_details' => [[
                'id' => (string) ($data['item_id'] ?? 'evomi-order'),
                'price' => $amount,
                'quantity' => 1,
                'name' => $data['item_name'] ?? 'Pesanan Evomi',
            ]],
            'customer_details' => array_filter([
                'first_name' => $firstName !== '' ? $firstName : 'Customer',
                'last_name' => $lastName !== '' ? $lastName : null,
                'email' => $data['customer_email'] ?? null,
                'phone' => $data['customer_phone'] ?? null,
            ]),
        ];

        if ($bank === 'mandiri') {
            $payload = array_merge($basePayload, [
                'payment_type' => 'echannel',
                'echannel' => [
                    'bill_info1' => 'Payment:',
                    'bill_info2' => 'Evomi Order',
                ],
            ]);
        } else {
            $payload = array_merge($basePayload, [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => [
                    'bank' => $bank,
                ],
            ]);
        }

        try {
            $va = $midtrans->createBankTransferCharge($payload);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $va['order_id'],
                    'transaction_id' => $va['transaction_id'],
                    'order_id' => $va['order_id'],
                    'bank' => $va['bank'],
                    'va_number' => $va['va_number'],
                    'biller_code' => $va['biller_code'] ?? null,
                    'bill_key' => $va['bill_key'] ?? null,
                    'status' => $va['status'] ?? null,
                    'expiry_time' => $va['expiry_time'] ?? null,
                    'invoice_id' => $data['order_id'],
                    'is_production' => $midtrans->isProductionEnvironment(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal membuat Virtual Account Midtrans.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Midtrans VA create failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat Virtual Account Midtrans.',
            ], 500);
        }
    }

    /** GET /api/payments/midtrans/va/{orderId} */
    public function showMidtransVa(string $orderId, MidtransClient $midtrans)
    {
        try {
            $status = $midtrans->getTransactionStatus($orderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $status['transaction_id'] ?? $orderId,
                    'order_id' => $status['order_id'] ?? $orderId,
                    'status' => $status['transaction_status'] ?? null,
                    'fraud_status' => $status['fraud_status'] ?? null,
                    'payment_type' => $status['payment_type'] ?? null,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal cek status VA Midtrans.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Midtrans VA status failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status Virtual Account Midtrans.',
            ], 500);
        }
    }

    /**
     * POST /api/payments/xendit/va
     * Body: { external_id, amount, bank, customer_name?, expires_at? }
     */
    public function createXenditVa(Request $request, XenditClient $xendit)
    {
        $validator = Validator::make($request->all(), [
            'external_id' => 'required|string|max:80',
            'amount' => 'required|numeric|min:1',
            'bank' => 'required|string|in:bca,bni,bri,mandiri,permata',
            'customer_name' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $bankMap = [
            'bca' => 'BCA',
            'bni' => 'BNI',
            'bri' => 'BRI',
            'mandiri' => 'MANDIRI',
            'permata' => 'PERMATA',
        ];
        $bankCode = $bankMap[strtolower($data['bank'])] ?? 'BCA';
        $name = trim((string) ($data['customer_name'] ?? 'EVOMI'));
        if ($name === '') {
            $name = 'EVOMI';
        }
        // Xendit VA name: letters/spaces only, max 50
        $name = preg_replace('/[^A-Za-z\s]/', '', $name) ?: 'EVOMI';
        $name = mb_substr($name, 0, 50);

        $expiresAt = $data['expires_at']
            ?? now()->addHours(24)->toIso8601String();

        try {
            $va = $xendit->createVirtualAccount([
                'external_id' => $data['external_id'],
                'bank_code' => $bankCode,
                'name' => $name,
                'expected_amount' => (int) round((float) $data['amount']),
                'is_closed' => true,
                'is_single_use' => true,
                'expiration_date' => $expiresAt,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $va['id'],
                    'bank' => strtolower($data['bank']),
                    'bank_code' => $va['bank_code'],
                    'va_number' => $va['account_number'],
                    'status' => $va['status'] ?? null,
                    'external_id' => $va['external_id'] ?? $data['external_id'],
                    'expiry_time' => $va['expiration_date'] ?? null,
                    'expected_amount' => $va['expected_amount'] ?? (int) round((float) $data['amount']),
                    'invoice_id' => $data['external_id'],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal membuat Virtual Account Xendit.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Xendit VA create failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat Virtual Account Xendit.',
            ], 500);
        }
    }

    /** GET /api/payments/xendit/va/{id} */
    public function showXenditVa(string $id, XenditClient $xendit)
    {
        try {
            $va = $xendit->getVirtualAccount($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $va['id'] ?? $id,
                    'status' => $va['status'] ?? null,
                    'external_id' => $va['external_id'] ?? null,
                    'bank_code' => $va['bank_code'] ?? null,
                    'va_number' => $va['account_number'] ?? null,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal cek status VA Xendit.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Xendit VA status failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status Virtual Account Xendit.',
            ], 500);
        }
    }

    /**
     * POST /api/payments/midtrans/snap
     * Body: { order_id, amount, customer_name?, customer_email?, item_name? }
     */
    public function createMidtransSnap(Request $request, MidtransClient $midtrans)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|max:80',
            'amount' => 'required|numeric|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'item_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $amount = (int) round((float) $data['amount']);
        $settings = PaymentSetting::current();

        try {
            $snap = $midtrans->createSnapTransaction([
                'transaction_details' => [
                    'order_id' => $data['order_id'],
                    'gross_amount' => $amount,
                ],
                'customer_details' => array_filter([
                    'first_name' => $data['customer_name'] ?? null,
                    'email' => $data['customer_email'] ?? null,
                ]),
                'item_details' => [[
                    'id' => 'evomi-order',
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => $data['item_name'] ?? 'Pesanan Evomi',
                ]],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $snap['token'],
                    'redirect_url' => $snap['redirect_url'] ?? null,
                    'client_key' => $settings->midtransClientKey(),
                    'is_production' => $midtrans->isProductionEnvironment(),
                    'order_id' => $data['order_id'],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Gagal membuat Snap.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap create failed', ['detail' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi Midtrans.',
            ], 500);
        }
    }

    /** POST /api/payments/midtrans/notification — webhook */
    public function midtransNotification(Request $request, MidtransClient $midtrans, OrderPaymentService $payments)
    {
        $payload = $request->all();

        if (! $midtrans->verifyNotificationSignature($payload)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $txStatus = strtolower((string) ($payload['transaction_status'] ?? ''));

        Log::info('Midtrans notification received', [
            'order_id' => $orderId,
            'transaction_status' => $txStatus,
        ]);

        if (
            $orderId !== ''
            && in_array($txStatus, ['settlement', 'capture', 'success'], true)
        ) {
            $payments->markInvoicePaid($orderId, [
                'gateway' => 'midtrans',
                'transaction_status' => $txStatus,
            ]);
        }

        if ($orderId !== '' && in_array($txStatus, ['expire', 'cancel', 'deny'], true)) {
            $payments->expireInvoice($orderId, 'gateway_'.$txStatus);
        }

        return response()->json(['success' => true]);
    }

    /** POST /api/payments/xendit/notification — webhook */
    public function xenditNotification(Request $request, XenditClient $xendit, OrderPaymentService $payments)
    {
        $token = $request->header('x-callback-token');

        if (! $xendit->verifyCallbackToken($token)) {
            return response()->json(['success' => false, 'message' => 'Invalid callback token'], 403);
        }

        $status = strtoupper((string) $request->input('status'));
        $externalId = (string) (
            $request->input('external_id')
            ?: $request->input('reference_id')
            ?: ''
        );

        Log::info('Xendit notification received', [
            'id' => $request->input('id'),
            'status' => $status,
            'external_id' => $externalId,
            'reference_id' => $request->input('reference_id'),
        ]);

        // Prefer explicit paid signals — avoid treating VA expiry INACTIVE as paid.
        $paidStatuses = ['COMPLETED', 'SUCCEEDED', 'PAID'];
        $hasPaidAmount = $request->filled('amount') || $request->filled('paid_amount');
        if (
            $externalId !== ''
            && (in_array($status, $paidStatuses, true) || ($hasPaidAmount && $status !== 'EXPIRED'))
        ) {
            $payments->markInvoicePaid($externalId, [
                'gateway' => 'xendit',
                'status' => $status,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
