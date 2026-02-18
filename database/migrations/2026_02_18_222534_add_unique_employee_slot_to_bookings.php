<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
{
    Schema::table('bookings', function (Blueprint $table) {
        // 1. شيل الـ FK أولاً
        $table->dropForeign(['employee_id']);
        
        // 2. الآن تقدر تحذف الـ index
        $table->dropIndex(['employee_id', 'booking_date', 'start_time']);
        
        // 3. أضف الـ unique
        $table->unique(
            ['employee_id', 'booking_date', 'start_time'],
            'unique_employee_slot'
        );
        
        // 4. رجّع الـ FK
        $table->foreign('employee_id')
              ->references('id')
              ->on('employees')
              ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropForeign(['employee_id']);
        $table->dropUnique('unique_employee_slot');
        $table->index(['employee_id', 'booking_date', 'start_time']);
        $table->foreign('employee_id')
              ->references('id')
              ->on('employees')
              ->nullOnDelete();
    });
}
};
