<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\User;
use App\Services\RekazService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportRekazPackagesSeeder extends Seeder
{
    protected RekazService $rekazService;

    public function __construct(RekazService $rekazService)
    {
        $this->rekazService = $rekazService;
    }

    public function run(): void
    {
        DB::transaction(function () {
            // جلب الباقات من ركاز
            $result = $this->rekazService->getProducts();

            if (!$result['success']) {
                $this->command->error('فشل جلب الباقات من ركاز: ' . ($result['error'] ?? 'خطأ غير معروف'));
                return;
            }

            $rekazProducts = $result['data'];
            $this->command->info("تم جلب {$result['total_count']} عنصر من ركاز");

            // جلب user لإسناد created_by
            $userId = User::query()
                ->where('user_type', 'admin')
                ->value('id')
                ?? User::query()->value('id');

            $imported = 0;
            $skipped = 0;

            foreach ($rekazProducts as $product) {
                try {
                    // ✅ فلترة: استيراد Subscription فقط
                    if (($product['typeString'] ?? '') !== 'Subscription') {
                        $skipped++;
                        continue;
                    }

                    // تحويل بيانات ركاز إلى صيغتنا
                    $packageData = $this->rekazService->transformRekazPackageToPackage($product);

                    // تخطي إذا ما في rekaz_id
                    if (empty($packageData['rekaz_id'])) {
                        $this->command->warn("تخطي باقة بدون rekaz_id: {$product['name']}");
                        $skipped++;
                        continue;
                    }

                    // تخطي إذا ما في package (لازم يكون hasPackage = true)
                    if (!$packageData['has_package']) {
                        $this->command->warn("تخطي باقة بدون package: {$product['name']}");
                        $skipped++;
                        continue;
                    }

                    // حساب sort_order بناءً على عدد الغسلات
                    $sortOrder = $this->calculateSortOrder($packageData['washes_count']);

                    // إنشاء أو تحديث الباقة
                    $package = Package::withTrashed()->updateOrCreate(
                        [
                            'name' => $packageData['name'],
                        ],
                        [
                            'label' => $packageData['label'],
                            'description' => $packageData['description'],
                            'price' => $packageData['price'],
                            'discounted_price' => $packageData['discounted_price'],
                            'validity_days' => $packageData['validity_days'],
                            'washes_count' => $packageData['washes_count'],
                            'sort_order' => $sortOrder,
                            'is_active' => $packageData['is_active'],
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]
                    );

                    // استرجاع إذا محذوف
                    if ($package->trashed()) {
                        $package->restore();
                    }

                    // حفظ المعرف في جدول rekaz_mappings
                    $package->syncWithRekaz(
                        $packageData['rekaz_id'],
                        $packageData['type'],
                        [
                            'rekaz_product_id' => $product['id'],
                            'original_name' => $product['name'],
                            'type' => $packageData['type'],
                            'washes_count' => $packageData['washes_count'],
                            'validity_days' => $packageData['validity_days'],
                            'billing_period' => $product['pricing'][0]['billingPeriod'] ?? null,
                        ]
                    );

                    $pricePerWash = $packageData['washes_count'] > 0 
                        ? round(($packageData['discounted_price'] ?? $packageData['price']) / $packageData['washes_count'], 2)
                        : 0;

                    $this->command->info(
                        "✓ تم استيراد: {$product['name']} " .
                        "({$packageData['washes_count']} غسلات - {$pricePerWash} ريال/غسلة)"
                    );
                    $imported++;

                } catch (\Exception $e) {
                    $this->command->error("✗ خطأ في استيراد {$product['name']}: {$e->getMessage()}");
                    Log::error('Failed to import Rekaz package', [
                        'product' => $product,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("\n=== النتائج ===");
            $this->command->info("تم استيراد: {$imported} باقة");
            $this->command->warn("تم تخطي: {$skipped} عنصر");
        });
    }

    /**
     * حساب الترتيب بناءً على عدد الغسلات
     * الباقات الأصغر تظهر أولاً
     */
    protected function calculateSortOrder(int $washesCount): int
    {
        return match(true) {
            $washesCount <= 4 => 10,
            $washesCount <= 12 => 20,
            $washesCount <= 24 => 30,
            $washesCount <= 48 => 40,
            default => 50,
        };
    }
}