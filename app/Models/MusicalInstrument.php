<?php

namespace App\Models;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusicalInstrument extends Model
{
    use HasUuids;

    protected $fillable = [
        'island_id',
        'province_id',
        'name',
        'description',
        'image_path',
        'video_url',
        'status',
        'published_at',
    ];

    protected $hidden = ['image_path'];

    protected $appends = ['image_url'];

    protected $casts = [
        'status' => PublishStatus::class,
        'published_at' => 'datetime',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image_path ? asset('storage/' . $this->image_path) : null
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published->value);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }
}