<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;

class CategoryController extends CrudController
{
    protected string $modelClass = Category::class;
    protected string $title = 'دسته‌بندی‌ها';
    protected string $singular = 'دسته‌بندی';
    protected string $route = 'admin.categories';
    protected array $searchable = ['name_fa', 'name_en', 'slug'];
    protected string $orderColumn = 'sort_order';
    protected string $orderDirection = 'asc';

    protected array $columns = [
        ['label' => 'نام فارسی', 'attr' => 'name_fa'],
        ['label' => 'نام انگلیسی', 'attr' => 'name_en'],
        ['label' => 'شناسه', 'attr' => 'slug', 'mono' => true],
        ['label' => 'ترتیب', 'attr' => 'sort_order', 'num' => true],
        ['label' => 'فعال', 'value' => 'is_active_label'],
        ['label' => 'ارائه‌دهنده‌ها', 'value' => 'providers_count', 'num' => true],
    ];

    protected array $fields = [
        ['name' => 'name_fa', 'label' => 'نام فارسی', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
        ['name' => 'name_en', 'label' => 'نام انگلیسی', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:191'], 'col' => 6],
        ['name' => 'slug', 'label' => 'شناسه لاتین', 'type' => 'text', 'rules' => ['required', 'string', 'max:191', 'alpha_dash'], 'unique' => ['categories', 'slug'], 'col' => 6],
        ['name' => 'sort_order', 'label' => 'ترتیب نمایش', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0'], 'col' => 3],
        ['name' => 'is_active', 'label' => 'فعال باشد', 'type' => 'checkbox', 'col' => 3],
    ];
}
