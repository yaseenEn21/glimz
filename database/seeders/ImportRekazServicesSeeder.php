<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\RekazService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportRekazServicesSeeder extends Seeder
{
    protected RekazService $rekazService;

    public function __construct(RekazService $rekazService)
    {
        $this->rekazService = $rekazService;
    }

    public function run(): void
    {
        DB::transaction(function () {
            // جلب الخدمات من ركاز
            $result = $this->rekazService->getProducts();

            if (!$result['success']) {
                $this->command->error('فشل جلب الخدمات من ركاز: ' . ($result['error'] ?? 'خطأ غير معروف'));
                return;
            }

            $rekazProducts = $result['data'];
            $this->command->info("تم جلب {$result['total_count']} خدمة من ركاز");

            // جلب user لإسناد created_by
            $userId = User::query()
                ->where('user_type', 'admin')
                ->value('id')
                ?? User::query()->value('id');

            // تصنيف الخدمات حسب النوع
            $categoriesMap = $this->getCategoriesMap();

            $imported = 0;
            $skipped = 0;

            foreach ($rekazProducts as $product) {
                try {
                    // ✅ فلترة: استيراد Reservation فقط
                    if (($product['typeString'] ?? '') !== 'Reservation') {
                        $this->command->warn("تخطي خدمة من نوع: {$product['typeString']} - {$product['name']}");
                        $skipped++;
                        continue;
                    }

                    // تحويل بيانات ركاز إلى صيغتنا
                    $serviceData = $this->rekazService->transformRekazProductToService($product);

                    // تخطي إذا ما في rekaz_id
                    if (empty($serviceData['rekaz_id'])) {
                        $this->command->warn("تخطي خدمة بدون rekaz_id: {$product['name']}");
                        $skipped++;
                        continue;
                    }

                    // تحديد التصنيف بناءً على النوع
                    $categoryId = $this->determineCategoryId(
                        $product, 
                        $categoriesMap
                    );

                    // إنشاء أو تحديث الخدمة
                    $service = Service::withTrashed()->updateOrCreate(
                        [
                            'name' => $serviceData['name'],
                        ],
                        [
                            'service_category_id' => $categoryId,
                            'description' => $serviceData['description'],
                            'duration_minutes' => $serviceData['duration_minutes'],
                            'price' => $serviceData['price'],
                            'discounted_price' => $serviceData['discounted_price'],
                            'is_active' => $serviceData['is_active'],
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]
                    );

                    // استرجاع إذا محذوف
                    if ($service->trashed()) {
                        $service->restore();
                    }

                    // حفظ المعرف في جدول rekaz_mappings
                    $service->syncWithRekaz(
                        $serviceData['rekaz_id'],
                        $serviceData['type'],
                        [
                            'rekaz_product_id' => $product['id'],
                            'original_name' => $product['name'],
                            'type' => $serviceData['type'],
                        ]
                    );

                    $this->command->info("✓ تم استيراد: {$product['name']}");
                    $imported++;

                } catch (\Exception $e) {
                    $this->command->error("✗ خطأ في استيراد {$product['name']}: {$e->getMessage()}");
                    Log::error('Failed to import Rekaz service', [
                        'product' => $product,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("\n=== النتائج ===");
            $this->command->info("تم استيراد: {$imported} خدمة");
            $this->command->warn("تم تخطي: {$skipped} خدمة");
        });
    }

    /**
     * جلب خريطة التصنيفات
     */
    protected function getCategoriesMap(): array
    {
        return ServiceCategory::query()
            ->get(['id', 'name'])
            ->mapWithKeys(function ($category) {
                $arName = $category->name['ar'] ?? '';
                $enName = $category->name['en'] ?? '';
                
                return [
                    $arName => $category->id,
                    $enName => $category->id,
                ];
            })
            ->toArray();
    }

    /**
     * تحديد التصنيف المناسب للخدمة
     */
    protected function determineCategoryId(array $product, array $categoriesMap): int
    {
        // بما أننا نستورد Reservation فقط، نستخدم تصنيف افتراضي
        $defaultCategories = ['غسيل خارجي', 'Exterior Wash', 'خدمات', 'Services'];

        foreach ($defaultCategories as $catName) {
            if (isset($categoriesMap[$catName])) {
                return $categoriesMap[$catName];
            }
        }

        // إذا ما لقينا، نرجع أول تصنيف متاح
        return array_values($categoriesMap)[0] 
            ?? ServiceCategory::query()->value('id')
            ?? 1;
    }
}