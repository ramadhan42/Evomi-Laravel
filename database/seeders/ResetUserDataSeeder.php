<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Cart;
use App\Models\ContactMessage;
use App\Models\ContactReply;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Models\Product;
use App\Models\ShoppingNeed;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\UserQuizAnswer;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Bersihkan data transaksi + semua user, lalu siapkan ulang admin login saja.
 */
class ResetUserDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->truncateIfExists('contact_replies');
        $this->truncateIfExists('contact_messages');
        $this->truncateIfExists('order_trackings');
        $this->truncateIfExists('orders');
        $this->truncateIfExists('carts');
        $this->truncateIfExists('wishlists');
        $this->truncateIfExists('user_quiz_answers');
        $this->truncateIfExists('quiz_attempts');
        $this->truncateIfExists('shopping_needs');
        $this->truncateIfExists('personal_access_tokens');
        $this->truncateIfExists('sessions');
        $this->truncateIfExists('password_reset_tokens');
        $this->truncateIfExists('subscribers');

        // Model deletes as safety if truncate skipped
        ContactReply::query()->delete();
        ContactMessage::query()->delete();
        OrderTracking::query()->delete();
        Order::query()->delete();
        Cart::query()->delete();
        Wishlist::query()->delete();
        UserQuizAnswer::query()->delete();
        ShoppingNeed::query()->delete();
        PersonalAccessToken::query()->delete();
        Subscriber::query()->delete();

        User::query()->delete();

        Schema::enableForeignKeyConstraints();

        $this->seedFreshAdmin();

        $this->command?->info('User data dibersihkan. Admin login di-reset dari .env.');
    }

    private function truncateIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->truncate();
    }

    private function seedFreshAdmin(): void
    {
        $email = config('evomi.development_admin.email') ?: 'admin@evomi.id';
        $password = config('evomi.development_admin.password') ?: 'password';
        $name = config('evomi.development_admin.name') ?: 'Evomi Admin';

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'nama_lengkap' => $name,
            'email_verified_at' => now(),
            'last_login_at' => null,
            'last_seen_at' => null,
        ]);

        $this->command?->info("Admin dibuat ulang: {$email}");
    }
}
