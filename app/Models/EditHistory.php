<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditHistory extends Model
{
    protected $fillable = [
        'image_id',
        'adjustments',
        'thumbnail',
        'version_number',
    ];

    protected $casts = [
        'adjustments' => 'array',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public static function getDefaultAdjustments(): array
    {
        return [
            'brightness' => 0,
            'contrast' => 0,
            'saturation' => 0,
            'exposure' => 0,
            'temperature' => 0,
            'shadows' => 0,
            'highlights' => 0,
            'vibrance' => 0,
            'rotation' => 0,
            'flipH' => false,
            'flipV' => false,
            'filter' => null,
            'crop' => null,
        ];
    }
}
