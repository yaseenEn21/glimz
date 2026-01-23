<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class NotificationTemplate extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // helper بسيط لاختيار النص حسب اللغة
    public function getTitleForLocale(string $locale = 'ar'): string
    {
        if ($locale === 'en' && $this->title_en) {
            return $this->title_en;
        }
        return $this->title;
    }

    public function getBodyForLocale(string $locale = 'ar'): string
    {
        if ($locale === 'en' && $this->body_en) {
            return $this->body_en;
        }
        return $this->body;
    }

    // في NotificationTemplate Model
    public function getIconUrl(): ?string
    {
        if ($this->hasMedia('icon')) {
            return $this->getFirstMediaUrl('icon');
        }
        return null;
    }

    public function getIconPath(): ?string
    {
        if ($this->hasMedia('icon')) {
            $media = $this->getFirstMedia('icon');
            return $media ? $media->file_name : null;
        }
        return null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->useDisk('notification_icons') // 👈 استخدام الـ disk المخصص
            ->singleFile();
    }
}