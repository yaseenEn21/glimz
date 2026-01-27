<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeWeeklyInterval;
use App\Models\EmployeeWorkArea;
use App\Models\Service;
use App\Models\User;
use App\Services\RekazService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ImportRekazEmployeesSeeder extends Seeder
{
    protected RekazService $rekazService;

    public function __construct(RekazService $rekazService)
    {
        $this->rekazService = $rekazService;
    }

    public function run(): void
    {
        DB::transaction(function () {
            // جلب الموظفين من ركاز
            $result = $this->rekazService->getProviders();

            if (!$result['success']) {
                $this->command->error('فشل جلب الموظفين من ركاز: ' . ($result['error'] ?? 'خطأ غير معروف'));
                return;
            }

            $rekazProviders = $result['data'];
            $this->command->info("تم جلب " . count($rekazProviders) . " موظف من ركاز");

            // جلب admin user لإسناد created_by
            $adminUserId = User::query()
                ->where('user_type', 'admin')
                ->value('id')
                ?? User::query()->value('id');

            // جلب الخدمات المتاحة (أول 10 خدمات نشطة)
            $serviceIds = Service::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->limit(10)
                ->pluck('id')
                ->all();

            $imported = 0;
            $skipped = 0;

            foreach ($rekazProviders as $provider) {
                try {
                    // تحويل بيانات ركاز إلى صيغتنا
                    $employeeData = $this->rekazService->transformRekazProviderToEmployee($provider);

                    // تخطي إذا ما في rekaz_id
                    if (empty($employeeData['rekaz_id'])) {
                        $this->command->warn("تخطي موظف بدون rekaz_id: {$provider['name']}");
                        $skipped++;
                        continue;
                    }

                    // إنشاء أو تحديث User
                    $user = User::withTrashed()->updateOrCreate(
                        [
                            'mobile' => $employeeData['mobile'],
                        ],
                        [
                            'name' => $employeeData['name'],
                            'email' => $employeeData['email'],
                            'user_type' => $employeeData['user_type'],
                            'password' => Hash::make('password123'),
                            'is_active' => $employeeData['is_active'],
                            'gender' => 'male',
                            'created_by' => $adminUserId,
                            'updated_by' => $adminUserId,
                        ]
                    );

                    if ($user->trashed()) {
                        $user->restore();
                    }

                    // إنشاء أو تحديث Employee
                    $employee = Employee::withTrashed()->updateOrCreate(
                        [
                            'user_id' => $user->id,
                        ],
                        [
                            'is_active' => $employeeData['is_active'],
                            'created_by' => $adminUserId,
                            'updated_by' => $adminUserId,
                        ]
                    );

                    if ($employee->trashed()) {
                        $employee->restore();
                    }

                    // حفظ المعرف في جدول rekaz_mappings
                    $employee->syncWithRekaz(
                        $employeeData['rekaz_id'],
                        'Provider',
                        [
                            'original_name' => $provider['name'],
                            'user_id' => $user->id,
                        ]
                    );

                    // ✅ 1. تعيين الخدمات
                    if (count($serviceIds)) {
                        $employee->services()->syncWithPivotValues(
                            $serviceIds, 
                            ['is_active' => true]
                        );
                        $this->command->info("   → تم تعيين " . count($serviceIds) . " خدمة");
                    }

                    // ✅ 2. إضافة أوقات العمل الأسبوعية
                    $this->setupWeeklyIntervals($employee, $adminUserId);
                    $this->command->info("   → تم إضافة أوقات العمل الأسبوعية");

                    // ✅ 3. إضافة منطقة العمل
                    $this->setupWorkArea($employee, $adminUserId, $provider);
                    $this->command->info("   → تم إضافة منطقة العمل");

                    $this->command->info("✓ تم استيراد: {$provider['name']} (mobile: {$employeeData['mobile']})");
                    $imported++;

                } catch (\Exception $e) {
                    $this->command->error("✗ خطأ في استيراد {$provider['name']}: {$e->getMessage()}");
                    Log::error('Failed to import Rekaz employee', [
                        'provider' => $provider,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $skipped++;
                }
            }

            $this->command->info("\n=== النتائج ===");
            $this->command->info("تم استيراد: {$imported} موظف");
            $this->command->warn("تم تخطي: {$skipped} موظف");
            $this->command->warn("\n⚠️  كلمة المرور الافتراضية لجميع الموظفين: password123");
        });
    }

    /**
     * إعداد أوقات العمل الأسبوعية للموظف
     */
    protected function setupWeeklyIntervals(Employee $employee, int $adminUserId): void
    {
        // حذف الأوقات القديمة
        EmployeeWeeklyInterval::where('employee_id', $employee->id)->delete();

        // قالب أوقات العمل:
        // فترة صباحية: 08:00-13:30
        // استراحة: 13:30-16:00
        // فترة مسائية: 16:00-23:59
        $template = [
            ['type' => 'work', 'start' => '08:00', 'end' => '13:30'],
            ['type' => 'break', 'start' => '13:30', 'end' => '16:00'],
            ['type' => 'work', 'start' => '16:00', 'end' => '23:59'],
        ];

        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach ($days as $day) {
            foreach ($template as $interval) {
                EmployeeWeeklyInterval::create([
                    'employee_id' => $employee->id,
                    'day' => $day,
                    'type' => $interval['type'],
                    'start_time' => $interval['start'],
                    'end_time' => $interval['end'],
                    'is_active' => true,
                    'created_by' => $adminUserId,
                    'updated_by' => $adminUserId,
                ]);
            }
        }
    }

    /**
     * إعداد منطقة العمل للموظف
     */
    protected function setupWorkArea(Employee $employee, int $adminUserId, array $provider): void
    {
        // تحديد المنطقة بناءً على وصف الموظف في ركاز
        $description = strtolower($provider['description'] ?? '');
        $name = strtolower($provider['name'] ?? '');
        $text = $description . ' ' . $name;

        // مناطق مختلفة حسب المدينة
        $polygons = [
            'riyadh' => [
                ['lat' => 24.900, 'lng' => 46.500],
                ['lat' => 24.900, 'lng' => 46.900],
                ['lat' => 24.500, 'lng' => 46.900],
                ['lat' => 24.500, 'lng' => 46.500],
            ],
            'dammam' => [
                ['lat' => 26.500, 'lng' => 49.900],
                ['lat' => 26.500, 'lng' => 50.300],
                ['lat' => 26.200, 'lng' => 50.300],
                ['lat' => 26.200, 'lng' => 49.900],
            ],
            'khobar' => [
                ['lat' => 26.400, 'lng' => 50.100],
                ['lat' => 26.400, 'lng' => 50.250],
                ['lat' => 26.200, 'lng' => 50.250],
                ['lat' => 26.200, 'lng' => 50.100],
            ],
            'dhahran' => [
                ['lat' => 26.350, 'lng' => 50.100],
                ['lat' => 26.350, 'lng' => 50.200],
                ['lat' => 26.250, 'lng' => 50.200],
                ['lat' => 26.250, 'lng' => 50.100],
            ],
            'jeddah' => [
                ['lat' => 21.650, 'lng' => 39.100],
                ['lat' => 21.650, 'lng' => 39.350],
                ['lat' => 21.450, 'lng' => 39.350],
                ['lat' => 21.450, 'lng' => 39.100],
            ],
        ];

        // تحديد المنطقة بناءً على الوصف
        $polygon = null;
        
        if (str_contains($text, 'riyadh') || str_contains($text, 'north') || str_contains($text, 'east') || 
            str_contains($text, 'west') || str_contains($text, 'middle') || str_contains($text, 'malqa') || 
            str_contains($text, 'narjis') || str_contains($text, 'arid')) {
            $polygon = $polygons['riyadh'];
        } elseif (str_contains($text, 'dammam') || str_contains($text, 'qurtuba') || str_contains($text, 'munisya')) {
            $polygon = $polygons['dammam'];
        } elseif (str_contains($text, 'khobar') || str_contains($text, 'ghadir') || str_contains($text, 'nafal')) {
            $polygon = $polygons['khobar'];
        } elseif (str_contains($text, 'dhahran')) {
            $polygon = $polygons['dhahran'];
        } else {
            // افتراضي: جدة
            $polygon = $polygons['jeddah'];
        }

        // حساب الحدود
        $lats = array_map(fn($p) => (float) $p['lat'], $polygon);
        $lngs = array_map(fn($p) => (float) $p['lng'], $polygon);

        EmployeeWorkArea::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'polygon' => $polygon,
                'min_lat' => min($lats),
                'max_lat' => max($lats),
                'min_lng' => min($lngs),
                'max_lng' => max($lngs),
                'is_active' => true,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]
        );
    }
}