<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use Illuminate\Contracts\View\View as ViewContract;

class CrawlerController extends Controller
{
    /** Crawler dashboard. The full engine lands in the next phase (D2). */
    public function index(): ViewContract
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null && $admin->hasAbility('manageCrawlers'), 403);

        $sources = CrawlSource::query()
            ->with(['provider', 'crawlRuns' => fn ($q) => $q->latest('started_at')->limit(1)])
            ->orderBy('id')
            ->paginate(20);

        return view('admin.crawler.index', [
            'sources' => $sources,
            'runs' => CrawlRun::query()->with('crawlSource.provider')->latest('started_at')->limit(30)->get(),
            'stats' => [
                'sources' => CrawlSource::count(),
                'enabled' => CrawlSource::where('is_enabled', true)->count(),
                'ok' => CrawlRun::where('status', CrawlRun::STATUS_OK)->count(),
                'failed' => CrawlRun::where('status', '!=', CrawlRun::STATUS_OK)->count(),
            ],
            'engineReady' => false,
        ]);
    }
}
