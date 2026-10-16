<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraditionalHouse extends Model
{
    use HasUuids;

    protected $table = 'traditional_houses';

    protected $fillable = [
        'province_id', 'name', 'description', 'image_path',
    ];

    protected $hidden = ['image_path'];

    protected $appends = ['image_url'];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image_path ? url('/media/' . $this->image_path) : null
        );
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}