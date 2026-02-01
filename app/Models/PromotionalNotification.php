<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionalNotification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'target_type',
        'target_user_ids',
        'linkable_type',
        'linkable_id',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'successful_sends',
        'failed_sends',
        'internal_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'title' => 'array',
        'body' => 'array',
        'target_user_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'successful_sends' => 'integer',
        'failed_sends' => 'integer',
    ];

    /**
     * العلاقة مع الكيان المرتبط (Service, Package, Product, etc.)
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * العلاقة مع من أنشأ الإشعار
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع من حدّث الإشعار
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope للإشعارات المجدولة
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope للإشعارات المسودة
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope للإشعارات المرسلة
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * الحصول على العنوان حسب اللغة
     */
    public function getTitleForLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->title[$locale] ?? $this->title['ar'] ?? $this->title['en'] ?? '';
    }

    /**
     * الحصول على النص حسب اللغة
     */
    public function getBodyForLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->body[$locale] ?? $this->body['ar'] ?? $this->body['en'] ?? '';
    }

    /**
     * الحصول على عدد المستلمين المستهدفين
     */
    public function getTargetUsersCount(): int
    {
        if ($this->target_type === 'all_users') {
            return User::where('user_type', 'customer')
                ->where('is_active', true)
                ->count();
        }

        if ($this->target_type === 'specific_users' && $this->target_user_ids) {
            return count($this->target_user_ids);
        }

        return 0;
    }

    /**
     * الحصول على المستخدمين المستهدفين
     */
    public function getTargetUsers()
    {
        if ($this->target_type === 'all_users') {
            return User::where('user_type', 'customer')
                ->where('is_active', true)
                ->get();
        }

        if ($this->target_type === 'specific_users' && $this->target_user_ids) {
            return User::whereIn('id', $this->target_user_ids)
                ->where('is_active', true)
                ->get();
        }

        return collect([]);
    }

    /**
     * الحصول على نسبة النجاح
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->successful_sends / $this->total_recipients) * 100, 2);
    }

    /**
     * التحقق من إمكانية التعديل
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    /**
     * التحقق من إمكانية الإرسال
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    /**
     * التحقق من إمكانية الإلغاء
     */
    public function canBeCancelled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * إلغاء الإشعار المجدول
     */
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        return $this->update([
            'status' => 'cancelled',
            'updated_by' => auth()->id(),
        ]);
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