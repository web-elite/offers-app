<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Tag;
use Database\Seeders\Concerns\InteractsWithSeedData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    use InteractsWithSeedData;

    public function run(): void
    {
        foreach ($this->seedData()['tags'] as $row) {
            $tag = Tag::updateOrCreate(
                ['slug' => $row['slug']],
                ['name_fa' => $row['name_fa']]
            );

            $tierMatches = $row['match'] ?? [];
            $accessMatches = $row['match_access'] ?? [];

            if ($tierMatches === [] && $accessMatches === []) {
                continue;
            }

            $query = Offer::query();

            if ($tierMatches !== []) {
                $query->whereIn('free_tier_type', $tierMatches);
            }

            if ($accessMatches !== []) {
                $query->where(function (Builder $q) use ($accessMatches) {
                    foreach ($accessMatches as $access) {
                        $q->orWhereJsonContains('access_types', $access);
                    }
                });
            }

            $tag->offers()->sync($query->pluck('id')->all());
        }
    }
}
