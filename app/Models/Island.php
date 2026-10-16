<?php

namespace App\Models;

use App\Models\Province;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Island extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
    ];

        public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}