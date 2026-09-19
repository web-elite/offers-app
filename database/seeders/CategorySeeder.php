<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Concerns\InteractsWithSeedData;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use InteractsWithSeedData;

    public function run(): void
    {
        foreach ($this->seedData()['categories'] as $row) {
            Category::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name_fa' => $row['name_fa'],
                    'name_en' => $row['name_en'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                    'sort_order' => $row['sort_order'] ?? 0,
                ]
            );
        }
    }
}
