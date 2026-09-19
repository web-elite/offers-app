<?php

namespace App\Http\Controllers\Admin;

use App\Models\VerificationMethod;

class VerificationMethodController extends CrudController
{
    protected string $modelClass = VerificationMethod::class;
    protected string $title = 'روش‌های تأیید هویت';
    protected string $singular = 'روش تأیید';
    protected string $route = 'admin.verification-methods';
    protected array $searchable = ['key', 'label_fa'];
    protected string $orderColumn = 'id';
    protected string $orderDirection = 'asc';

    protected array $columns = [
        ['label' => 'کلید', 'attr' => 'key', 'mono' => true],
        ['label' => 'عنوان', 'attr' => 'label_fa'],
        ['label' => 'تعداد آفر', 'value' => 'offers_count', 'num' => true],
    ];

    protected array $fields = [
        ['name' => 'key', 'label' => 'کلید (لاتین)', 'type' => 'text', 'rules' => ['required', 'string', 'max:64', 'alpha_dash'], 'unique' => ['verification_methods', 'key'], 'col' => 6, 'help' => 'نمونه: email، phone، card، telegram. کلیدهای اصلی سیستم باید دست‌نخورده بمانند.'],
        ['name' => 'label_fa', 'label' => 'عنوان فارسی', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
    ];

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAbility($this->gate);

        $item = $this->modelClass::findOrFail($id);

        if (in_array($item->key, VerificationMethod::KEYS, true)) {
            return back()->with('error', 'روش‌های تأیید پیش‌فرض سیستم قابل حذف نیستند؛ فقط عنوان آن‌ها را ویرایش کنید.');
        }

        $item->delete();

        return redirect()->route($this->routeName().'.index')->with('status', 'حذف شد.');
    }
}
