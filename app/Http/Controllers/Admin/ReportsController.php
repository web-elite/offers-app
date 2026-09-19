<?php

namespace App\Http\Controllers\Admin;

use App\Models\Offer;
use App\Models\AuditLog;
use App\Models\OfferReport;
use Illuminate\Http\Request;
use App\Models\AnalyticsEvent;
use App\Models\UserSubmission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    /**
     * Analytics overview: traffic, top offers, event breakdowns, audit trail.
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $days = min((int) $request->query('days', 30), 365);
        $from = now()->subDays($days)->startOfDay();

        $totalEvents = AnalyticsEvent::where('created_at', '>=', $from)->count();

        // Event breakdown by type
        $eventCounts = AnalyticsEvent::query()
            ->where('created_at', '>=', $from)
            ->groupBy('event_type')
            ->select('event_type', DB::raw('count(*) as cnt'))
            ->pluck('cnt', 'event_type')
            ->all();

        // Top offers by total engagement (views + outbound clicks + QR scans)
        $topOfferRows = AnalyticsEvent::query()
            ->where('created_at', '>=', $from)
            ->whereNotNull('offer_id')
            ->whereIn('event_type', ['offer_view', 'outbound_click', 'qr_scan'])
            ->groupBy('offer_id')
            ->select(
                'offer_id',
                DB::raw("sum(case when event_type = 'offer_view' then 1 else 0 end) as views"),
                DB::raw("sum(case when event_type = 'outbound_click' then 1 else 0 end) as clicks"),
                DB::raw("sum(case when event_type = 'qr_scan' then 1 else 0 end) as qr"),
                DB::raw('count(*) as total'),
            )
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $offer = Offer::find($row->offer_id);
                return [
                    'offer'  => $offer,
                    'slug'   => $offer?->slug,
                    'title'  => $offer?->title_fa,
                    'views'  => (int) $row->views,
                    'clicks' => (int) $row->clicks,
                    'qr'     => (int) $row->qr,
                    'total'  => (int) $row->total,
                ];
            })
            ->filter(fn ($row) => $row['offer'] !== null)
            ->values();

        // Crawler pipeline: success/failure rate in the window
        $crawlerRuns = \App\Models\CrawlRun::query()->where('started_at', '>=', $from)->get();
        $crawlerOk   = $crawlerRuns->filter(fn ($r) => $r->status === \App\Models\CrawlRun::STATUS_OK)->count();
        $crawlerFail = $crawlerRuns->count() - $crawlerOk;
        $crawlerRate = $crawlerRuns->isNotEmpty()
            ? round($crawlerOk / $crawlerRuns->count() * 100, 1)
            : 0;

        // Audit log: most recent admin actions
        $recentAudits = AuditLog::query()
            ->latest('created_at')
            ->limit(15)
            ->get();

        // Pending moderation queue
        $pendingReports     = OfferReport::where('status', 'pending')->count();
        $pendingSubmissions = UserSubmission::where('status', 'pending')->count();

        // OfferVersion source mix in window
        $sourceMix = \App\Models\OfferVersion::query()
            ->where('created_at', '>=', $from)
            ->groupBy('source')
            ->select('source', DB::raw('count(*) as cnt'))
            ->pluck('cnt', 'source')
            ->all();

        return view('admin.reports', [
            'days'             => $days,
            'from'             => $from,
            'totalEvents'      => $totalEvents,
            'eventCounts'      => $eventCounts,
            'topOffers'        => $topOfferRows,
            'crawlerRuns'      => $crawlerRuns,
            'crawlerOk'        => $crawlerOk,
            'crawlerFail'      => $crawlerFail,
            'crawlerRate'      => $crawlerRate,
            'recentAudits'     => $recentAudits,
            'pendingReports'   => $pendingReports,
            'pendingSubmissions' => $pendingSubmissions,
            'sourceMix'        => $sourceMix,
        ]);
    }

    /**
     * Stream all analytics events in the window as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $days = min((int) $request->query('days', 30), 365);
        $from = now()->subDays($days)->startOfDay();

        return response()->streamDownload(function () use ($from) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens the CSV correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id', 'event_type', 'offer_slug', 'offer_title',
                'payload', 'created_at',
            ]);

            AnalyticsEvent::query()
                ->where('created_at', '>=', $from)
                ->with('offer')
                ->orderBy('created_at')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->id,
                            $row->event_type,
                            $row->offer?->slug ?? '',
                            $row->offer?->title_fa ?? '',
                            $row->payload ? json_encode($row->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                            $row->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($out);
        }, 'analytics-events-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
