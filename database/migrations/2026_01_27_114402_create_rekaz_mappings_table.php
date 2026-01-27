<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekaz_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('mappable_type'); // Booking, Customer, Service, Invoice, etc.
            $table->unsignedBigInteger('mappable_id'); // الـ ID المحلي
            $table->string('rekaz_id'); // الـ ID في ركاز
            $table->string('rekaz_entity_type')->nullable(); // نوع الكيان في ركاز (إذا مختلف)
            $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->timestamps();

            // Indexes
            $table->unique(['mappable_type', 'mappable_id']);
            $table->index('rekaz_id');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekaz_mappings');
    }
};