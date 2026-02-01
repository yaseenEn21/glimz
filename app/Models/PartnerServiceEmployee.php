<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PartnerServiceEmployee extends Pivot
{
    protected $table = 'partner_service_employee';

    protected $fillable = [
        'partner_id',
        'service_id',
        'employee_id',
    ];

    /**
     * العلاقات
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}