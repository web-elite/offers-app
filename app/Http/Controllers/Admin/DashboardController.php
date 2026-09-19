<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use App\Models\Offer;
use App\Models\OfferReport;
use App\Models\Provider;
use App\Models\UserSubmission;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'providersCount' => Provider::count(),
            'offersCount' => Offer::count(),
            'activeOffersCount' => Offer::where('status', 'active')->count(),
            'pendingReportsCount' => OfferReport::where('status', 'pending')->count(),
            'pendingSubmissionsCount' => UserSubmission::where('status', 'pending')->count(),
            'lastCrawlRun' => CrawlRun::with('crawlSource.provider')->latest('started_at')->first(),
        ]);
    }
}
