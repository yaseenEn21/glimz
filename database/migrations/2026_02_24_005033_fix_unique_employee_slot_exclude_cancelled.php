<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique('unique_employee_slot');
        });

        DB::statement("
        ALTER TABLE bookings
        ADD COLUMN employee_slot_key VARCHAR(100)
            GENERATED ALWAYS AS (
                IF(status = 'cancelled', NULL, CONCAT(employee_id, '-', booking_date, '-', start_time))
            ) VIRTUAL,
        ADD UNIQUE KEY unique_employee_slot (employee_slot_key)
    ");

        Schema::table('bookings', function (Blueprint $table) {
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
        });

        DB::statement("
        ALTER TABLE bookings
        DROP INDEX unique_employee_slot,
        DROP COLUMN employee_slot_key
    ");

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }
};
