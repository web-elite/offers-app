<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];
        $new = [];

        foreach ($changes as $key => $value) {
            $old[$key] = $model->getOriginal($key);
            $new[$key] = $model->getAttribute($key);
        }

        AuditLog::create([
            'actor' => auth('admin')->user()?->email,
            'action' => AuditLog::ACTION_UPDATED,
            'entity' => strtolower(class_basename($model)),
            'entity_id' => $model->getKey(),
            'old_value' => $old,
            'new_value' => $new,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
