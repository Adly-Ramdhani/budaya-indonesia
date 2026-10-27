<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Song extends Model
{
    use HasUuids;

    protected $fillable = [
        'province_id', 'title', 'composer', 'language',
        'description', 'lyrics', 'audio_path', 'status', 'published_at',
    ];

    protected $hidden = ['audio_path'];

    protected $appends = ['audio_url'];

    /**
     * URL audio dibuat oleh Laravel sesuai disk yang dipakai.
     * - Lokal (disk "public")  => APP_URL/storage/songs/audio/xxx.mp3
     * - Produksi (disk "s3")   => AWS_URL/songs/audio/xxx.mp3 (R2, S3, dll.)
     */
    protected function audioUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->audio_path
                ? Storage::disk(config('filesystems.media_disk'))->url($this->audio_path)
                : null
        );
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}