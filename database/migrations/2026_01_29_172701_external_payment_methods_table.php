<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_payment_methods', function (Blueprint $table) {
            $table->id();

            // اسم وسيلة الدفع
            $table->json('name'); // ['ar' => 'تحويل بنكي', 'en' => 'Bank Transfer']
            $table->json('description')->nullable(); // وصف إضافي

            // معرف فريد للوسيلة (مثل: bank_transfer, cash, cheque, pos)
            $table->string('code')->unique();

            // أيقونة أو صورة
            $table->string('icon')->nullable();

            // هل تتطلب معلومات إضافية؟ (رقم التحويل، صورة الإيصال...)
            $table->boolean('requires_reference')->default(false);
            $table->boolean('requires_attachment')->default(false);

            // معلومات الحساب البنكي (اختياري)
            $table->json('bank_details')->nullable(); // IBAN, Bank Name, etc.

            // الحالة والترتيب
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // ملاحظات للموظفين
            $table->text('internal_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_payment_methods');
    }
};