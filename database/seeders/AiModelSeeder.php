<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\ModelCreator;
use Database\Seeders\Concerns\InteractsWithSeedData;
use Illuminate\Database\Seeder;
use RuntimeException;

class AiModelSeeder extends Seeder
{
    use InteractsWithSeedData;

    public function run(): void
    {
        $creators = ModelCreator::pluck('id', 'slug');

        foreach ($this->seedData()['ai_models'] as $row) {
            $creatorId = $creators[$row['creator']] ?? null;

            if ($creatorId === null) {
                throw new RuntimeException("Unknown model creator [{$row['creator']}] for model [{$row['slug']}]");
            }

            AiModel::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'model_creator_id' => $creatorId,
                    // context_window/capabilities stay null until crawler enrichment.
                    'context_window' => null,
                    'capabilities' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
