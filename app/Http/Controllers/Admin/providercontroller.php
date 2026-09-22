<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Provider;

class ProviderController extends CrudController
{
    protected string $modelClass = Provider::class;
    protected string $title = 'ارائه‌دهنده‌ها';
    protected string $singular = 'ارائه‌دهنده';
    protected string $route = 'admin.providers';
    protected array $searchable = ['name', 'slug', 'canonical_url'];
    protected array $with = ['category'];
    protected string $orderColumn = 'name';
    protected string $orderDirection = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'نام', 'attr' => 'name'],
            ['label' => 'دسته', 'value' => 'category.name_fa'],
            ['label' => 'شناسه (slug)', 'attr' => 'slug', 'mono' => true],
            ['label' => 'وضعیت', 'attr' => 'status'],
            ['label' => 'آفرها', 'value' => 'offers_count', 'num' => true],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'نام ارائه‌دهنده', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
            ['name' => 'slug', 'label' => 'شناسه لاتین (slug)', 'type' => 'text', 'rules' => ['required', 'string', 'max:191', 'alpha_dash'], 'unique' => ['providers', 'slug'], 'col' => 6, 'help' => 'در آدرس صفحه استفاده می‌شود؛ فقط حروف لاتین، عدد و خط تیره.'],
            ['name' => 'category_id', 'label' => 'دسته‌بندی', 'type' => 'select', 'rules' => ['required', 'integer', 'exists:categories,id'], 'options' => fn () => Category::query()->orderBy('sort_order')->pluck('name_fa', 'id')->all(), 'col' => 4],
            ['name' => 'status', 'label' => 'وضعیت', 'type' => 'select', 'rules' => ['required'], 'options' => ['active' => 'فعال', 'inactive' => 'غیرفعال'], 'col' => 4],
            ['name' => 'logo_url', 'label' => 'آدرس لوگو (اختیاری)', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:500'], 'col' => 4],
            ['name' => 'canonical_url', 'label' => 'آدرس اصلی سایت', 'type' => 'url', 'rules' => ['required', 'url', 'max:500'], 'col' => 6],
            ['name' => 'referral_url', 'label' => 'لینک معرفی (اختیاری)', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:500'], 'col' => 6],
            ['name' => 'description_fa', 'label' => 'توضیحات', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:5000'], 'col' => 12],
        ];
    }
}
