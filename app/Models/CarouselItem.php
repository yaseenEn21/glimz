<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CarouselItem extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'label',
        'title',
        'description',
        'hint',
        'cta',
        'carouselable_type',
        'carouselable_id',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'label' => 'array',
        'title' => 'array',
        'description' => 'array',
        'hint' => 'array',
        'cta' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function carouselable(): MorphTo
    {
        return $this->morphTo();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image_ar')->singleFile();
        $this->addMediaCollection('image_en')->singleFile();
    }

    public function getImageUrl(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $collection = $locale === 'en' ? 'image_en' : 'image_ar';

        $url = $this->getFirstMediaUrl($collection);
        if (!$url) {
            $url = $this->getFirstMediaUrl('image_ar') ?: $this->getFirstMediaUrl('image_en');
        }
        return $url ?: null;
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?? auth()->id();
                $model->updated_by = $model->updated_by ?? auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

}