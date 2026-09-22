<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ad;
use App\Support\FaDigits;

class AdController extends CrudController
{
    protected string $modelClass = Ad::class;
    protected string $title = 'تبلیغات';
    protected string $singular = 'تبلیغ';
    protected string $route = 'admin.ads';
    protected array $searchable = ['title', 'target_url'];
    protected string $orderColumn = 'priority';
    protected string $orderDirection = 'desc';

    protected array $indexFilters = [
        ['name' => 'position', 'label' => 'جایگاه', 'column' => 'position', 'options' => Ad::POSITION_LABELS],
        ['name' => 'is_active', 'label' => 'وضعیت', 'column' => 'is_active', 'options' => ['1' => 'فعال', '0' => 'غیرفعال']],
    ];

    protected function columns(): array
    {
        return [
            ['label' => 'عنوان', 'value' => fn (Ad $a) => $a->title],
            ['label' => 'جایگاه', 'value' => fn (Ad $a) => Ad::POSITION_LABELS[$a->position] ?? $a->position],
            ['label' => 'فعال', 'value' => fn (Ad $a) => $a->is_active ? 'بله' : 'خیر'],
            ['label' => 'نمایش', 'value' => fn (Ad $a) => FaDigits::convert(number_format($a->impressions))],
            ['label' => 'کلیک', 'value' => fn (Ad $a) => FaDigits::convert(number_format($a->clicks))],
            ['label' => 'نرخ کلیک', 'value' => fn (Ad $a) => FaDigits::convert(number_format($a->ctr(), 2)).'٪'],
            ['label' => 'بازه', 'value' => fn (Ad $a) => trim(
                ($a->starts_at?->format('Y-m-d') ?? '—').' تا '.($a->ends_at?->format('Y-m-d') ?? '—'),
                ' '
            )],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'عنوان تبلیغ', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
            ['name' => 'target_url', 'label' => 'لینک مقصد', 'type' => 'url', 'rules' => ['required', 'url', 'max:500'], 'col' => 6],
            ['name' => 'position', 'label' => 'جایگاه نمایش', 'type' => 'select', 'rules' => ['required'], 'options' => Ad::POSITION_LABELS, 'col' => 6],
            ['name' => 'priority', 'label' => 'اولویت', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0'], 'col' => 3, 'help' => 'عدد بزرگ‌تر، نمایش زودتر.'],
            ['name' => 'is_active', 'label' => 'فعال باشد', 'type' => 'checkbox', 'col' => 3],
            ['name' => 'image_url', 'label' => 'آدرس تصویر (اختیاری)', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:500'], 'col' => 12, 'help' => 'اگر تصویر خارجی دارید، آدرس آن را وارد کنید.'],
            ['name' => 'image', 'label' => 'یا بارگذاری تصویر', 'type' => 'file', 'rules' => ['nullable', 'image', 'max:3072'], 'col' => 12, 'stores' => 'image_url', 'disk' => 'ads', 'help' => 'حداکثر ۳ مگابایت. در صورت بارگذاری، جای آدرس تصویر را می‌گیرد. (نیاز به php artisan storage:link)'],
            ['name' => 'html_snippet', 'label' => 'کد HTML جایگزین (اختیاری)', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:20000'], 'col' => 12, 'help' => 'در صورت خالی بودن تصویر، این کد خام نمایش داده می‌شود.'],
            ['name' => 'starts_at', 'label' => 'شروع نمایش', 'type' => 'datetime', 'rules' => ['nullable', 'date'], 'col' => 6],
            ['name' => 'ends_at', 'label' => 'پایان نمایش', 'type' => 'datetime', 'rules' => ['nullable', 'date', 'after_or_equal:starts_at'], 'col' => 6],
        ];
    }
}
