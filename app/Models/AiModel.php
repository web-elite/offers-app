<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AiModel extends Model
{
    protected $fillable = ['slug', 'name', 'model_creator_id', 'context_window', 'capabilities', 'is_active'];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function modelCreator(): BelongsTo
    {
        return $this->belongsTo(ModelCreator::class);
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class);
    }
}
