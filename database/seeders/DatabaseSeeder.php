<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ResetUserDataSeeder::class,
            EvomiCatalogSeeder::class,
            ProductSeeder::class,
            QuizSeeder::class,
            CmsSeeder::class,
            LocaleSeeder::class,
            ShopCmsSeeder::class,
            PaymentSettingSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
