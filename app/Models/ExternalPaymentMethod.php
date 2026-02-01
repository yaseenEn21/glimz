<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalPaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'code',
        'icon',
        'requires_reference',
        'requires_attachment',
        'bank_details',
        'is_active',
        'sort_order',
        'internal_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'bank_details' => 'array',
        'requires_reference' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope للوسائل النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * الحصول على الاسم حسب اللغة
     */
    public function getLocalizedName(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->name[$locale] ?? $this->name['ar'] ?? $this->name['en'] ?? null;
    }

    /**
     * الحصول على الوصف حسب اللغة
     */
    public function getLocalizedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->description[$locale] ?? $this->description['ar'] ?? $this->description['en'] ?? null;
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