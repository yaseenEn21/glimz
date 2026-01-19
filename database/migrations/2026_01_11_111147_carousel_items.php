<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carousel_items', function (Blueprint $table) {
            $table->id();

            // محتوى متعدد اللغات
            $table->json('label')->nullable();
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('hint')->nullable();
            $table->json('cta')->nullable();

            $table->nullableMorphs('carouselable'); // carouselable_type, carouselable_id

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // $table->timestamp('starts_at')->nullable();
            // $table->timestamp('ends_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            // $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carousel_items');
    }
};