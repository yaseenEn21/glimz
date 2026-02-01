<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Partner;
use App\Models\Service;
use App\Models\User;
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
        $plateNumber = $data['plate_number'];

        $car = Car::query()
            ->where('user_id', $user->id)
            ->where('plate_number', $plateNumber)
            ->first();

        if ($car) {
            $car->update([
                'color' => $data['color'] ?? $car->color,
                'model' => $data['model'] ?? $car->model,
            ]);
        } else {
            $car = Car::create([
                'user_id' => $user->id,
                'plate_number' => $plateNumber,
                'color' => $data['color'] ?? null,
                'model' => $data['model'] ?? null,
            ]);
        }

        return $car;
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