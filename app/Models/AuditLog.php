<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const ACTION_UPDATED = 'updated';
    public const ACTION_CREATED = 'created';
    public const ACTION_DELETED = 'deleted';

    protected $fillable = [
        'actor', 'action', 'entity', 'entity_id', 'old_value', 'new_value', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }
}
