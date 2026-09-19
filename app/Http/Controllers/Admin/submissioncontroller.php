<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Provider;
use App\Models\UserSubmission;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function index(Request $request): ViewContract
    {
        $this->authorizeModerator();

        $status = (string) $request->query('status', UserSubmission::STATUS_PENDING);

        $submissions = UserSubmission::query()
            ->when(in_array($status, UserSubmission::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.submissions.index', [
            'submissions' => $submissions,
            'status' => $status,
            'pendingCount' => UserSubmission::query()->where('status', UserSubmission::STATUS_PENDING)->count(),
        ]);
    }

    /** Approve: create the provider + a draft offer awaiting review. */
    public function approve(int $id): RedirectResponse
    {
        $this->authorizeModerator();

        $submission = UserSubmission::query()->findOrFail($id);

        if ($submission->status === UserSubmission::STATUS_APPROVED) {
            return back()->with('error', 'این پیشنهاد قبلاً تأیید شده است.');
        }

        $slug = $this->uniqueSlug($submission->provider_name);

        $categoryId = \App\Models\Category::query()->orderBy('sort_order')->value('id');

        $provider = Provider::create([
            'slug' => $slug,
            'name' => $submission->provider_name,
            'category_id' => $categoryId,
            'canonical_url' => $submission->url,
            'description_fa' => $submission->offer_note_fa,
            'status' => Provider::STATUS_ACTIVE,
        ]);

        Offer::create([
            'provider_id' => $provider->id,
            'slug' => $slug,
            'title_fa' => 'آفر پیشنهادی کاربر — '.$submission->provider_name,
            'description_fa' => $submission->offer_note_fa,
            'free_tier_type' => Offer::TIER_UNKNOWN,
            'status' => Offer::STATUS_MANUAL_REVIEW,
            'access_types' => [],
            'last_verified_at' => null,
        ]);

        $submission->forceFill(['status' => UserSubmission::STATUS_APPROVED])->save();

        return back()->with('status', 'پیشنهاد تأیید شد و آفر پیش‌نویس ساخته شد (در انتظار بازبینی).');
    }

    public function reject(int $id): RedirectResponse
    {
        $this->authorizeModerator();

        UserSubmission::query()->findOrFail($id)->forceFill(['status' => UserSubmission::STATUS_REJECTED])->save();

        return back()->with('status', 'پیشنهاد رد شد.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'provider';
        }

        $slug = $base;
        $i = 2;

        while (Provider::query()->where('slug', $slug)->exists() || Offer::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function authorizeModerator(): void
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null && $admin->hasAbility('moderate'), 403);
    }
}
