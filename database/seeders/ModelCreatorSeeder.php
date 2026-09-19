<?php

namespace Database\Seeders;

use App\Models\ModelCreator;
use Database\Seeders\Concerns\InteractsWithSeedData;
use Illuminate\Database\Seeder;

class ModelCreatorSeeder extends Seeder
{
    use InteractsWithSeedData;

    public function run(): void
    {
        foreach ($this->seedData()['model_creators'] as $row) {
            ModelCreator::updateOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name']]
            );
        }
    }
}
