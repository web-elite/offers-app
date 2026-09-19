<?php

namespace Database\Seeders;

use App\Support\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            VerificationMethodSeeder::class,
            ModelCreatorSeeder::class,
            AiModelSeeder::class,
            ProviderOfferSeeder::class,
            DemoCrawlSeeder::class,
            TagSeeder::class,
            SettingsSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
