<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Partner;
use App\Models\Service;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerBookingService
{
    /**
     * إنشاء حجز جديد من الشريك
     */
    public function createBooking(Partner $partner, array $data): array
    {
        try {
            return DB::transaction(function () use ($partner, $data) {
                // 1. التحقق من الخدمة
                $service = Service::query()
                    ->where('id', $data['service_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$service) {
                    return [
                        'success' => false,
                        'error' => 'Service not found or inactive',
                        'error_code' => 'SERVICE_NOT_FOUND',
                    ];
                }

                // 2. التحقق من أن الشريك مخصص له هذه الخدمة
                $hasService = $partner->serviceEmployeeAssignments()
                    ->where('service_id', $service->id)
                    ->exists();

                if (!$hasService) {
                    return [
                        'success' => false,
                        'error' => 'Partner is not authorized for this service',
                        'error_code' => 'SERVICE_NOT_AUTHORIZED',
                    ];
                }

                // 3. التحقق من الموظف
                if (isset($data['employee_id'])) {
                    $hasEmployee = $partner->serviceEmployeeAssignments()
                        ->where('service_id', $service->id)
                        ->where('employee_id', $data['employee_id'])
                        ->exists();

                    if (!$hasEmployee) {
                        return [
                            'success' => false,
                            'error' => 'Employee not authorized for this service',
                            'error_code' => 'EMPLOYEE_NOT_AUTHORIZED',
                        ];
                    }
                }

                // 4. إنشاء/تحديث العميل
                $customer = $this->createOrUpdateCustomer($data['customer']);

                // 5. إنشاء/تحديث السيارة
                $car = $this->createOrUpdateCar($customer, $data['car']);

                // 6. إنشاء/تحديث العنوان
                $address = $this->createOrUpdateAddress($customer, $data['address']);

                // 7. تحويل التاريخ والوقت
                $day = Carbon::createFromFormat('d-m-Y', $data['date']);
                $dbDate = $day->toDateString();
                $startTime = $data['start_time'];
                $duration = (int) $service->duration_minutes;
                $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
                    ->addMinutes($duration)
                    ->format('H:i');

                // 8. Pricing
                $pricing = app(BookingPricingService::class)
                    ->resolve($service, $customer, $address, $startTime);

                $finalUnitPrice = (float) $pricing['final_unit_price'];

                // 9. إنشاء الحجز
                $booking = Booking::create([
                    'partner_id' => $partner->id,
                    'external_id' => $data['external_id'],

                    'user_id' => $customer->id,
                    'car_id' => $car->id,
                    'address_id' => $address->id,
                    'service_id' => $service->id,
                    'employee_id' => $data['employee_id'] ?? null,

                    'zone_id' => $pricing['zone_id'],
                    'time_period' => $pricing['time_period'],

                    'service_unit_price_snapshot' => $pricing['unit_price'],
                    'service_discounted_price_snapshot' => $pricing['discounted_price'],
                    'service_final_price_snapshot' => $finalUnitPrice,
                    'service_points_snapshot' => (int) ($service->points ?? 0),

                    'service_charge_amount_snapshot' => $finalUnitPrice,
                    'service_pricing_source' => $pricing['pricing_source'],
                    'service_pricing_meta' => [
                        'applied_id' => $pricing['applied_id'],
                        'lat' => (float) $address->lat,
                        'lng' => (float) $address->lng,
                    ],

                    'status' => 'confirmed', // الشريك يؤكد مباشرة
                    'confirmed_at' => now(),

                    'booking_date' => $dbDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration_minutes' => $duration,

                    'service_price_snapshot' => (float) $service->price,
                    'products_subtotal_snapshot' => 0,
                    'subtotal_snapshot' => $finalUnitPrice,
                    'total_snapshot' => $finalUnitPrice,
                    'currency' => 'SAR',

                    'meta' => [
                        'partner_booking' => true,
                        'partner_name' => $partner->name,
                        'customer_notes' => $data['notes'] ?? null,
                    ],

                    'created_by' => null,
                    'updated_by' => null,
                ]);

                // 10. تحديث Cache للحد اليومي
                $cacheKey = "partner_{$partner->id}_daily_bookings_" . now()->format('Y-m-d');
                \Cache::forget($cacheKey);

                Log::info('Partner booking created', [
                    'partner_id' => $partner->id,
                    'booking_id' => $booking->id,
                    'external_id' => $data['external_id'],
                ]);

                return [
                    'success' => true,
                    'booking' => $booking->load(['service', 'employee.user', 'car', 'address']),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Partner booking creation failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create booking: ' . $e->getMessage(),
                'error_code' => 'BOOKING_CREATE_FAILED',
            ];
        }
    }

    /**
     * تحديث وقت الحجز
     */
    public function rescheduleBooking(Partner $partner, string $externalId, array $data): array
    {
        try {
            $booking = Booking::query()
                ->where('partner_id', $partner->id)
                ->where('external_id', $externalId)
                ->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'error' => 'Booking not found',
                    'error_code' => 'BOOKING_NOT_FOUND',
                ];
            }

            if (in_array($booking->status, ['cancelled', 'completed'])) {
                return [
                    'success' => false,
                    'error' => 'Booking cannot be rescheduled',
                    'error_code' => 'BOOKING_NOT_RESCHEDULABLE',
                ];
            }

            $day = Carbon::createFromFormat('d-m-Y', $data['date']);
            $dbDate = $day->toDateString();
            $startTime = $data['start_time'];
            $duration = (int) $booking->duration_minutes;
            $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
                ->addMinutes($duration)
                ->format('H:i');

            // التحقق من الموظف إذا تم تغييره
            if (isset($data['employee_id'])) {
                $hasEmployee = $partner->serviceEmployeeAssignments()
                    ->where('service_id', $booking->service_id)
                    ->where('employee_id', $data['employee_id'])
                    ->exists();

                if (!$hasEmployee) {
                    return [
                        'success' => false,
                        'error' => 'Employee not authorized for this service',
                        'error_code' => 'EMPLOYEE_NOT_AUTHORIZED',
                    ];
                }
            }

            $booking->update([
                'booking_date' => $dbDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'employee_id' => $data['employee_id'] ?? $booking->employee_id,
            ]);

            Log::info('Partner booking rescheduled', [
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'external_id' => $externalId,
            ]);

            return [
                'success' => true,
                'booking' => $booking->fresh(['service', 'employee.user', 'car', 'address']),
            ];
        } catch (\Exception $e) {
            Log::error('Partner booking reschedule failed', [
                'partner_id' => $partner->id,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to reschedule booking: ' . $e->getMessage(),
                'error_code' => 'BOOKING_RESCHEDULE_FAILED',
            ];
        }
    }

    /**
     * إلغاء الحجز
     */
    public function cancelBooking(Partner $partner, string $externalId, ?string $reason = null): array
    {
        try {
            $booking = Booking::query()
                ->where('partner_id', $partner->id)
                ->where('external_id', $externalId)
                ->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'error' => 'Booking not found',
                    'error_code' => 'BOOKING_NOT_FOUND',
                ];
            }

            if ($booking->status === 'cancelled') {
                return [
                    'success' => true,
                    'booking' => $booking,
                    'message' => 'Booking already cancelled',
                ];
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason ?? 'Cancelled by partner',
            ]);

            Log::info('Partner booking cancelled', [
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'external_id' => $externalId,
            ]);

            return [
                'success' => true,
                'booking' => $booking->fresh(['service', 'employee.user']),
            ];
        } catch (\Exception $e) {
            Log::error('Partner booking cancellation failed', [
                'partner_id' => $partner->id,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to cancel booking: ' . $e->getMessage(),
                'error_code' => 'BOOKING_CANCEL_FAILED',
            ];
        }
    }

    /**
     * إنشاء/تحديث العميل
     */
    protected function createOrUpdateCustomer(array $data): User
    {
        // تنسيق رقم الموبايل
        $mobile = $this->formatMobile($data['mobile']);

        $user = User::query()
            ->where('mobile', $mobile)
            ->first();

        if ($user) {
            // تحديث البيانات إذا تغيرت
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? $user->email,
            ]);
        } else {
            // إنشاء عميل جديد
            $user = User::create([
                'name' => $data['name'],
                'mobile' => $mobile,
                'email' => $data['email'] ?? null,
                'password' => bcrypt(\Str::random(16)), // رقم سري عشوائي
                'user_type' => 'customer',
                'is_active' => true,
                'notification' => true,
            ]);
        }

        return $user;
    }

    /**
     * إنشاء/تحديث السيارة
     */
    protected function createOrUpdateCar(User $user, array $data): Car
    {
        // 1. فصل plate_number و plate_letters
        $plateParts = $this->parsePlateNumber($data['plate_number']);

        // 2. البحث عن vehicle_make_id و vehicle_model_id
        $vehicleIds = $this->resolveVehicleIds($data['model'] ?? null);

        // 3. البحث عن السيارة الموجودة
        $car = Car::query()
            ->where('user_id', $user->id)
            ->where('plate_number', $plateParts['number'])
            ->where('plate_letters', $plateParts['letters'])
            ->first();

        if ($car) {
            // تحديث السيارة الموجودة
            $car->update([
                'color' => $data['color'] ?? $car->color,
                'vehicle_make_id' => $vehicleIds['make_id'] ?? $car->vehicle_make_id,
                'vehicle_model_id' => $vehicleIds['model_id'] ?? $car->vehicle_model_id,
            ]);
        } else {
            // إنشاء سيارة جديدة
            $car = Car::create([
                'user_id' => $user->id,
                'vehicle_make_id' => $vehicleIds['make_id'],
                'vehicle_model_id' => $vehicleIds['model_id'],
                'color' => $data['color'] ?? null,
                'plate_number' => $plateParts['number'],
                'plate_letters' => $plateParts['letters'],
                'plate_letters_ar' => $plateParts['letters_ar'],
                'is_default' => false,
            ]);
        }

        return $car;
    }

    /**
     * فصل رقم اللوحة إلى أرقام وحروف
     */
    protected function parsePlateNumber(string $plateNumber): array
    {
        // إزالة المسافات الزائدة
        $plateNumber = trim($plateNumber);

        // فصل الحروف عن الأرقام
        // مثال: "أ ب ج 1234" أو "ABC 1234"

        // استخراج الأرقام
        preg_match_all('/\d+/', $plateNumber, $numbersMatch);
        $numbers = implode('', $numbersMatch[0] ?? []);

        // استخراج الحروف (عربي وإنجليزي)
        preg_match_all('/[\x{0600}-\x{06FF}a-zA-Z]+/u', $plateNumber, $lettersMatch);
        $letters = implode(' ', $lettersMatch[0] ?? []);

        // تحديد إذا كانت الحروف عربية
        $isArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $letters);

        return [
            'number' => $numbers ?: '0000',
            'letters' => $letters ?: 'XXX',
            'letters_ar' => $isArabic ? $letters : null,
        ];
    }

    /**
     * البحث عن vehicle_make_id و vehicle_model_id
     */
    protected function resolveVehicleIds(?string $modelString): array
    {
        if (!$modelString) {
            return $this->getUnknownVehicleIds();
        }

        $model = VehicleModel::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', ['%' . mb_strtolower($modelString) . '%'])
            ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', ['%' . strtolower($modelString) . '%'])
            ->first();

        if ($model) {
            return [
                'make_id' => $model->vehicle_make_id,
                'model_id' => $model->id,
            ];
        }

        // استراتيجية 2: محاولة فصل Make و Model
        // مثال: "تويوتا كامري" → make: "تويوتا", model: "كامري"
        $parts = preg_split('/\s+/', trim($modelString), 2);

        if (count($parts) >= 2) {
            $makeName = $parts[0];
            $modelName = $parts[1];

            $make = VehicleMake::query()
                ->where('is_active', true)
                ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) = ?', [mb_strtolower($makeName)])
                ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) = ?', [strtolower($makeName)])
                ->first();

            if ($make) {
                $model = VehicleModel::query()
                    ->where('vehicle_make_id', $make->id)
                    ->where('is_active', true)
                    ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', ['%' . mb_strtolower($modelName) . '%'])
                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', ['%' . strtolower($modelName) . '%'])
                    ->first();

                if ($model) {
                    return [
                        'make_id' => $make->id,
                        'model_id' => $model->id,
                    ];
                }

                // لو لقينا Make بس، نستخدم أول موديل منه
                $firstModel = VehicleModel::query()
                    ->where('vehicle_make_id', $make->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();

                if ($firstModel) {
                    return [
                        'make_id' => $make->id,
                        'model_id' => $firstModel->id,
                    ];
                }
            }
        }

        // استراتيجية 3: البحث عن Make فقط
        $make = VehicleMake::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', ['%' . mb_strtolower($modelString) . '%'])
            ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', ['%' . strtolower($modelString) . '%'])
            ->first();

        if ($make) {
            $firstModel = VehicleModel::query()
                ->where('vehicle_make_id', $make->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();

            if ($firstModel) {
                return [
                    'make_id' => $make->id,
                    'model_id' => $firstModel->id,
                ];
            }
        }

        // إذا ما لقينا: استخدام Unknown
        return $this->getUnknownVehicleIds();
    }

    /**
     * الحصول على Unknown vehicle make/model
     */
    protected function getUnknownVehicleIds(): array
    {
        static $unknownMake = null;
        static $unknownModel = null;

        if ($unknownMake === null) {
            // البحث أو إنشاء "Unknown" make
            $unknownMake = VehicleMake::query()
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(name, "$.en")) = ?', ['Unknown'])
                ->first();

            if (!$unknownMake) {
                $unknownMake = VehicleMake::create([
                    'external_id' => 99999,
                    'name' => ['ar' => 'غير محدد', 'en' => 'Unknown'],
                    'is_active' => true,
                    'sort_order' => 9999,
                ]);
            }
        }

        if ($unknownModel === null) {

            $unknownModel = VehicleModel::query()
                ->where('vehicle_make_id', $unknownMake->id)
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(name, "$.en")) = ?', ['Unknown'])
                ->first();

            if (!$unknownModel) {
                $unknownModel = VehicleModel::create([
                    'vehicle_make_id' => $unknownMake->id,
                    'external_id' => 99999,
                    'name' => ['ar' => 'غير محدد', 'en' => 'Unknown'],
                    'is_active' => true,
                    'sort_order' => 9999,
                ]);
            }

        }

        return [
            'make_id' => $unknownMake->id,
            'model_id' => $unknownModel->id,
        ];
    }

    /**
     * إنشاء/تحديث العنوان
     */
    protected function createOrUpdateAddress(User $user, array $data): Address
    {
        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        // البحث عن عنوان قريب جداً (نفس الإحداثيات تقريباً)
        $address = Address::query()
            ->where('user_id', $user->id)
            ->whereRaw('ABS(lat - ?) < 0.0001', [$lat])
            ->whereRaw('ABS(lng - ?) < 0.0001', [$lng])
            ->first();

        if ($address) {
            $address->update([
                'address' => $data['address'] ?? $address->address,
            ]);
        } else {
            $address = Address::create([
                'user_id' => $user->id,
                'address' => $data['address'] ?? 'Address',
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }

        return $address;
    }

    /**
     * تنسيق رقم الموبايل
     */
    protected function formatMobile(string $mobile): string
    {
        // إزالة كل شيء غير الأرقام
        $mobile = preg_replace('/[^0-9]/', '', $mobile);

        // إزالة 0 من البداية
        if (substr($mobile, 0, 1) === '0') {
            $mobile = substr($mobile, 1);
        }

        // إزالة 966 من البداية إذا موجودة
        if (substr($mobile, 0, 3) === '966') {
            $mobile = substr($mobile, 3);
        }

        // إرجاع الرقم بصيغة دولية
        return '+966' . $mobile;
    }
}