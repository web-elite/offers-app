<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;

class TagController extends CrudController
{
    protected string $modelClass = Tag::class;
    protected string $title = 'برچسب‌ها';
    protected string $singular = 'برچسب';
    protected string $route = 'admin.tags';
    protected array $searchable = ['name_fa', 'slug'];
    protected string $orderColumn = 'name_fa';
    protected string $orderDirection = 'asc';

    protected array $columns = [
        ['label' => 'عنوان', 'attr' => 'name_fa'],
        ['label' => 'شناسه', 'attr' => 'slug', 'mono' => true],
        ['label' => 'تعداد آفر', 'value' => 'offers_count', 'num' => true],
    ];

    protected array $fields = [
        ['name' => 'name_fa', 'label' => 'عنوان برچسب', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
        ['name' => 'slug', 'label' => 'شناسه لاتین', 'type' => 'text', 'rules' => ['required', 'string', 'max:191', 'alpha_dash'], 'unique' => ['tags', 'slug'], 'col' => 6],
    ];
}
