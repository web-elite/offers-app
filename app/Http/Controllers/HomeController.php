<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Offer;
use App\Models\AiModel;
use App\Models\CrawlRun;
use App\Support\Settings;
use Illuminate\View\View;
use App\Support\Analytics;
use App\Models\CrawlResult;
use App\Models\OfferReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\UserSubmission;
use App\Support\EffectiveOffer;
use App\Support\OfferPresenter;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Models\VerificationMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    /** Query params that make up an active filter session. */
    private const FILTER_PARAMS = [
        'q', 'free', 'model', 'verification', 'no_verification',
        'no_card', 'no_phone', 'access', 'tier', 'sort',
    ];

    private const SORTS = ['fresh', 'credits'];

    /** One-pager with filter pipeline + discovery strips + ad slots. */
    public function index(Request $request): View
    {
        $gridQuery = $this->offerQuery();
        $this->applyFilters($gridQuery, $request);
        $this->applySort($gridQuery, in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'fresh');

        $offers = $gridQuery->get();
        EffectiveOffer::hydrate($offers);

        $filtered = collect(self::FILTER_PARAMS)
            ->contains(fn (string $param) => $request->filled($param));

        $viewData = [
            'offers' => $offers,
            'crawlMap' => $this->crawlMapFor($offers),
            'showStrips' => ! $filtered,
            'freshOffers' => collect(),
            'dailyOffers' => collect(),
            'aiModels' => AiModel::query()->where('is_active', true)->orderBy('name')->get(),
            'verificationMethods' => VerificationMethod::query()
                ->whereIn('key', OfferPresenter::REAL_VERIFICATION_KEYS)
                ->orderBy('id')
                ->get(),
            'activeChips' => $this->activeChips($request),
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'fresh',
            'adSlots' => $this->adSlots($offers),
            'afterGridAd' => $this->afterGridAd(),
        ];

        if (! $filtered) {
            $viewData['freshOffers'] = $freshOffers = $this->offerQuery()
                ->whereNotNull('last_verified_at')
                ->orderByDesc('last_verified_at')
                ->limit(8)
                ->get();

            $viewData['dailyOffers'] = $dailyOffers = $this->offerQuery()
                ->freeTierType(Offer::TIER_DAILY_RESET)
                ->orderByDesc('last_verified_at')
                ->limit(8)
                ->get();

            EffectiveOffer::hydrate($freshOffers->merge($dailyOffers));
        }

        return view('public.home', $viewData);
    }

    /** Full detail page with SEO meta. */
    public function show(string $slug): View
    {
        $offer = $this->offerQuery()
            ->where('slug', $slug)
            ->firstOrFail();

        EffectiveOffer::hydrate(collect([$offer]));

        $crawlMap = $this->crawlMapFor(collect([$offer]));

        return view('public.offers.show', [
            'offer' => $offer,
            'crawl' => $crawlMap[$offer->provider_id] ?? null,
        ]);
    }

    /** Store an offer report (ajax-able, csrf). */
    public function report(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $offer = Offer::query()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'report_type' => ['required', Rule::in(OfferReport::TYPES)],
            'details_fa' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = $offer->reports()->create([
            'report_type' => $data['report_type'],
            'details_fa' => $data['details_fa'] ?? null,
            'status' => OfferReport::STATUS_PENDING,
        ]);

        $message = 'ممنون! گزارش شما ثبت شد و توسط مدیران بررسی می‌شود.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'id' => $report->id]);
        }

        return back()->with('report_success', $message);
    }

    /** Store a user submitted provider/offer suggestion (honeypot protected). */
    public function submit(Request $request): JsonResponse|RedirectResponse
    {
        // Honeypot: humans never fill the hidden field. Pretend success, store nothing.
        if ($request->filled('website')) {
            return $this->submitDone('ممنون! پیشنهاد شما ثبت شد.');
        }

        $data = $request->validate([
            'provider_name' => ['required', 'string', 'max:191'],
            'url' => ['required', 'url', 'max:500'],
            'offer_note_fa' => ['nullable', 'string', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:191'],
        ]);

        UserSubmission::create([
            'provider_name' => $data['provider_name'],
            'url' => $data['url'],
            'offer_note_fa' => $data['offer_note_fa'] ?? null,
            'contact' => $data['contact'] ?? null,
            'status' => UserSubmission::STATUS_PENDING,
        ]);

        return $this->submitDone('ممنون! پیشنهاد شما ثبت شد و بعد از بررسی منتشر می‌شود.');
    }

    private function submitDone(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()->to('/#submit')->with('submit_success', $message);
    }

    /** Outbound click tracking + 302 to referral (or canonical) URL. */
    public function go(Request $request, string $slug): RedirectResponse
    {
        $offer = Offer::query()->with('provider')->where('slug', $slug)->firstOrFail();

        Analytics::record(Analytics::EVENT_OUTBOUND_CLICK, $offer->id, [
            'referer' => $request->header('referer'),
        ]);

        $target = $offer->provider?->referral_url ?: $offer->provider?->canonical_url;

        abort_if($target === null || $target === '', 404);

        return redirect()->away($target, 302);
    }

    /** Ad click tracking + 302 to the ad target URL. */
    public function adClick(int $id): RedirectResponse
    {
        $ad = Ad::query()->findOrFail($id);

        $ad->increment('clicks');

        Analytics::record(Analytics::EVENT_AD_CLICK, null, ['ad_id' => $ad->id]);

        abort_if($ad->target_url === '', 404);

        return redirect()->away($ad->target_url, 302);
    }

    /** JS analytics beacons: offer_view / qr_scan / filter_use. */
    public function analyticsEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(Analytics::EVENT_TYPES)],
            'offer_id' => ['nullable', 'integer', Rule::exists('offers', 'id')],
            'payload' => ['nullable', 'array'],
        ]);

        $event = Analytics::record(
            $data['type'],
            $data['offer_id'] ?? null,
            $data['payload'] ?? [],
        );

        return response()->json(['ok' => true, 'id' => $event->id]);
    }

    /** XML sitemap: home + all offer detail pages. */
    public function sitemap(): Response
    {
        $entries = [
            ['loc' => URL::to('/'), 'lastmod' => null, 'changefreq' => 'daily', 'priority' => '1.0'],
        ];

        Offer::query()->orderBy('slug')->get(['slug', 'last_verified_at', 'updated_at'])
            ->each(function (Offer $offer) use (&$entries): void {
                $entries[] = [
                    'loc' => URL::route('public.offers.show', $offer->slug),
                    'lastmod' => ($offer->last_verified_at ?? $offer->updated_at)?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>".htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8')."</loc>\n";

            if ($entry['lastmod'] !== null) {
                $xml .= "\t\t<lastmod>".$entry['lastmod']."</lastmod>\n";
            }

            $xml .= "\t\t<changefreq>".$entry['changefreq']."</changefreq>\n";
            $xml .= "\t\t<priority>".$entry['priority']."</priority>\n";
            $xml .= "\t</url>\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    // ------------------------------------------------------------------

    /**
     * Ads inserted between cards: one ad after every N rendered cards.
     *
     * @param  Collection<int, Offer>  $offers
     * @return array<int, Ad> keyed by 1-based card iteration the ad follows
     */
    private function adSlots(Collection $offers): array
    {
        if (! Settings::bool(Settings::ADS_ENABLED)) {
            return [];
        }

        $interval = max(1, Settings::int(Settings::ADS_INTERVAL, 4));
        $count = $offers->count();

        if ($count <= $interval) {
            return [];
        }

        $ads = Ad::query()
            ->activeNow()
            ->position(Ad::POSITION_BETWEEN_CARDS)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        if ($ads->isEmpty()) {
            return [];
        }

        $slots = [];
        $adIndex = 0;

        for ($position = $interval; $position < $count; $position += $interval) {
            $slots[$position] = $ads[$adIndex % $ads->count()];
            $adIndex++;
        }

        return $slots;
    }

    private function afterGridAd(): ?Ad
    {
        if (! Settings::bool(Settings::ADS_ENABLED)) {
            return null;
        }

        return Ad::query()
            ->activeNow()
            ->position(Ad::POSITION_AFTER_GRID)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->first();
    }

    /** Base offer query with all relations needed by the public UI (no N+1). */
    private function offerQuery(): Builder
    {
        return Offer::query()->with([
            'provider.category',
            'verificationMethods',
            'aiModels',
            'tags',
            'versions',
        ]);
    }

    /** Compose all filter params on top of one query using the Step B scopes. */
    private function applyFilters(Builder $query, Request $request): void
    {
        $query->search(trim((string) $request->query('q', '')));

        if ($request->boolean('free')) {
            $query->freeOnly();
        }

        $models = $this->strings($request->query('model'));
        if ($models !== []) {
            $query->whereHas('aiModels', fn (Builder $m) => $m->whereIn('slug', $models));
        }

        $verification = array_values(array_intersect(
            $this->strings($request->query('verification')),
            OfferPresenter::REAL_VERIFICATION_KEYS,
        ));
        if ($verification !== []) {
            $query->hasVerification($verification);
        }

        if ($request->boolean('no_verification')) {
            $query->withoutVerification(OfferPresenter::REAL_VERIFICATION_KEYS);
        }

        if ($request->boolean('no_card')) {
            $query->withoutVerification(['card']);
        }

        if ($request->boolean('no_phone')) {
            $query->withoutVerification(['phone']);
        }

        $access = array_values(array_intersect(
            $this->strings($request->query('access')),
            [Offer::ACCESS_API, Offer::ACCESS_WEB, Offer::ACCESS_CHAT_UI, Offer::ACCESS_IDE, Offer::ACCESS_TELEGRAM_BOT],
        ));
        if ($access !== []) {
            // any-of semantics across the selected access types
            $query->where(function (Builder $outer) use ($access): void {
                foreach ($access as $index => $type) {
                    $index === 0
                        ? $outer->accessType($type)
                        : $outer->orWhere(fn (Builder $inner) => $inner->accessType($type));
                }
            });
        }

        $tiers = array_values(array_intersect(
            $this->strings($request->query('tier')),
            [
                Offer::TIER_FOREVER_FREE, Offer::TIER_DAILY_RESET, Offer::TIER_MONTHLY_CREDIT,
                Offer::TIER_SIGNUP_BONUS, Offer::TIER_REFERRAL_BONUS, Offer::TIER_TRIAL,
                Offer::TIER_FREE_MODELS, Offer::TIER_FREE_CREDITS,
            ],
        ));
        if ($tiers !== []) {
            $query->whereIn('free_tier_type', $tiers);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        if ($sort === 'credits') {
            // nulls last
            $query->orderByRaw('CASE WHEN credits_amount IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('credits_amount')
                ->orderByDesc('id');

            return;
        }

        $query->orderByDesc('last_verified_at')->orderByDesc('id');
    }

    /**
     * Latest ok crawl result per provider — one query, keyed map (no N+1).
     *
     * @param  Collection<int, Offer>  $offers
     * @return array<int, array{models: array<int, string>, crawled_at: \Illuminate\Support\Carbon|null, status: string}>
     */
    private function crawlMapFor(Collection $offers): array
    {
        $providerIds = $offers->pluck('provider_id')->filter()->unique()->values();

        if ($providerIds->isEmpty()) {
            return [];
        }

        $results = CrawlResult::query()
            ->select('crawl_results.*')
            ->join('crawl_runs', 'crawl_runs.id', '=', 'crawl_results.crawl_run_id')
            ->join('crawl_sources', 'crawl_sources.id', '=', 'crawl_runs.crawl_source_id')
            ->with('crawlRun.crawlSource')
            ->whereIn('crawl_sources.provider_id', $providerIds->all())
            ->where('crawl_runs.status', CrawlRun::STATUS_OK)
            ->orderByDesc('crawl_runs.started_at')
            ->get();

        $map = [];

        foreach ($results as $result) {
            $providerId = $result->crawlRun?->crawlSource?->provider_id;

            if ($providerId === null || isset($map[$providerId])) {
                continue; // keep only the latest ok run per provider
            }

            $map[$providerId] = [
                'models' => $result->free_models ?? [],
                'crawled_at' => $result->crawlRun->started_at,
                'status' => $result->crawlRun->status,
            ];
        }

        return $map;
    }

    /** Chips for active filters, each linking to the URL without that value. */
    private function activeChips(Request $request): array
    {
        $chips = [];
        $current = $request->query();

        $remove = function (string $param, ?string $value = null) use ($current): string {
            $query = $current;

            if ($value === null) {
                unset($query[$param]);
            } else {
                $query[$param] = array_values(array_diff((array) ($query[$param] ?? []), [$value]));

                if ($query[$param] === []) {
                    unset($query[$param]);
                }
            }

            return $query === [] ? URL::to('/') : URL::to('/?'.http_build_query($query));
        };

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $chips[] = ['label' => 'جستجو: '.$q, 'url' => $remove('q')];
        }

        if ($request->boolean('free')) {
            $chips[] = ['label' => 'فقط کاملاً رایگان', 'url' => $remove('free')];
        }

        if ($request->boolean('no_verification')) {
            $chips[] = ['label' => 'بدون نیاز به تأیید', 'url' => $remove('no_verification')];
        }

        if ($request->boolean('no_card')) {
            $chips[] = ['label' => 'بدون کارت بانکی', 'url' => $remove('no_card')];
        }

        if ($request->boolean('no_phone')) {
            $chips[] = ['label' => 'بدون شماره موبایل', 'url' => $remove('no_phone')];
        }

        foreach ($this->strings($request->query('model')) as $slug) {
            $name = AiModel::query()->where('slug', $slug)->value('name');
            $chips[] = ['label' => 'مدل: '.($name ?? $slug), 'url' => $remove('model', $slug)];
        }

        foreach ($this->strings($request->query('verification')) as $key) {
            $label = VerificationMethod::query()->where('key', $key)->value('label_fa');
            $chips[] = ['label' => 'تأیید: '.($label ?? $key), 'url' => $remove('verification', $key)];
        }

        foreach ($this->strings($request->query('access')) as $type) {
            $chips[] = ['label' => 'دسترسی: '.(OfferPresenter::ACCESS_LABELS[$type] ?? $type), 'url' => $remove('access', $type)];
        }

        foreach ($this->strings($request->query('tier')) as $tier) {
            $chips[] = ['label' => (OfferPresenter::TIER_LABELS[$tier] ?? $tier), 'url' => $remove('tier', $tier)];
        }

        if ($request->query('sort') === 'credits') {
            $chips[] = ['label' => 'مرتب‌سازی: بیشترین اعتبار', 'url' => $remove('sort')];
        }

        return $chips;
    }

    /**
     * @param  mixed  $values
     * @return array<int, string>
     */
    private function strings(mixed $values): array
    {
        if ($values === null) {
            return [];
        }

        $values = is_array($values) ? $values : [$values];

        $values = array_map(fn ($value) => trim((string) $value), $values);
        $values = array_values(array_unique(array_filter($values, fn (string $value) => $value !== '')));

        return array_slice($values, 0, 50);
    }
}
