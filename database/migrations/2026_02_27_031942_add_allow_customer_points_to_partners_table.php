<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->boolean('allow_customer_points')
                ->default(false)
                ->after('daily_booking_limit')
                ->comment('هل يُسمح بمنح نقاط لزبائن هذا الشريك');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('allow_customer_points');
        });
    }
};
