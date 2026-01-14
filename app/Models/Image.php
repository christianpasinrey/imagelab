<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Image extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'original_filename',
    ];

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
