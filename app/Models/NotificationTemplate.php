<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationTemplate extends Model
{
    use HasFactory;

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
}