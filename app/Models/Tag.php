<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name_fa'];

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class);
    }
}
