<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Provider;
use App\Models\VerificationMethod;
use Database\Seeders\Concerns\InteractsWithSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

class ProviderOfferSeeder extends Seeder
{
    use InteractsWithSeedData;

    public function run(): void
    {
        $data = $this->seedData();

        $categories = Category::pluck('id', 'slug');
        $methods = VerificationMethod::pluck('id', 'key');
        $models = AiModel::pluck('id', 'slug');
        $modelLinks = collect($data['offer_model_links'])->groupBy('offer_provider');

        foreach ($data['providers'] as $row) {
            $provider = Provider::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'category_id' => $this->resolveId($categories, $row['category'], 'category', $row['slug']),
                    'canonical_url' => $row['canonical_url'],
                    'referral_url' => $row['referral_url'] ?? null,
                    'status' => Provider::STATUS_ACTIVE,
                ]
            );

            $offerData = $row['offer'];

            $offer = Offer::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'provider_id' => $provider->id,
                    'title_fa' => $offerData['title_fa'],
                    'description_fa' => $offerData['notes_fa'] ?? null,
                    'free_tier_type' => $offerData['free_tier_type'],
                    'status' => Offer::STATUS_ACTIVE,
                    'currency' => 'USD',
                    'credits_amount' => $offerData['credits_amount'] ?? null,
                    'credits_unit' => $offerData['credits_unit'] ?? null,
                    'raw_note_fa' => $offerData['credits_raw'] ?? null,
                    'access_types' => $offerData['access_types'],
                    'last_verified_at' => now(),
                    'published_at' => now(),
                ]
            );

            $offer->verificationMethods()->sync(
                $this->resolveIds($methods, $offerData['verification'] ?? [], 'verification method', $row['slug'])
            );

            $offer->aiModels()->sync(
                $this->resolveIds(
                    $models,
                    collect($modelLinks->get($row['slug'], []))->pluck('model')->all(),
                    'ai model',
                    $row['slug']
                )
            );
        }
    }

    /**
     * @param  Collection<string, int>  $lookup
     */
    private function resolveId(Collection $lookup, string $key, string $label, string $context): int
    {
        $id = $lookup[$key] ?? null;

        if ($id === null) {
            throw new RuntimeException("Unknown {$label} [{$key}] for offer provider [{$context}]");
        }

        return $id;
    }

    /**
     * @param  Collection<string, int>  $lookup
     * @param  array<int, string>  $keys
     * @return array<int, int>
     */
    private function resolveIds(Collection $lookup, array $keys, string $label, string $context): array
    {
        return array_map(fn (string $key) => $this->resolveId($lookup, $key, $label, $context), $keys);
    }
}
