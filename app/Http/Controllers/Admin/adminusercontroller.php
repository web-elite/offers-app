<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;

class AdminUserController extends CrudController
{
    protected string $modelClass = Admin::class;
    protected string $title = 'مدیران';
    protected string $singular = 'مدیر';
    protected string $route = 'admin.admins';
    protected string $gate = 'manageAdmins';
    protected array $searchable = ['name', 'email'];
    protected string $orderColumn = 'id';
    protected string $orderDirection = 'desc';

    protected array $columns = [
        ['label' => 'نام', 'attr' => 'name'],
        ['label' => 'ایمیل', 'attr' => 'email', 'mono' => true],
        ['label' => 'نقش', 'value' => 'role_label'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'نام', 'type' => 'text', 'rules' => ['required', 'string', 'max:191'], 'col' => 6],
        ['name' => 'email', 'label' => 'ایمیل', 'type' => 'email', 'rules' => ['required', 'email', 'max:191'], 'unique' => ['admins', 'email'], 'col' => 6],
        ['name' => 'role', 'label' => 'نقش', 'type' => 'select', 'rules' => ['required'], 'options' => [
            Admin::ROLE_SUPER_ADMIN => 'مدیر ارشد',
            Admin::ROLE_ADMIN => 'مدیر',
            Admin::ROLE_EDITOR => 'ویراستار',
            Admin::ROLE_CRAWLER_MANAGER => 'مدیر کراولر',
            Admin::ROLE_REVIEWER => 'بررسی‌کننده',
        ], 'col' => 6],
        ['name' => 'password', 'label' => 'رمز عبور', 'type' => 'password', 'col' => 6, 'help' => 'در حالت ویرایش، خالی گذاشتن یعنی بدون تغییر.'],
    ];
}
