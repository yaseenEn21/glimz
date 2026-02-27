<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'api_token',
        'mobile',
        'email',
        'webhook_url',
        'daily_booking_limit',
        'is_active',
        'created_by',
        'updated_by',
        'webhook_type',
        'allow_customer_points'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_booking_limit' => 'integer',
        'allow_customer_points' => 'boolean'
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // توليد API Token تلقائياً عند الإنشاء
        static::creating(function ($partner) {
            if (empty($partner->api_token)) {
                $partner->api_token = self::generateUniqueToken();
            }
        });
    }

    /**
     * توليد Token فريد
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('api_token', $token)->exists());

        return $token;
    }

    /**
     * إعادة توليد Token
     */
    public function regenerateToken(): string
    {
        $this->api_token = self::generateUniqueToken();
        $this->save();

        return $this->api_token;
    }

    /**
     * العلاقات
     */

    // الخدمات المخصصة للشريك
    public function services()
    {
        return $this->belongsToMany(Service::class, 'partner_service_employee')
            ->withPivot('employee_id')
            ->withTimestamps()
            ->distinct();
    }

    // الموظفين المخصصين للشريك
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'partner_service_employee')
            ->withPivot('service_id')
            ->withTimestamps()
            ->distinct();
    }

    // جلب الموظفين لخدمة معينة
    public function employeesForService($serviceId)
    {
        return $this->employees()
            ->wherePivot('service_id', $serviceId)
            ->get();
    }

    // جلب الخدمات لموظف معين
    public function servicesForEmployee($employeeId)
    {
        return $this->services()
            ->wherePivot('employee_id', $employeeId)
            ->get();
    }

    // جلب جميع التخصيصات (service + employee)
    public function serviceEmployeeAssignments()
    {
        return $this->hasMany(PartnerServiceEmployee::class);
    }

    // من أنشأ السجل
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // من حدث السجل
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Accessors
     */
    public function getMaskedTokenAttribute(): string
    {
        return Str::mask($this->api_token, '*', 8, -8);
    }
}