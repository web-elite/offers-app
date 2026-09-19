<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Config-driven admin CRUD used by all content resources.
 *
 * Field spec: ['name','label','type','rules','unique'=>[table,col],'options'=>array|callable,
 *              'col'=>12|6|4,'help'=>string,'empty'=>string,'disk'=>string,'stores'=>string]
 * Types: text|url|email|number|price|datetime|textarea|select|checkbox|multiselect|checkboxgroup|password|file
 */
abstract class CrudController extends Controller
{
    protected string $modelClass;
    protected string $title = '';
    protected string $singular = '';
    protected string $gate = 'manageContent';
    protected array $columns = [];
    protected array $fields = [];
    protected array $searchable = [];
    protected array $with = [];
    protected array $withCount = [];
    protected string $orderColumn = 'id';
    protected string $orderDirection = 'desc';
    /** @var array<string, string> relation name => request input name */
    protected array $syncRelations = [];
    protected ?string $extrasView = null;
    protected int $perPage = 15;
    /** Explicit route base name; defaults to a name derived from the class. */
    protected ?string $route = null;
    /** Index filter selects: ['name','label','options'=>array,'column'=>string,'type'=>select|json] */
    protected array $indexFilters = [];

    public function index(Request $request): ViewContract
    {
        $this->authorizeAbility($this->gate);

        $query = $this->indexQuery($request);
        $this->applyIndexFilters($query, $request);

        $items = $query->orderBy($this->orderColumn, $this->orderDirection)
            ->paginate($this->perPage)
            ->withQueryString();

        return view('admin.crud.index', [
            'title' => $this->title,
            'singular' => $this->singular,
            'items' => $items,
            'rows' => $this->rows($items),
            'columns' => $this->columns,
            'routeName' => $this->routeName(),
            'searchable' => $this->searchable !== [],
            'filters' => $this->indexFilters,
            'bulkUrl' => $this->bulkUrl(),
            'bulkLabel' => $this->bulkLabel(),
            'searchTerm' => (string) $request->query('q', ''),
        ]);
    }

    public function create(): ViewContract
    {
        $this->authorizeAbility($this->gate);

        return view('admin.crud.form', [
            'title' => 'افزودن '.$this->singular,
            'item' => $this->newModel(),
            'fields' => $this->resolvedFields(),
            'action' => route($this->routeName().'.store'),
            'routeName' => $this->routeName(),
            'extrasView' => null,
            'extrasData' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAbility($this->gate);

        $class = $this->modelClass;
        $data = $this->validated($request, null);
        $item = $class::create($data);
        $this->syncRelations($item, $request);
        $this->afterSave($item, $request);

        return redirect()->route($this->routeName().'.index')->with('status', $this->savedMessage());
    }

    public function edit(Request $request, int $id): ViewContract
    {
        $this->authorizeAbility($this->gate);

        $item = $this->modelQuery()->with($this->editWith())->findOrFail($id);

        return view('admin.crud.form', [
            'title' => 'ویرایش '.$this->singular,
            'item' => $item,
            'fields' => $this->resolvedFields(),
            'action' => route($this->routeName().'.update', $item->getKey()),
            'routeName' => $this->routeName(),
            'extrasView' => $this->extrasView,
            'extrasData' => $this->extrasData($item),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAbility($this->gate);

        $item = $this->modelQuery()->findOrFail($id);
        $data = $this->validated($request, $item);
        $item->fill($data)->save();
        $this->syncRelations($item, $request);
        $this->afterSave($item, $request);

        return redirect()->route($this->routeName().'.index')->with('status', 'تغییرات ذخیره شد.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorizeAbility($this->gate);

        $this->modelQuery()->findOrFail($id)->delete();

        return redirect()->route($this->routeName().'.index')->with('status', 'حذف شد.');
    }

    // ------------------------------------------------------------------

    protected function newModel(): Model
    {
        $class = $this->modelClass;

        return new $class;
    }

    protected function modelQuery(): Builder
    {
        $class = $this->modelClass;

        return $class::query();
    }

    protected function authorizeAbility(string $ability): void
    {
        $admin = auth('admin')->user();

        abort_unless($admin instanceof Admin && $admin->hasAbility($ability), 403);
    }

    protected function savedMessage(): string
    {
        return 'با موفقیت ذخیره شد.';
    }

    protected function indexQuery(Request $request): Builder
    {
        $query = $this->modelQuery()->with($this->with);

        if ($this->withCount !== []) {
            $query->withCount($this->withCount);
        }

        $term = trim((string) $request->query('q', ''));
        if ($term !== '' && $this->searchable !== []) {
            $query->where(function (Builder $w) use ($term) {
                foreach ($this->searchable as $column) {
                    $w->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        return $query;
    }

    protected function applyIndexFilters(Builder $query, Request $request): void
    {
        foreach ($this->indexFilters as $filter) {
            $value = $request->query($filter['name']);

            if ($value === null || $value === '') {
                continue;
            }

            $column = $filter['column'] ?? $filter['name'];

            if (($filter['type'] ?? 'select') === 'json') {
                $query->whereJsonContains($column, (string) $value);
                continue;
            }

            $query->where($column, $value);
        }
    }

    /** Render table rows into plain strings so Blade stays dumb. */
    protected function rows(mixed $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $cells = [];

            foreach ($this->columns as $column) {
                $cells[] = $this->cellValue($item, $column);
            }

            $rows[] = [
                'id' => $item->getKey(),
                'cells' => $cells,
                'editUrl' => route($this->routeName().'.edit', $item->getKey()),
                'deleteUrl' => route($this->routeName().'.destroy', $item->getKey()),
                'deletable' => $this->deletable($item),
            ];
        }

        return $rows;
    }

    protected function deletable(Model $item): bool
    {
        return true;
    }

    protected function cellValue(Model $item, array $column): string
    {
        $value = null;

        if (isset($column['value'])) {
            $value = is_callable($column['value']) ? ($column['value'])($item) : data_get($item, $column['value']);
        } elseif (isset($column['attr'])) {
            $value = data_get($item, $column['attr']);
        }

        if (is_bool($value)) {
            return $value ? 'بله' : 'خیر';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }

    protected function bulkUrl(): ?string
    {
        return null;
    }

    protected function bulkLabel(): string
    {
        return '';
    }

    protected function editWith(): array
    {
        return array_keys($this->syncRelations);
    }

    /** @return array<int, array> with option callables resolved */
    protected function resolvedFields(): array
    {
        return array_map(function (array $field): array {
            if (isset($field['options']) && is_callable($field['options'])) {
                $field['options'] = $field['options']();
            }

            return $field;
        }, $this->fields);
    }

    protected function rules(?Model $model): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';

            if ($type === 'password') {
                $rules[$name] = $model === null
                    ? ['required', 'string', 'min:8']
                    : ['nullable', 'string', 'min:8'];
                continue;
            }

            if ($type === 'file') {
                $rules[$name] = $field['rules'] ?? ['nullable', 'image', 'max:2048'];
                continue;
            }

            $fieldRules = $field['rules'] ?? ['nullable'];

            if (isset($field['unique'])) {
                [$table, $column] = $field['unique'];
                $rule = Rule::unique($table, $column);

                if ($model !== null && $model->getKey() !== null) {
                    $rule->ignore($model->getKey());
                }

                $fieldRules[] = $rule;
            }

            if (in_array($type, ['multiselect', 'checkboxgroup'], true)) {
                $fieldRules = ['nullable', 'array'];
                $rules[$name.'.*'] = ['string'];
            }

            $rules[$name] = $fieldRules;
        }

        foreach ($this->syncRelations as $input) {
            $rules[$input] = ['nullable', 'array'];
            $rules[$input.'.*'] = ['integer'];
        }

        return array_merge($rules, $this->extraRules($model));
    }

    protected function extraRules(?Model $model): array
    {
        return [];
    }

    protected function validated(Request $request, ?Model $model): array
    {
        $request->validate($this->rules($model));

        $fillable = $this->newModel()->getFillable();
        $data = [];

        foreach ($this->fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';

            if ($type === 'password') {
                $value = (string) $request->input($name, '');
                if ($value !== '') {
                    $data[$name] = $value;
                }
                continue;
            }

            $targetName = $type === 'file' ? ($field['stores'] ?? $name) : $name;

            if ($type === 'file') {
                if ($request->hasFile($name)) {
                    $path = $request->file($name)->store($field['disk'] ?? 'ads', 'public');
                    $data[$targetName] = \Illuminate\Support\Facades\Storage::url($path);
                }
                continue;
            }

            if (! in_array($name, $fillable, true)) {
                continue;
            }

            if ($type === 'checkbox') {
                $data[$name] = $request->boolean($name);
                continue;
            }

            if (in_array($type, ['multiselect', 'checkboxgroup'], true)) {
                $data[$name] = array_values(array_filter(
                    (array) $request->input($name, []),
                    fn ($v) => $v !== null && $v !== ''
                ));
                continue;
            }

            if ($type === 'number') {
                $value = $request->input($name);
                $data[$name] = ($value === null || $value === '') ? null : (int) $value;
                continue;
            }

            $value = $request->input($name);
            $data[$name] = ($value === null || $value === '') ? null : $value;
        }

        return $data;
    }

    protected function syncRelations(Model $item, Request $request): void
    {
        foreach ($this->syncRelations as $relation => $input) {
            $item->{$relation}()->sync(array_values(array_filter((array) $request->input($input, []))));
        }
    }

    protected function afterSave(Model $item, Request $request): void
    {
        //
    }

    protected function extrasData(Model $item): array
    {
        return [];
    }

    protected function routeName(): string
    {
        return $this->route
            ?? 'admin.'.Str::of(class_basename(static::class))->before('Controller')->snake()->plural()->toString();
    }
}
