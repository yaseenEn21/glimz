<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Services\RekazService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportRekazBranchesSeeder extends Seeder
{
    protected RekazService $rekazService;

    public function __construct(RekazService $rekazService)
    {
        $this->rekazService = $rekazService;
    }

    public function run(): void
    {
        DB::transaction(function () {
            // جلب الفروع من ركاز
            $result = $this->rekazService->getBranches();

            if (!$result['success']) {
                $this->command->error('فشل جلب الفروع من ركاز: ' . ($result['error'] ?? 'خطأ غير معروف'));
                return;
            }

            $rekazBranches = $result['data'];
            $this->command->info("تم جلب " . count($rekazBranches) . " فرع من ركاز");

            // جلب admin user لإسناد created_by
            $adminUserId = User::query()
                ->where('user_type', 'admin')
                ->value('id')
                ?? User::query()->value('id');

            $imported = 0;
            $skipped = 0;

            foreach ($rekazBranches as $branch) {
                try {
                    // تحويل بيانات ركاز إلى صيغتنا
                    $branchData = $this->rekazService->transformRekazBranchToBranch($branch);

                    // تخطي إذا ما في rekaz_id
                    if (empty($branchData['rekaz_id'])) {
                        $this->command->warn("تخطي فرع بدون rekaz_id: {$branch['name']}");
                        $skipped++;
                        continue;
                    }

                    // إنشاء أو تحديث الفرع
                    $branchModel = Branch::withTrashed()->updateOrCreate(
                        [
                            'name' => $branchData['name'],
                        ],
                        [
                            'created_by' => $adminUserId,
                            'updated_by' => $adminUserId,
                        ]
                    );

                    // استرجاع إذا محذوف
                    if ($branchModel->trashed()) {
                        $branchModel->restore();
                    }

                    // حفظ المعرف في جدول rekaz_mappings
                    $branchModel->syncWithRekaz(
                        $branchData['rekaz_id'],
                        'Branch',
                        [
                            'original_name' => $branch['name'],
                            'address_url' => $branchData['address_url'],
                        ]
                    );

                    $this->command->info("✓ تم استيراد: {$branch['name']} ({$branchData['name']['ar']})");
                    $imported++;

                } catch (\Exception $e) {
                    $this->command->error("✗ خطأ في استيراد {$branch['name']}: {$e->getMessage()}");
                    Log::error('Failed to import Rekaz branch', [
                        'branch' => $branch,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("\n=== النتائج ===");
            $this->command->info("تم استيراد: {$imported} فرع");
            $this->command->warn("تم تخطي: {$skipped} فرع");
        });
    }
}