<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OfferReport;
use App\Models\OfferVersion;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): ViewContract
    {
        $this->authorizeModerator();

        $status = (string) $request->query('status', OfferReport::STATUS_PENDING);

        $reports = OfferReport::query()
            ->with('offer.provider')
            ->when(in_array($status, OfferReport::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status,
            'pendingCount' => OfferReport::query()->where('status', OfferReport::STATUS_PENDING)->count(),
        ]);
    }

    public function resolve(int $id): RedirectResponse
    {
        $this->authorizeModerator();

        $report = OfferReport::query()->with('offer')->findOrFail($id);
        $report->forceFill(['status' => OfferReport::STATUS_RESOLVED])->save();

        $offer = $report->offer;

        if ($offer !== null) {
            $old = $offer->status;

            $offer->forceFill(['status' => Offer::STATUS_EXPIRED])->save();

            OfferVersion::create([
                'offer_id' => $offer->id,
                'old_data' => ['status' => $old],
                'new_data' => ['status' => Offer::STATUS_EXPIRED, 'reason' => 'گزارش کاربر: '.$report->report_type],
                'detected_at' => now(),
                'source' => OfferVersion::SOURCE_USER_REPORT,
            ]);
        }

        return back()->with('status', 'گزارش تأیید شد و آفر منقضی علامت خورد.');
    }

    public function reject(int $id): RedirectResponse
    {
        $this->authorizeModerator();

        OfferReport::query()->findOrFail($id)->forceFill(['status' => OfferReport::STATUS_REJECTED])->save();

        return back()->with('status', 'گزارش رد شد.');
    }

    private function authorizeModerator(): void
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null && $admin->hasAbility('moderate'), 403);
    }
}
