<?php

namespace Database\Seeders;

use App\Models\VerificationMethod;
use Database\Seeders\Concerns\InteractsWithSeedData;
use Illuminate\Database\Seeder;

class VerificationMethodSeeder extends Seeder
{
    use InteractsWithSeedData;

    public function run(): void
    {
        foreach ($this->seedData()['verification_methods'] as $row) {
            VerificationMethod::updateOrCreate(
                ['key' => $row['key']],
                ['label_fa' => $row['label_fa']]
            );
        }
    }
}
