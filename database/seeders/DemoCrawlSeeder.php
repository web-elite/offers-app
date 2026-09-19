<?php

namespace Database\Seeders;

use App\Models\CrawlResult;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class DemoCrawlSeeder extends Seeder
{
    /**
     * Demo "free models today" data so the freshness UI has something to show.
     *
     * @var array<string, array<int, string>>
     */
    private const DEMO_FREE_MODELS = [
        'vsllm' => ['glm-4.7-flash-free'],
        'duck-ai' => ['gpt-5.6'],
    ];

    public function run(): void
    {
        foreach (self::DEMO_FREE_MODELS as $providerSlug => $freeModels) {
            $provider = Provider::where('slug', $providerSlug)->firstOrFail();

            $startedAt = now()->subMinutes(3);

            $source = CrawlSource::updateOrCreate(
                ['provider_id' => $provider->id, 'source_type' => CrawlSource::TYPE_MODELS],
                [
                    'url' => $provider->canonical_url,
                    'parser_hint' => ['free_models' => 'list of models offered without charge'],
                    'is_enabled' => true,
                    'crawl_frequency_hours' => 24,
                ]
            );

            $run = CrawlRun::create([
                'crawl_source_id' => $source->id,
                'started_at' => $startedAt,
                'finished_at' => $startedAt->copy()->addSeconds(1),
                'http_status' => 200,
                'status' => CrawlRun::STATUS_OK,
                'duration_ms' => 842,
            ]);

            CrawlResult::create([
                'crawl_run_id' => $run->id,
                'extracted_json' => [
                    'source_url' => $provider->canonical_url,
                    'free_models' => $freeModels,
                ],
                'free_models' => $freeModels,
                'requires_card' => false,
                'requires_phone' => false,
                'evidence_text' => 'Demo seed evidence: free models listed by the provider.',
            ]);

            $provider->offers()->update([
                'last_crawl_at' => $startedAt,
                'next_crawl_at' => $startedAt->copy()->addDay(),
            ]);
        }
    }
}
