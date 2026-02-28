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
            $table->boolean('allow_slot_fallback')->default(true)->after('daily_booking_limit');
            $table->unsignedInteger('slot_fallback_minutes')->default(60)->after('allow_slot_fallback');
            $table->enum('slot_fallback_direction', ['before', 'after', 'both'])
                ->default('both')->after('slot_fallback_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'allow_slot_fallback',
                'slot_fallback_minutes',
                'slot_fallback_direction',
            ]);
        });
    }
};
