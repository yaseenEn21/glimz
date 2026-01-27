<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\RekazService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportRekazProductsSeeder extends Seeder
{
    protected RekazService $rekazService;

    public function __construct(RekazService $rekazService)
    {
        $this->rekazService = $rekazService;
    }

    public function run(): void
    {
        DB::transaction(function () {
            // جلب المنتجات من ركاز
            $result = $this->rekazService->getProducts();

            if (!$result['success']) {
                $this->command->error('فشل جلب المنتجات من ركاز: ' . ($result['error'] ?? 'خطأ غير معروف'));
                return;
            }

            $rekazProducts = $result['data'];
            $this->command->info("تم جلب {$result['total_count']} منتج من ركاز");

            // جلب user لإسناد created_by
            $userId = User::query()
                ->where('user_type', 'admin')
                ->value('id')
                ?? User::query()->value('id');

            // جلب التصنيفات
            $categoriesMap = $this->getCategoriesMap();

            $imported = 0;
            $skipped = 0;

            foreach ($rekazProducts as $product) {
                try {
                    // ✅ فلترة: استيراد Merchandise فقط
                    if (($product['typeString'] ?? '') !== 'Merchandise') {
                        $skipped++;
                        continue;
                    }

                    // تحويل بيانات ركاز إلى صيغتنا
                    $productData = $this->rekazService->transformRekazProductToProduct($product);

                    // تخطي إذا ما في rekaz_id
                    if (empty($productData['rekaz_id'])) {
                        $this->command->warn("تخطي منتج بدون rekaz_id: {$product['name']}");
                        $skipped++;
                        continue;
                    }

                    // تحديد التصنيف (nullable)
                    $categoryId = $this->determineCategoryId($product, $categoriesMap);

                    // إنشاء أو تحديث المنتج
                    $productModel = Product::withTrashed()->updateOrCreate(
                        [
                            'name' => $productData['name'],
                        ],
                        [
                            'product_category_id' => $categoryId,
                            'description' => $productData['description'],
                            'cost' => 0, // ما في cost في ركاز، خليه 0
                            'price' => $productData['price'],
                            'discounted_price' => $productData['discounted_price'],
                            'is_active' => $productData['is_active'],
                            'max_qty_per_booking' => $productData['max_qty_per_booking'],
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]
                    );

                    // استرجاع إذا محذوف
                    if ($productModel->trashed()) {
                        $productModel->restore();
                    }

                    // حفظ المعرف في جدول rekaz_mappings
                    $productModel->syncWithRekaz(
                        $productData['rekaz_id'],
                        $productData['type'],
                        [
                            'rekaz_product_id' => $product['id'],
                            'original_name' => $product['name'],
                            'type' => $productData['type'],
                            'max_qty' => $productData['max_qty_per_booking'],
                        ]
                    );

                    $this->command->info("✓ تم استيراد: {$product['name']}");
                    $imported++;

                } catch (\Exception $e) {
                    $this->command->error("✗ خطأ في استيراد {$product['name']}: {$e->getMessage()}");
                    Log::error('Failed to import Rekaz product', [
                        'product' => $product,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("\n=== النتائج ===");
            $this->command->info("تم استيراد: {$imported} منتج");
            $this->command->warn("تم تخطي: {$skipped} منتج");
        });
    }

    /**
     * جلب خريطة التصنيفات
     */
    protected function getCategoriesMap(): array
    {
        return ProductCategory::query()
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
     * تحديد التصنيف المناسب للمنتج (nullable)
     */
    protected function determineCategoryId(array $product, array $categoriesMap): ?int
    {
        // محاولة تحديد التصنيف من اسم المنتج
        $productName = $product['nameAr'] ?? $product['name'];
        
        // منطق بسيط للتصنيف - عدله حسب احتياجك
        $categoryKeywords = [
            'إضافات' => ['مناديل', 'فواحة', 'تغليف', 'علبة'],
            'اكسسوارات' => ['حامل', 'قفازات', 'طاولة', 'مبخرة'],
            'منظفات' => ['مزيل', 'منظف'],
        ];

        foreach ($categoryKeywords as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($productName, $keyword) !== false) {
                    if (isset($categoriesMap[$categoryName])) {
                        return $categoriesMap[$categoryName];
                    }
                }
            }
        }

        // إرجاع null إذا ما لقينا تصنيف مناسب (لأن الحقل nullable)
        return null;
    }
}