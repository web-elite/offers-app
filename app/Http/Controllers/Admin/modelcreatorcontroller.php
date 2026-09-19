<?php

namespace App\Http\Controllers\Admin;

use App\Models\ModelCreator;

class ModelCreatorController extends CrudController
{
    protected string $modelClass = ModelCreator::class;
    protected string $title = 'سازندگان مدل';
    protected string $singular = 'سازنده مدل';
    protected string $route = 'admin.model-creators';
    protected array $searchable = ['name', 'slug'];
    protected string $orderColumn = 'name';
    protected string $orderDirection = 'asc';

    protected array $columns = [
        ['label' => 'نام', 'attr' => 'name'],
        ['label' => 'شناسه', 'attr' => 'slug', 'mono' => true],
        ['label' => 'تعداد مدل', 'value' => 'ai_models_count', 'num' => true],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'نام سازنده', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
        ['name' => 'slug', 'label' => 'شناسه لاتین', 'type' => 'text', 'rules' => ['required', 'string', 'max:191', 'alpha_dash'], 'unique' => ['model_creators', 'slug'], 'col' => 6],
    ];
}
