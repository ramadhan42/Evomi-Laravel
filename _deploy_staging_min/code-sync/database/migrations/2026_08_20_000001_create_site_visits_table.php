<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_key')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_type', 20)->default('guest')->index(); // guest|user
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('country', 100)->nullable();
            $table->string('country_code', 8)->nullable()->index();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('full_url', 1000)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device', 40)->nullable();
            $table->string('browser', 80)->nullable();
            $table->string('platform', 80)->nullable();
            $table->timestamp('visited_at')->useCurrent()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->index(['visitor_type', 'visited_at']);
            $table->index(['visitor_key', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
