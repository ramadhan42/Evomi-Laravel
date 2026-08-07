<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentSettingController extends Controller
{
    /** GET /api/payment-settings — public (checkout) */
    public function publicShow()
    {
        $settings = PaymentSetting::current();

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => $settings->provider?->value ?? 'manual',
                'is_production' => $settings->usesMidtrans()
                    ? $settings->midtransIsProduction()
                    : ($settings->usesXendit() ? $settings->xenditIsProduction() : false),
                'configured' => $settings->isConfigured(),
                'client_key' => $settings->usesMidtrans() ? $settings->midtransClientKey() : null,
            ],
        ]);
    }

    /** GET /api/admin/payment-settings */
    public function show()
    {
        $settings = PaymentSetting::current();

        return response()->json([
            'success' => true,
            'data' => $this->adminPayload($settings),
        ]);
    }

    /** PUT/PATCH /api/admin/payment-settings */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['sometimes', 'required', Rule::enum(PaymentProvider::class)],
            'midtrans' => ['sometimes', 'array'],
            'midtrans.is_production' => ['sometimes', 'boolean'],
            'midtrans.merchant_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'midtrans.client_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'midtrans.server_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'xendit' => ['sometimes', 'array'],
            'xendit.is_production' => ['sometimes', 'boolean'],
            'xendit.merchant_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'xendit.callback_token' => ['sometimes', 'nullable', 'string', 'max:255'],
            'xendit.secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = PaymentSetting::current();
        $data = $validator->validated();

        if (isset($data['provider'])) {
            $settings->provider = PaymentProvider::from((string) $data['provider']);
        }

        if (isset($data['midtrans']) && is_array($data['midtrans'])) {
            $this->applyMidtrans($settings, $data['midtrans']);
        }

        if (isset($data['xendit']) && is_array($data['xendit'])) {
            $this->applyXendit($settings, $data['xendit']);
        }

        $nextProvider = $settings->provider;

        if ($nextProvider === PaymentProvider::Midtrans && ! $settings->isMidtransConfigured()) {
            throw ValidationException::withMessages([
                'midtrans.client_key' => [
                    'Client key dan server key Midtrans wajib diisi saat Midtrans aktif.',
                ],
            ]);
        }

        if ($nextProvider === PaymentProvider::Xendit && ! $settings->isXenditConfigured()) {
            throw ValidationException::withMessages([
                'xendit.callback_token' => [
                    'Callback token dan secret key Xendit wajib diisi saat Xendit aktif.',
                ],
            ]);
        }

        // Keep legacy is_production in sync with the active provider mode.
        if ($settings->usesMidtrans()) {
            $settings->is_production = $settings->midtransIsProduction();
        } elseif ($settings->usesXendit()) {
            $settings->is_production = $settings->xenditIsProduction();
        } else {
            $settings->is_production = false;
        }

        $settings->updated_by = $request->user()?->id;
        $settings->save();

        return $this->show();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function applyMidtrans(PaymentSetting $settings, array $input): void
    {
        if (array_key_exists('is_production', $input)) {
            $settings->midtrans_is_production = (bool) $input['is_production'];
        }

        if (array_key_exists('merchant_id', $input)) {
            $settings->midtrans_merchant_id = $this->nullableTrim($input['merchant_id']);
        }

        if (array_key_exists('client_key', $input)) {
            $client = $this->nullableTrim($input['client_key']);
            if ($client !== null) {
                $settings->midtrans_client_key = $client;
            }
        }

        if (array_key_exists('server_key', $input)) {
            $server = $this->nullableTrim($input['server_key']);
            if ($server !== null) {
                $settings->midtrans_server_key = $server;
            }
        }

        $client = (string) ($settings->midtransClientKey() ?? '');
        $server = (string) ($settings->midtransServerKey() ?? '');

        if ($client === '' && $server === '') {
            return;
        }

        $clientSandbox = str_starts_with($client, 'SB-Mid-client-');
        $clientLive = str_starts_with($client, 'Mid-client-');
        $serverSandbox = str_starts_with($server, 'SB-Mid-server-');
        $serverLive = str_starts_with($server, 'Mid-server-');

        if ($client !== '' && ! $clientSandbox && ! $clientLive) {
            throw ValidationException::withMessages([
                'midtrans.client_key' => [
                    'Client Key Midtrans tidak valid. Gunakan SB-Mid-client-... atau Mid-client-...',
                ],
            ]);
        }

        if ($server !== '' && ! $serverSandbox && ! $serverLive) {
            throw ValidationException::withMessages([
                'midtrans.server_key' => [
                    'Server Key Midtrans tidak valid. Gunakan SB-Mid-server-... atau Mid-server-...',
                ],
            ]);
        }

        if (($clientSandbox || $serverSandbox) && $clientSandbox !== $serverSandbox) {
            throw ValidationException::withMessages([
                'midtrans.client_key' => [
                    'Client Key dan Server Key harus sama-sama SB- (Sandbox) atau sama-sama tanpa SB-.',
                ],
            ]);
        }

        if ($clientSandbox || $serverSandbox) {
            $settings->midtrans_is_production = false;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function applyXendit(PaymentSetting $settings, array $input): void
    {
        if (array_key_exists('is_production', $input)) {
            $settings->xendit_is_production = (bool) $input['is_production'];
        }

        if (array_key_exists('merchant_id', $input)) {
            $settings->xendit_merchant_id = $this->nullableTrim($input['merchant_id']);
        }

        if (array_key_exists('callback_token', $input)) {
            $token = $this->nullableTrim($input['callback_token']);
            if ($token !== null) {
                $settings->xendit_callback_token = $token;
            }
        }

        if (array_key_exists('secret_key', $input)) {
            $secret = $this->nullableTrim($input['secret_key']);
            if ($secret !== null) {
                $settings->xendit_secret_key = $secret;
            }
        }

        $secret = (string) ($settings->xenditSecretKey() ?? '');
        if ($secret === '') {
            return;
        }

        $dev = str_starts_with($secret, 'xnd_development_');
        $live = str_starts_with($secret, 'xnd_production_');

        if (! $dev && ! $live) {
            throw ValidationException::withMessages([
                'xendit.secret_key' => [
                    'Secret Key Xendit tidak valid. Gunakan xnd_development_... atau xnd_production_...',
                ],
            ]);
        }

        // Samakan mode Production dengan jenis key (seperti Midtrans SB-/live).
        $settings->xendit_is_production = $live;
    }

    private function adminPayload(PaymentSetting $settings): array
    {
        $midtransServer = $settings->midtransServerKey();
        $xenditSecret = $settings->xenditSecretKey();

        return [
            'provider' => $settings->provider?->value ?? 'manual',
            'is_production' => $settings->usesMidtrans()
                ? $settings->midtransIsProduction()
                : ($settings->usesXendit() ? $settings->xenditIsProduction() : false),
            'configured' => $settings->isConfigured(),
            'updated_by' => $settings->updated_by,
            'updated_at' => $settings->updated_at,
            'midtrans' => [
                'is_production' => $settings->midtransIsProduction(),
                'merchant_id' => $settings->midtransMerchantId(),
                'client_key' => $settings->midtransClientKey(),
                'server_key' => $midtransServer,
                'server_key_masked' => $settings->maskSecret($midtransServer),
                'has_server_key' => filled($midtransServer),
                'configured' => $settings->isMidtransConfigured(),
            ],
            'xendit' => [
                'is_production' => $settings->xenditIsProduction(),
                'merchant_id' => $settings->xenditMerchantId(),
                'callback_token' => $settings->xenditCallbackToken(),
                'secret_key' => $xenditSecret,
                'secret_key_masked' => $settings->maskSecret($xenditSecret),
                'has_secret_key' => filled($xenditSecret),
                'configured' => $settings->isXenditConfigured(),
            ],
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
