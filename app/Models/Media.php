<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'disk',
        'path',
        'thumbnail_path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
        'uploaded_by',
    ];

    protected $appends = [
        'url',
        'thumbnail_url',
        'api_url',
        'api_thumbnail_url',
        'preferred_image_url',
        'preferred_thumbnail_url',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        if (empty($this->path)) {
            return null;
        }

        return $this->buildPublicStorageUrl((string) $this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }

        return $this->buildPublicStorageUrl((string) $this->thumbnail_path);
    }

    public function getApiUrlAttribute(): ?string
    {
        if (!$this->id) {
            return null;
        }

        return url('/api/media/' . $this->id);
    }

    public function getApiThumbnailUrlAttribute(): ?string
    {
        if (!$this->id || empty($this->thumbnail_path)) {
            return null;
        }

        return url('/api/media/' . $this->id . '?variant=thumb');
    }

    public function getPreferredImageUrlAttribute(): ?string
    {
        return $this->api_url;
    }

    public function getPreferredThumbnailUrlAttribute(): ?string
    {
        return $this->api_thumbnail_url;
    }

    private function buildPublicStorageUrl(string $relativePath): string
    {
        $base = (string) (config('filesystems.disks.public.url') ?: (rtrim((string) config('app.url'), '/') . '/storage'));

        return rtrim($base, '/') . '/' . ltrim($relativePath, '/');
    }
}
