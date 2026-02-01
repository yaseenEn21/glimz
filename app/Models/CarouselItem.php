<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
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
        'display_type',
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

    /**
     * Scope لجلب العناصر النشطة حسب الوقت الحالي
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Scope لجلب العناصر المجدولة
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>', now());
    }

    /**
     * Scope لجلب العناصر المنتهية
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('ends_at')
            ->where('ends_at', '<', now());
    }

    /**
     * التحقق من أن العنصر نشط حالياً
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * الحصول على حالة العنصر
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return 'active';
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