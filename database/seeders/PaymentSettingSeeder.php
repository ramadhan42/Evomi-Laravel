<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use App\PaymentProvider;
use Illuminate\Database\Seeder;

class PaymentSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = PaymentSetting::query()->firstOrNew(['id' => PaymentSetting::SINGLETON_ID]);

        if (! $settings->exists) {
            $settings->fill([
                'provider' => PaymentProvider::Manual->value,
                'is_production' => false,
                'midtrans_is_production' => (bool) config('services.midtrans.is_production', false),
                'midtrans_merchant_id' => config('services.midtrans.merchant_id'),
                'midtrans_client_key' => config('services.midtrans.client_key'),
                'midtrans_server_key' => config('services.midtrans.server_key'),
                'xendit_is_production' => false,
                'xendit_merchant_id' => config('services.xendit.merchant_id'),
                'xendit_callback_token' => config('services.xendit.callback_token'),
                'xendit_secret_key' => config('services.xendit.secret_key'),
            ]);
            $settings->save();
        }
    }
}
