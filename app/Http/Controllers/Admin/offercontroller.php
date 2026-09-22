<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;
use App\Models\Offer;
use App\Models\AiModel;
use App\Models\Provider;
use App\Models\OfferVersion;
use Illuminate\Http\Request;
use App\Models\AdminOverride;
use App\Support\EffectiveOffer;
use Illuminate\Validation\Rule;
use App\Models\VerificationMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;

class OfferController extends CrudController
{
    protected string $modelClass = Offer::class;
    protected string $title = 'آفرها';
    protected string $singular = 'آفر';
    protected string $route = 'admin.offers';
    protected array $searchable = ['title_fa', 'slug', 'description_fa'];
    protected array $with = ['provider'];
    protected array $withCount = ['reports'];
    protected string $orderColumn = 'id';
    protected string $orderDirection = 'desc';
    protected ?string $extrasView = 'admin.offers.overrides';

    protected array $syncRelations = [
        'verificationMethods' => 'verification_method_ids',
        'aiModels' => 'ai_model_ids',
        'tags' => 'tag_ids',
    ];

    protected array $indexFilters = [
        ['name' => 'status', 'label' => 'وضعیت', 'column' => 'status', 'options' => [
            Offer::STATUS_ACTIVE => 'فعال',
            Offer::STATUS_EXPIRED => 'منقضی',
            Offer::STATUS_TEMPORARILY_UNAVAILABLE => 'موقتاً در دسترس نیست',
            Offer::STATUS_REQUIRES_VERIFICATION => 'نیازمند تأیید هویت',
            Offer::STATUS_MANUAL_REVIEW => 'در انتظار بازبینی',
            Offer::STATUS_UNKNOWN => 'نامشخص',
        ]],
        ['name' => 'free_tier_type', 'label' => 'نوع آفر', 'column' => 'free_tier_type', 'options' => [
            Offer::TIER_FOREVER_FREE => 'رایگان دائمی',
            Offer::TIER_DAILY_RESET => 'ریست روزانه',
            Offer::TIER_MONTHLY_CREDIT => 'اعتبار ماهانه',
            Offer::TIER_SIGNUP_BONUS => 'هدیه ثبت‌نام',
            Offer::TIER_REFERRAL_BONUS => 'پاداش معرفی',
            Offer::TIER_TRIAL => 'دوره آزمایشی',
            Offer::TIER_FREE_MODELS => 'مدل‌های رایگان',
            Offer::TIER_FREE_CREDITS => 'اعتبار رایگان',
            Offer::TIER_UNKNOWN => 'نامشخص',
        ]],
        ['name' => 'access', 'label' => 'نوع دسترسی', 'column' => 'access_types', 'type' => 'json', 'options' => [
            Offer::ACCESS_API => 'API',
            Offer::ACCESS_WEB => 'وب',
            Offer::ACCESS_CHAT_UI => 'چت',
            Offer::ACCESS_IDE => 'IDE',
            Offer::ACCESS_TELEGRAM_BOT => 'ربات تلگرام',
        ]],
    ];

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان آفر', 'value' => fn (Offer $o) => $o->title_fa],
            ['label' => 'ارائه‌دهنده', 'value' => fn (Offer $o) => $o->provider?->name],
            ['label' => 'نوع', 'value' => fn (Offer $o) => $o->free_tier_type],
            ['label' => 'اعتبار', 'value' => fn (Offer $o) => $o->credits_amount !== null
                ? number_format((int) $o->credits_amount).' '.($o->credits_unit ?? '')
                : null],
            ['label' => 'وضعیت', 'value' => fn (Offer $o) => $o->status],
            ['label' => 'آخرین بررسی', 'value' => fn (Offer $o) => $o->last_verified_at?->format('Y-m-d H:i')],
            ['label' => 'گزارش', 'value' => fn (Offer $o) => (string) ($o->reports_count ?? 0)],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'provider_id', 'label' => 'ارائه‌دهنده', 'type' => 'select', 'rules' => ['required', 'integer', 'exists:providers,id'], 'options' => fn () => Provider::query()->orderBy('name')->pluck('name', 'id')->all(), 'col' => 6],
        ['name' => 'title_fa', 'label' => 'عنوان آفر', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
        ['name' => 'slug', 'label' => 'شناسه لاتین', 'type' => 'text', 'rules' => ['required', 'string', 'max:191', 'alpha_dash'], 'unique' => ['offers', 'slug'], 'col' => 6],
        ['name' => 'status', 'label' => 'وضعیت آفر', 'type' => 'select', 'rules' => ['required'], 'options' => [
            Offer::STATUS_ACTIVE => 'فعال',
            Offer::STATUS_EXPIRED => 'منقضی',
            Offer::STATUS_TEMPORARILY_UNAVAILABLE => 'موقتاً در دسترس نیست',
            Offer::STATUS_REQUIRES_VERIFICATION => 'نیازمند تأیید هویت',
            Offer::STATUS_MANUAL_REVIEW => 'در انتظار بازبینی',
            Offer::STATUS_UNKNOWN => 'نامشخص',
        ], 'col' => 6],
        ['name' => 'free_tier_type', 'label' => 'نوع آفر', 'type' => 'select', 'rules' => ['required'], 'options' => [
            Offer::TIER_FOREVER_FREE => 'رایگان دائمی',
            Offer::TIER_DAILY_RESET => 'ریست روزانه',
            Offer::TIER_MONTHLY_CREDIT => 'اعتبار ماهانه',
            Offer::TIER_SIGNUP_BONUS => 'هدیه ثبت‌نام',
            Offer::TIER_REFERRAL_BONUS => 'پاداش معرفی',
            Offer::TIER_TRIAL => 'دوره آزمایشی',
            Offer::TIER_FREE_MODELS => 'مدل‌های رایگان',
            Offer::TIER_FREE_CREDITS => 'اعتبار رایگان',
            Offer::TIER_UNKNOWN => 'نامشخص',
        ], 'col' => 6],
        ['name' => 'access_types', 'label' => 'نوع دسترسی', 'type' => 'checkboxgroup', 'col' => 12, 'options' => [
            Offer::ACCESS_API => 'API',
            Offer::ACCESS_WEB => 'وب',
            Offer::ACCESS_CHAT_UI => 'چت',
            Offer::ACCESS_IDE => 'IDE',
            Offer::ACCESS_TELEGRAM_BOT => 'ربات تلگرام',
        ]],
        ['name' => 'credits_amount', 'label' => 'مقدار اعتبار', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0'], 'col' => 4],
        ['name' => 'credits_unit', 'label' => 'واحد اعتبار', 'type' => 'select', 'rules' => ['nullable'], 'empty' => '— بدون واحد —', 'options' => [
            'usd' => 'دلار', 'credit' => 'کردیت', 'token' => 'توکن', 'model' => 'مدل', 'domain' => 'دامنه',
        ], 'col' => 4],
        ['name' => 'pricing_type', 'label' => 'نوع قیمت‌گذاری', 'type' => 'select', 'rules' => ['nullable'], 'empty' => '— نامشخص —', 'options' => [
            Offer::PRICING_FREE => 'رایگان',
            Offer::PRICING_FREEMIUM => 'فری‌میوم',
            Offer::PRICING_TRIAL => 'آزمایشی',
            Offer::PRICING_PAY_AS_YOU_GO => 'پرداخت به‌مقدار مصرف',
            Offer::PRICING_SUBSCRIPTION => 'اشتراکی',
        ], 'col' => 4],
        ['name' => 'price', 'label' => 'قیمت', 'type' => 'price', 'rules' => ['nullable', 'numeric', 'min:0'], 'col' => 4],
        ['name' => 'currency', 'label' => 'واحد پول', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:8'], 'col' => 4],
        ['name' => 'last_verified_at', 'label' => 'آخرین بررسی دستی', 'type' => 'datetime', 'rules' => ['nullable', 'date'], 'col' => 4],
        ['name' => 'description_fa', 'label' => 'توضیحات (نمایش در جزئیات)', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:5000'], 'col' => 12],
        ['name' => 'raw_note_fa', 'label' => 'یادداشت داخلی / متن خام منبع', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:5000'], 'col' => 12],
        ['name' => 'verification_method_ids', 'label' => 'روش‌های تأیید مورد نیاز', 'type' => 'multiselect', 'options' => fn () => VerificationMethod::query()->orderBy('id')->pluck('label_fa', 'id')->all(), 'col' => 12],
        ['name' => 'ai_model_ids', 'label' => 'مدل‌های مرتبط', 'type' => 'multiselect', 'options' => fn () => AiModel::query()->orderBy('name')->pluck('name', 'id')->all(), 'col' => 12],
            ['name' => 'tag_ids', 'label' => 'برچسب‌ها', 'type' => 'multiselect', 'options' => fn () => Tag::query()->orderBy('name_fa')->pluck('name_fa', 'id')->all(), 'col' => 12],
        ];
    }

    protected function bulkUrl(): ?string
    {
        return route('admin.offers.bulk');
    }

    protected function bulkLabel(): string
    {
        return 'علامت‌گذاری به‌عنوان منقضی';
    }

    /** Bulk "mark expired": status + offer_versions audit note. */
    public function bulk(Request $request): RedirectResponse
    {
        $this->authorizeAbility('manageContent');

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:offers,id'],
            'action' => ['required', Rule::in(['mark_expired'])],
        ]);

        $offers = Offer::query()->whereIn('id', $data['ids'])->get();
        $actor = (string) (auth('admin')->user()->email ?? 'admin');
        $count = 0;

        foreach ($offers as $offer) {
            $old = $offer->status;

            $offer->forceFill(['status' => Offer::STATUS_EXPIRED])->save();

            OfferVersion::create([
                'offer_id' => $offer->id,
                'old_data' => ['status' => $old],
                'new_data' => ['status' => Offer::STATUS_EXPIRED, 'by' => $actor],
                'detected_at' => now(),
                'source' => OfferVersion::SOURCE_ADMIN,
            ]);

            $count++;
        }

        return redirect()->route('admin.offers.index')->with('status', "{$count} آفر به‌عنوان منقضی علامت خورد.");
    }

    /** Add an admin override row (override > crawler > column). */
    public function storeOverride(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAbility('manageContent');

        $offer = Offer::findOrFail($id);

        $data = $request->validate([
            'field' => ['required', Rule::in(EffectiveOffer::FIELDS)],
            'override_value' => ['required', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $current = EffectiveOffer::values($offer);

        AdminOverride::create([
            'offer_id' => $offer->id,
            'field' => $data['field'],
            'crawler_value' => $current[$data['field']] ?? null,
            'override_value' => $data['override_value'],
            'reason' => $data['reason'],
            'actor' => (string) (auth('admin')->user()->email ?? 'admin'),
        ]);

        return back()->with('status', 'بازنویسی ثبت شد.');
    }

    public function destroyOverride(int $id, int $overrideId): RedirectResponse
    {
        $this->authorizeAbility('manageContent');

        AdminOverride::query()->where('offer_id', $id)->whereKey($overrideId)->firstOrFail()->delete();

        return back()->with('status', 'بازنویسی حذف شد.');
    }

    protected function extrasData(Model $item): array
    {
        return [
            'overrides' => AdminOverride::query()->where('offer_id', $item->getKey())->orderByDesc('id')->get(),
            'overrideFields' => EffectiveOffer::FIELDS,
            'effective' => EffectiveOffer::values($item),
        ];
    }

    protected function deletable(Model $item): bool
    {
        return true;
    }
}
