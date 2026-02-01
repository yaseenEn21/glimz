<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('api_token', 64)->unique();
            $table->string('external_token', 64)->nullable()->unique();
            $table->string('mobile')->nullable();
            $table->string('email')->unique();
            $table->string('webhook_url')->nullable();
            $table->string('webhook_type')->default('generic');
            $table->unsignedInteger('daily_booking_limit')->default(100);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('username');
            $table->index('api_token');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};