<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_CRAWLER_MANAGER = 'crawler_manager';
    public const ROLE_REVIEWER = 'reviewer';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_EDITOR,
        self::ROLE_CRAWLER_MANAGER,
        self::ROLE_REVIEWER,
    ];

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => 'مدیر ارشد',
        self::ROLE_ADMIN => 'مدیر',
        self::ROLE_EDITOR => 'ویراستار',
        self::ROLE_CRAWLER_MANAGER => 'مدیر کراولر',
        self::ROLE_REVIEWER => 'بررسی‌کننده',
    ];

    /** Ability → allowed roles. */
    public const ABILITY_ROLES = [
        'manageContent' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_EDITOR],
        'manageCrawlers' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_CRAWLER_MANAGER],
        'moderate' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_REVIEWER],
        'manageAdmins' => [self::ROLE_SUPER_ADMIN],
    ];

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** ACL check against the admin guard user (Gates are bound to the default web guard, so we check directly). */
    public function hasAbility(string $ability): bool
    {
        $roles = self::ABILITY_ROLES[$ability] ?? [];

        return $roles !== [] && $this->hasRole(...$roles);
    }
}
