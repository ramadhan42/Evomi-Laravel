<?php

use App\PaymentProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->boolean('midtrans_is_production')->default(false)->after('is_production');
            $table->string('midtrans_merchant_id')->nullable()->after('midtrans_is_production');
            $table->string('midtrans_client_key')->nullable()->after('midtrans_merchant_id');
            $table->text('midtrans_server_key')->nullable()->after('midtrans_client_key');

            $table->boolean('xendit_is_production')->default(false)->after('midtrans_server_key');
            $table->string('xendit_merchant_id')->nullable()->after('xendit_is_production');
            $table->string('xendit_callback_token')->nullable()->after('xendit_merchant_id');
            $table->text('xendit_secret_key')->nullable()->after('xendit_callback_token');
        });

        // Migrate existing shared credentials into provider-specific columns.
        $rows = DB::table('payment_settings')->get();
        foreach ($rows as $row) {
            $provider = (string) ($row->provider ?? 'manual');
            $merchantId = $row->merchant_id ?? null;
            $clientKey = $row->client_key ?? null;
            $serverKey = $row->server_key ?? null;
            $isProduction = (bool) ($row->is_production ?? false);

            $looksMidtrans =
                str_starts_with((string) $clientKey, 'SB-Mid-client-')
                || str_starts_with((string) $clientKey, 'Mid-client-')
                || str_starts_with((string) $serverKey, 'SB-Mid-server-')
                || str_starts_with((string) $serverKey, 'Mid-server-');

            $looksXendit =
                str_starts_with((string) $serverKey, 'xnd_')
                || ($clientKey && ! $looksMidtrans && $provider === PaymentProvider::Xendit->value);

            $update = [];

            if ($provider === PaymentProvider::Midtrans->value || ($looksMidtrans && $provider !== PaymentProvider::Xendit->value)) {
                $update['midtrans_is_production'] = $isProduction;
                $update['midtrans_merchant_id'] = $merchantId;
                $update['midtrans_client_key'] = $clientKey;
                $update['midtrans_server_key'] = $serverKey;
            }

            if ($provider === PaymentProvider::Xendit->value || $looksXendit) {
                $update['xendit_is_production'] = $isProduction;
                $update['xendit_merchant_id'] = $merchantId;
                $update['xendit_callback_token'] = $clientKey;
                $update['xendit_secret_key'] = $serverKey;
            }

            // If provider is midtrans but keys look xendit-only (or vice versa), still keep what we can.
            if ($provider === PaymentProvider::Midtrans->value && empty($update['midtrans_server_key']) && $serverKey) {
                $update['midtrans_is_production'] = $isProduction;
                $update['midtrans_merchant_id'] = $merchantId;
                $update['midtrans_client_key'] = $clientKey;
                $update['midtrans_server_key'] = $serverKey;
            }

            if ($provider === PaymentProvider::Xendit->value && empty($update['xendit_secret_key']) && $serverKey) {
                $update['xendit_is_production'] = $isProduction;
                $update['xendit_merchant_id'] = $merchantId;
                $update['xendit_callback_token'] = $clientKey;
                $update['xendit_secret_key'] = $serverKey;
            }

            if ($update !== []) {
                DB::table('payment_settings')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'midtrans_is_production',
                'midtrans_merchant_id',
                'midtrans_client_key',
                'midtrans_server_key',
                'xendit_is_production',
                'xendit_merchant_id',
                'xendit_callback_token',
                'xendit_secret_key',
            ]);
        });
    }
};
