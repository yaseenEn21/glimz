<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotional_notifications', function (Blueprint $table) {
            $table->id();

            // محتوى الإشعار
            $table->json('title'); // ['ar' => '...', 'en' => '...']
            $table->json('body');  // ['ar' => '...', 'en' => '...']

            // الجمهور المستهدف
            $table->enum('target_type', ['specific_users', 'all_users'])->default('all_users');
            $table->json('target_user_ids')->nullable(); // [1, 2, 3, ...] للمستخدمين المحددين

            // الربط (Link Target)
            $table->string('linkable_type')->nullable(); // App\Models\Service, App\Models\Package, etc.
            $table->unsignedBigInteger('linkable_id')->nullable();

            // الحالة والجدولة
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // إحصائيات
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('successful_sends')->default(0);
            $table->unsignedInteger('failed_sends')->default(0);

            // ملاحظات داخلية (للإدمن فقط)
            $table->text('internal_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
            $table->index(['linkable_type', 'linkable_id']);
            $table->index(['target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotional_notifications');
    }
};