<?php

namespace App\Http\Controllers\Admin;

use App\Models\AiModel;
use App\Models\ModelCreator;

class AiModelController extends CrudController
{
    protected string $modelClass = AiModel::class;
    protected string $title = 'مدل‌های هوش مصنوعی';
    protected string $singular = 'مدل';
    protected string $route = 'admin.ai-models';
    protected array $searchable = ['name', 'slug'];
    protected array $with = ['modelCreator'];
    protected string $orderColumn = 'name';
    protected string $orderDirection = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'نام مدل', 'attr' => 'name'],
            ['label' => 'شناسه', 'attr' => 'slug', 'mono' => true],
            ['label' => 'سازنده', 'value' => 'modelCreator.name'],
            ['label' => 'پنجره متن', 'attr' => 'context_window', 'num' => true],
            ['label' => 'فعال', 'value' => 'is_active_label'],
            ['label' => 'آفرها', 'value' => 'offers_count', 'num' => true],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'نام مدل', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
            ['name' => 'slug', 'label' => 'شناسه لاتین', 'type' => 'text', 'rules' => ['required', 'string', 'max:191', 'alpha_dash'], 'unique' => ['ai_models', 'slug'], 'col' => 6],
            ['name' => 'model_creator_id', 'label' => 'سازنده مدل', 'type' => 'select', 'rules' => ['nullable', 'integer', 'exists:model_creators,id'], 'options' => fn () => ModelCreator::query()->orderBy('name')->pluck('name', 'id')->all(), 'col' => 6, 'empty' => '— انتخاب کنید —'],
            ['name' => 'context_window', 'label' => 'پنجره متن (توکن)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0'], 'col' => 6],
            ['name' => 'capabilities', 'label' => 'قابلیت‌ها', 'type' => 'checkboxgroup', 'col' => 12, 'options' => [
                'vision' => 'تصویر',
                'tool_calling' => 'فراخوانی ابزار',
                'reasoning' => 'استدلال',
                'coding' => 'کدنویسی',
                'image' => 'تولید تصویر',
                'audio' => 'صدا',
                'open_closed' => 'متن‌باز',
            ]],
            ['name' => 'is_active', 'label' => 'فعال باشد', 'type' => 'checkbox', 'col' => 12],
        ];
    }
}
