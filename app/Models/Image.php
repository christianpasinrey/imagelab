<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Image extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'title',
        'slug',
        'tags',
        'session_id',
        'original_filename',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($image) {
            if (empty($image->slug)) {
                $image->slug = Str::slug($image->name) . '-' . Str::random(6);
            }
            if (empty($image->title)) {
                $image->title = $image->name;
            }
        });
    }

    public function canEdit(string $sessionId): bool
    {
        return $this->session_id === $sessionId;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function editHistories(): HasMany
    {
        return $this->hasMany(EditHistory::class)->orderBy('version_number', 'desc');
    }

    public function latestVersion(): ?EditHistory
    {
        return $this->editHistories()->first();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('original')
            ->singleFile();

        $this->addMediaCollection('versions');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(800)
            ->nonQueued();
    }
}
