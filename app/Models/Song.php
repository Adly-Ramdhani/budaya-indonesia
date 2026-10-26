<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Song extends Model
{
    use HasUuids;

    protected $fillable = [
        'province_id', 'title', 'composer', 'language',
        'description', 'lyrics', 'audio_path', 'status', 'published_at',
    ];

    protected $hidden = ['audio_path'];

    protected $appends = ['audio_url'];


    protected function audioUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->audio_path ? url('/media/' . $this->audio_path) : null
        );
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}