<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;

class SlotService
{

    public function getSlots(string $date, int $serviceId, float $lat, float $lng, ?int $stepMinutes = null, string $mode = 'blocks', ?int $excludeBookingId = null, ?int $partnerId = null): array
    {
        $tz = config('app.timezone', 'UTC');
        $day = Carbon::createFromFormat('d-m-Y', $date, $tz);
        $dbDate = $day->toDateString();
        $nextDbDate = $day->copy()->addDay()->toDateString();

        $service = Service::query()
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->first();

        if (!$service) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $date,
                    'service_id' => $serviceId,
                    'error_code' => 'SERVICE_NOT_FOUND',
                    'error' => 'Service not found',
                ],
            ];
        }

        $duration = (int) $service->duration_minutes;
        $step = $stepMinutes ?? (int) config('booking.slot_step_minutes', 60);
        $weekday = $this->carbonToDayEnum($day);

        $now = Carbon::now($tz)->startOfMinute();

        if ($day->lt($now->copy()->startOfDay())) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $date,
                    'service_id' => $serviceId,
                    'error_code' => 'DATE_IN_PAST',
                    'error' => 'Date is in the past',
                ],
            ];
        }

        $cutoffMinutes = null;
        if ($day->isSameDay($now)) {
            $nowMinutes = ($now->hour * 60) + $now->minute;
            $lead = (int) config('booking.min_lead_minutes', 0);
            $nowMinutes += $lead;
            $cutoffMinutes = (int) (ceil($nowMinutes / $step) * $step);
        }

        $baseQuery = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn($q) => $q->where('is_active', true)->where('user_type', 'biker'))
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId)
                    ->where('employee_services.is_active', 1);
            });

        if ($partnerId) {
            $baseQuery->whereHas('partnerAssignments', function ($q) use ($partnerId, $serviceId) {
                $q->where('partner_id', $partnerId)
                    ->where('service_id', $serviceId);
            });
        }

        $employeesForServiceCount = (clone $baseQuery)->count();

        $employees = (clone $baseQuery)
            ->whereHas('workArea', function ($q) use ($lat, $lng) {
                $q->where('is_active', true)
                    ->where('min_lat', '<=', $lat)
                    ->where('max_lat', '>=', $lat)
                    ->where('min_lng', '<=', $lng)
                    ->where('max_lng', '>=', $lng);
            })
            ->with([
                'user:id,name',
                'workArea:id,employee_id,polygon,min_lat,max_lat,min_lng,max_lng',
                'weeklyIntervals' => function ($q) use ($weekday) {
                    $q->where('day', $weekday)->where('is_active', true);
                },
                'timeBlocks' => function ($q) use ($dbDate) {
                    $q->where('date', $dbDate)->where('is_active', true);
                },
            ])
            ->get();

        if ($employees->isEmpty()) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $date,
                    'day' => $weekday,
                    'service_id' => $serviceId,
                    'lat' => (string) $lat,
                    'lng' => (string) $lng,
                    'employees_for_service' => $employeesForServiceCount,
                    'employees_in_bbox' => 0,
                    'employees_in_polygon' => 0,
                    'error_code' => 'OUT_OF_COVERAGE',
                    'error' => 'Address is outside service coverage area (bbox)',
                ],
            ];
        }

        $candidates = $employees->filter(function ($emp) use ($lat, $lng) {
            $poly = $emp->workArea?->polygon ?? [];
            return $this->pointInPolygon($lat, $lng, $poly);
        })->values();

        if ($candidates->isEmpty()) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $date,
                    'day' => $weekday,
                    'service_id' => $serviceId,
                    'lat' => (string) $lat,
                    'lng' => (string) $lng,
                    'employees_for_service' => $employeesForServiceCount,
                    'employees_in_bbox' => $employees->count(),
                    'employees_in_polygon' => 0,
                    'error_code' => 'OUT_OF_COVERAGE',
                    'error' => 'Address is outside service coverage area (polygon)',
                ],
            ];
        }

        $grouped = [];
        $noWorkCount = 0;

        foreach ($candidates as $emp) {
            $work = $emp->weeklyIntervals->where('type', 'work')->values();
            $breaks = $emp->weeklyIntervals->where('type', 'break')->values();

            if ($work->isEmpty()) {
                $noWorkCount++;
                continue;
            }

            $workIntervals = $work->map(fn($i) => $this->resolveInterval($i->start_time, $i->end_time))->all();
            $breakIntervals = $breaks->map(fn($i) => $this->resolveInterval($i->start_time, $i->end_time))->all();
            $blockIntervals = $emp->timeBlocks->map(fn($b) => $this->resolveInterval($b->start_time, $b->end_time))->all();

            $available = $this->subtractIntervals($workIntervals, $breakIntervals);
            $available = $this->subtractIntervals($available, $blockIntervals);

            if ($cutoffMinutes !== null) {
                $available = $this->subtractIntervals($available, [[0, $cutoffMinutes]]);
            }

            $bookingIntervals = $this->getBookingIntervals($emp->id, $dbDate, $nextDbDate, $excludeBookingId);
            $available = $this->subtractIntervals($available, $bookingIntervals);

            // ✅ FIX: نمرر workIntervals كـ grid anchors
            $slots = $this->generateSlots($available, $duration, $step, $mode, $workIntervals);

            foreach ($slots as $slot) {
                $rawStart = $slot['raw_start'];
                $rawEnd = $slot['raw_end'];

                if ($cutoffMinutes !== null && $rawStart < $cutoffMinutes) {
                    continue;
                }

                $slotBookingDate = $rawStart >= 1440 ? $nextDbDate : $dbDate;

                $startTime = $this->minutesToTime($rawStart);
                $endTime = $this->minutesToTime($rawEnd);
                $key = $startTime . '|' . $endTime;

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'booking_date' => $slotBookingDate,
                        'mid_night' => $rawStart >= 1440,
                        'raw_start' => $rawStart,
                        'employees' => [],
                    ];
                }

                $grouped[$key]['employees'][] = [
                    'employee_id' => (int) $emp->id,
                    'user_id' => (int) $emp->user_id,
                    'name' => (string) ($emp->user?->name ?? ''),
                ];
            }
        }

        $items = array_values($grouped);
        usort($items, fn($a, $b) => $a['raw_start'] <=> $b['raw_start']);

        $items = array_map(function ($item) {
            unset($item['raw_start']);
            return $item;
        }, $items);

        $meta = [
            'date' => $date,
            'day' => $weekday,
            'service_id' => $serviceId,
            'duration_minutes' => $duration,
            'step_minutes' => $step,
            'lat' => (string) $lat,
            'lng' => (string) $lng,
            'employees_considered' => $candidates->count(),
        ];

        if (empty($items)) {
            if ($noWorkCount === $candidates->count()) {
                $meta['error_code'] = 'NO_WORKING_HOURS';
                $meta['error'] = 'No employees have working hours on this day';
            } else {
                $meta['error_code'] = 'NO_SLOTS_AVAILABLE';
                $meta['error'] = 'No slots available after breaks/blocks/bookings';
            }
        }

        return [
            'items' => $items,
            'meta' => $meta,
        ];
    }

    public function getPartnerSlots(string $date, int $serviceId, float $lat, float $lng, ?int $stepMinutes = null, string $mode = 'blocks', ?int $excludeBookingId = null, ?int $partnerId = null): array
    {
        $tz = config('app.timezone', 'Asia/Riyadh');
        $day = Carbon::createFromFormat('d-m-Y', $date, $tz);
        $dbDate = $day->toDateString();
        $nextDbDate = $day->copy()->addDay()->toDateString();

        $service = Service::query()
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->first();

        if (!$service) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $dbDate,
                    'service_id' => $serviceId,
                    'error_code' => 'SERVICE_NOT_FOUND',
                    'error' => 'Service not found',
                ],
            ];
        }

        $duration = (int) $service->duration_minutes;
        $step = $stepMinutes ?? $duration;
        $weekday = $this->carbonToDayEnum($day);

        $now = Carbon::now($tz)->startOfMinute();

        if ($day->lt($now->copy()->startOfDay())) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $dbDate,
                    'service_id' => $serviceId,
                    'error_code' => 'DATE_IN_PAST',
                    'error' => 'Date is in the past',
                ],
            ];
        }

        $cutoffMinutes = null;
        if ($day->isSameDay($now)) {
            $nowMinutes = ($now->hour * 60) + $now->minute;
            $lead = (int) config('booking.min_lead_minutes', 0);
            $nowMinutes += $lead;
            $cutoffMinutes = (int) (ceil($nowMinutes / $step) * $step);
        }

        $baseQuery = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn($q) => $q->where('is_active', true)->where('user_type', 'biker'));

        if ($partnerId) {
            $baseQuery->whereHas('partnerAssignments', function ($q) use ($partnerId, $serviceId) {
                $q->where('partner_id', $partnerId)
                    ->where('service_id', $serviceId);
            });
        } else {
            $baseQuery->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId)
                    ->where('employee_services.is_active', 1);
            });
        }

        $employeesForServiceCount = (clone $baseQuery)->count();

        $employees = (clone $baseQuery)
            ->whereHas('workArea', function ($q) use ($lat, $lng) {
                $q->where('is_active', true)
                    ->where('min_lat', '<=', $lat)
                    ->where('max_lat', '>=', $lat)
                    ->where('min_lng', '<=', $lng)
                    ->where('max_lng', '>=', $lng);
            })
            ->with([
                'user:id,name',
                'workArea:id,employee_id,polygon,min_lat,max_lat,min_lng,max_lng',
                'weeklyIntervals' => function ($q) use ($weekday) {
                    $q->where('day', $weekday)->where('is_active', true);
                },
                'timeBlocks' => function ($q) use ($dbDate) {
                    $q->where('date', $dbDate)->where('is_active', true);
                },
            ])
            ->get();

        if ($employees->isEmpty()) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $dbDate,
                    'day' => $weekday,
                    'service_id' => $serviceId,
                    'lat' => (string) $lat,
                    'lng' => (string) $lng,
                    'employees_for_service' => $employeesForServiceCount,
                    'employees_in_bbox' => 0,
                    'employees_in_polygon' => 0,
                    'error_code' => 'OUT_OF_COVERAGE',
                    'error' => 'Address is outside service coverage area (bbox)',
                ],
            ];
        }

        $candidates = $employees->filter(function ($emp) use ($lat, $lng) {
            $poly = $emp->workArea?->polygon ?? [];
            return $this->pointInPolygon($lat, $lng, $poly);
        })->values();

        if ($candidates->isEmpty()) {
            return [
                'items' => [],
                'meta' => [
                    'date' => $dbDate,
                    'day' => $weekday,
                    'service_id' => $serviceId,
                    'lat' => (string) $lat,
                    'lng' => (string) $lng,
                    'employees_for_service' => $employeesForServiceCount,
                    'employees_in_bbox' => $employees->count(),
                    'employees_in_polygon' => 0,
                    'error_code' => 'OUT_OF_COVERAGE',
                    'error' => 'Address is outside service coverage area (polygon)',
                ],
            ];
        }

        $grouped = [];
        $noWorkCount = 0;

        foreach ($candidates as $emp) {
            $work = $emp->weeklyIntervals->where('type', 'work')->values();
            $breaks = $emp->weeklyIntervals->where('type', 'break')->values();

            if ($work->isEmpty()) {
                $noWorkCount++;
                continue;
            }

            $workIntervals = $work->map(fn($i) => $this->resolveInterval($i->start_time, $i->end_time))->all();
            $breakIntervals = $breaks->map(fn($i) => $this->resolveInterval($i->start_time, $i->end_time))->all();
            $blockIntervals = $emp->timeBlocks->map(fn($b) => $this->resolveInterval($b->start_time, $b->end_time))->all();

            $available = $this->subtractIntervals($workIntervals, $breakIntervals);
            $available = $this->subtractIntervals($available, $blockIntervals);

            if ($cutoffMinutes !== null) {
                $available = $this->subtractIntervals($available, [[0, $cutoffMinutes]]);
            }

            $bookingIntervals = $this->getBookingIntervals($emp->id, $dbDate, $nextDbDate, $excludeBookingId);
            $available = $this->subtractIntervals($available, $bookingIntervals);

            \Log::debug('[SlotService] Employee available intervals', [
                'employee_id' => $emp->id,
                'employee_name' => $emp->user?->name,
                'date' => $dbDate,
                'work_intervals' => $workIntervals,
                'break_intervals' => $breakIntervals,
                'block_intervals' => $blockIntervals,
                'booking_intervals' => $bookingIntervals,
                'final_available' => $available,
            ]);

            // ✅ FIX: نمرر workIntervals كـ grid anchors
            $slots = $this->generateSlots($available, $duration, $step, $mode, $workIntervals);

            foreach ($slots as $slot) {
                if ($cutoffMinutes !== null && $slot['raw_start'] < $cutoffMinutes) {
                    continue;
                }

                $startTime = $this->minutesToTime($slot['raw_start']);
                $key = $startTime;

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'start_time' => $startTime,
                        'booking_date' => $slot['raw_start'] >= 1440 ? $nextDbDate : $dbDate,
                        'mid_night' => $slot['raw_start'] >= 1440,
                        'raw_start' => $slot['raw_start'],
                    ];
                }
            }
        }

        $items = array_values($grouped);
        usort($items, fn($a, $b) => $a['raw_start'] <=> $b['raw_start']);

        $items = array_map(function ($item) {
            unset($item['raw_start']);
            return $item;
        }, $items);

        $meta = [
            'date' => $dbDate,
            'day' => $weekday,
            'service_id' => $serviceId,
            'duration_minutes' => $duration,
            'lat' => (string) $lat,
            'lng' => (string) $lng,
        ];

        if (empty($items)) {
            if ($noWorkCount === $candidates->count()) {
                $meta['error_code'] = 'NO_WORKING_HOURS';
                $meta['error'] = 'No employees have working hours on this day';
            } else {
                $meta['error_code'] = 'NO_SLOTS_AVAILABLE';
                $meta['error'] = 'No slots available after breaks/blocks/bookings';
            }
        }

        return [
            'items' => $items,
            'meta' => $meta,
        ];
    }

    public function getPartnerSlotsWithEmployees(
        string $date,
        int $serviceId,
        float $lat,
        float $lng,
        ?int $partnerId = null,
        ?int $excludeBookingId = null
    ): array {
        $tz = config('app.timezone', 'Asia/Riyadh');
        $day = Carbon::createFromFormat('d-m-Y', $date, $tz);
        $dbDate = $day->toDateString();
        $nextDbDate = $day->copy()->addDay()->toDateString();

        $service = Service::query()
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->first();

        if (!$service) {
            return [
                'slots' => [],
                'error_code' => 'SERVICE_NOT_FOUND',
            ];
        }

        $duration = (int) $service->duration_minutes;
        $step = $duration;
        $weekday = $this->carbonToDayEnum($day);
        $now = Carbon::now($tz)->startOfMinute();

        if ($day->lt($now->copy()->startOfDay())) {
            return [
                'slots' => [],
                'error_code' => 'DATE_IN_PAST',
            ];
        }

        $cutoffMinutes = null;
        if ($day->isSameDay($now)) {
            $nowMinutes = ($now->hour * 60) + $now->minute;
            $lead = (int) config('booking.min_lead_minutes', 0);
            $nowMinutes += $lead;
            $cutoffMinutes = (int) (ceil($nowMinutes / $step) * $step);
        }

        $baseQuery = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn($q) => $q->where('is_active', true)->where('user_type', 'biker'));

        if ($partnerId) {
            $baseQuery->whereHas('partnerAssignments', function ($q) use ($partnerId, $serviceId) {
                $q->where('partner_id', $partnerId)
                    ->where('service_id', $serviceId);
            });
        } else {
            $baseQuery->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId)
                    ->where('employee_services.is_active', 1);
            });
        }

        $employees = (clone $baseQuery)
            ->whereHas('workArea', function ($q) use ($lat, $lng) {
                $q->where('is_active', true)
                    ->where('min_lat', '<=', $lat)
                    ->where('max_lat', '>=', $lat)
                    ->where('min_lng', '<=', $lng)
                    ->where('max_lng', '>=', $lng);
            })
            ->with([
                'user:id,name',
                'workArea:id,employee_id,polygon,min_lat,max_lat,min_lng,max_lng',
                'weeklyIntervals' => function ($q) use ($weekday) {
                    $q->where('day', $weekday)->where('is_active', true);
                },
                'timeBlocks' => function ($q) use ($dbDate) {
                    $q->where('date', $dbDate)->where('is_active', true);
                },
            ])
            ->get();

        if ($employees->isEmpty()) {
            return [
                'slots' => [],
                'error_code' => 'OUT_OF_COVERAGE',
            ];
        }

        $candidates = $employees->filter(function ($emp) use ($lat, $lng) {
            $poly = $emp->workArea?->polygon ?? [];
            return $this->pointInPolygon($lat, $lng, $poly);
        })->values();

        if ($candidates->isEmpty()) {
            return [
                'slots' => [],
                'error_code' => 'OUT_OF_COVERAGE',
            ];
        }

        $slotsByTime = [];

        foreach ($candidates as $emp) {
            $work = $emp->weeklyIntervals->where('type', 'work')->values();
            $breaks = $emp->weeklyIntervals->where('type', 'break')->values();

            if ($work->isEmpty()) {
                continue;
            }

            $workIntervals = $work->map(fn($i) => $this->resolveInterval($i->start_time, $i->end_time))->all();
            $breakIntervals = $breaks->map(fn($i) => $this->resolveInterval($i->start_time, $i->end_time))->all();
            $blockIntervals = $emp->timeBlocks->map(fn($b) => $this->resolveInterval($b->start_time, $b->end_time))->all();

            $available = $this->subtractIntervals($workIntervals, $breakIntervals);
            $available = $this->subtractIntervals($available, $blockIntervals);

            if ($cutoffMinutes !== null) {
                $available = $this->subtractIntervals($available, [[0, $cutoffMinutes]]);
            }

            $bookingIntervals = $this->getBookingIntervals($emp->id, $dbDate, $nextDbDate, $excludeBookingId);
            $available = $this->subtractIntervals($available, $bookingIntervals);

            // ✅ FIX: بدل ما نبدأ من $startMin مباشرة، نوجد أول slot على grid الأصلي
            foreach ($available as [$startMin, $endMin]) {
                // نحسب أول نقطة على grid (من work interval المناسب) >= startMin
                $slotStart = $this->alignToGrid($startMin, $workIntervals, $duration);

                while ($slotStart + $duration <= $endMin) {
                    $timeKey = $this->minutesToTime($slotStart);

                    if (!isset($slotsByTime[$timeKey])) {
                        $slotsByTime[$timeKey] = [
                            'start_time' => $timeKey,
                            'booking_date' => $slotStart >= 1440 ? $nextDbDate : $dbDate,
                            'mid_night' => $slotStart >= 1440,
                            'raw_start' => $slotStart,
                            'employees' => [],
                        ];
                    }

                    $slotsByTime[$timeKey]['employees'][] = [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->user->name ?? null,
                    ];

                    $slotStart += $step;
                }
            }
        }

        $slots = array_values($slotsByTime);
        usort($slots, fn($a, $b) => $a['raw_start'] <=> $b['raw_start']);

        $slots = array_map(function ($slot) {
            unset($slot['raw_start']);
            return $slot;
        }, $slots);

        return [
            'slots' => $slots,
            'error_code' => empty($slots) ? 'NO_SLOTS_AVAILABLE' : null,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    private function carbonToDayEnum(Carbon $day): string
    {
        return match ($day->dayOfWeek) {
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        };
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));
        return $h * 60 + $m;
    }

    private function resolveInterval(string $startTime, string $endTime): array
    {
        $s = $this->timeToMinutes($startTime);
        $e = $this->timeToMinutes($endTime);

        if ($e <= $s) {
            $e += 1440;
        }

        return [$s, $e];
    }

    private function minutesToTime(int $minutes): string
    {
        $wrapped = (($minutes % 1440) + 1440) % 1440;
        $h = intdiv($wrapped, 60);
        $m = $wrapped % 60;
        return str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    private function getBookingIntervals(int $employeeId, string $dbDate, string $nextDbDate, ?int $excludeBookingId = null): array
    {
        $todayBookings = Booking::query()
            ->where('employee_id', $employeeId)
            ->where('booking_date', $dbDate)
            ->whereNotIn('status', ['cancelled'])
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', (int) $excludeBookingId))
            ->get(['start_time', 'end_time'])
            ->map(fn($b) => $this->resolveInterval($b->start_time, $b->end_time))
            ->all();

        $tomorrowBookings = Booking::query()
            ->where('employee_id', $employeeId)
            ->where('booking_date', $nextDbDate)
            ->whereNotIn('status', ['cancelled'])
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', (int) $excludeBookingId))
            ->get(['start_time', 'end_time'])
            ->map(function ($b) {
                $s = $this->timeToMinutes($b->start_time) + 1440;
                $e = $this->timeToMinutes($b->end_time) + 1440;
                if ($e <= $s) {
                    $e += 1440;
                }
                return [$s, $e];
            })
            ->all();

        return array_merge($todayBookings, $tomorrowBookings);
    }

    /**
     * ✅ FIXED: توليد السلوتات مع دعم grid anchors
     *
     * @param array $available    الفترات المتاحة بعد كل الطرحات
     * @param int $durationMinutes مدة الخدمة
     * @param int $stepMinutes    الخطوة بين السلوتات
     * @param string $mode        'blocks' أو 'rolling'
     * @param array $gridAnchors  ✅ جديد: work intervals الأصلية لحساب بداية الـ grid
     *
     * في blocks mode: بدل ما نبدأ السلوت من بداية الـ interval المتاح ($s)،
     * نحسب أول سلوت على grid الأصلي (من بداية الدوام) >= $s.
     * هذا يضمن أن البلوكات والكسرات لا تُغير توزيع السلوتات.
     */
    private function generateSlots(array $available, int $durationMinutes, int $stepMinutes, string $mode = 'rolling', array $gridAnchors = []): array
    {
        $slots = [];

        foreach ($available as [$s, $e]) {

            if ($mode === 'blocks') {
                // ✅ FIX: أوجد أول نقطة على grid >= $s
                $t = $this->alignToGrid($s, $gridAnchors, $durationMinutes);

                while ($t + $durationMinutes <= $e) {
                    $slots[] = [
                        'start_time' => $this->minutesToTime($t),
                        'end_time' => $this->minutesToTime($t + $durationMinutes),
                        'raw_start' => $t,
                        'raw_end' => $t + $durationMinutes,
                    ];
                    $t += $durationMinutes;
                }
                continue;
            }

            // rolling (لم يتغير)
            $t = $this->ceilToStep($s, $stepMinutes);
            while ($t + $durationMinutes <= $e) {
                $slots[] = [
                    'start_time' => $this->minutesToTime($t),
                    'end_time' => $this->minutesToTime($t + $durationMinutes),
                    'raw_start' => $t,
                    'raw_end' => $t + $durationMinutes,
                ];
                $t += $stepMinutes;
            }
        }

        return $slots;
    }

    /**
     * ✅ دالة جديدة: إيجاد أول نقطة على grid الأصلي >= $minutes
     *
     * المنطق:
     *   - نبحث عن الـ work interval اللي يحتوي على $minutes (أو أقرب واحد قبله)
     *   - نحسب grid من بدايته: anchor, anchor+step, anchor+2*step, ...
     *   - نرجع أول قيمة >= $minutes
     *
     * @param int $minutes     الدقيقة اللي نريد أول slot >= منها
     * @param array $anchors   work intervals [[start, end], ...]
     * @param int $step        خطوة الـ grid (duration في blocks mode)
     * @return int             أول نقطة على الـ grid >= $minutes
     */
    private function alignToGrid(int $minutes, array $anchors, int $step): int
    {
        // لو ما في anchors، ارجع $minutes كما هو (fallback)
        if (empty($anchors) || $step <= 0) {
            return $minutes;
        }

        $best = null;

        foreach ($anchors as [$anchorStart, $anchorEnd]) {
            // نستخدم هذا الـ anchor لو:
            // 1. يبدأ قبل أو عند $minutes (الـ interval يحتوي أو يسبق النقطة)
            // 2. أو هو أقرب anchor مستقبلي (حالة ما في interval يحتوي النقطة)
            if ($anchorStart <= $minutes) {
                // حساب أول slot على grid من هذا الـ anchor >= $minutes
                $n = (int) ceil(($minutes - $anchorStart) / $step);
                $candidate = $anchorStart + $n * $step;

                // خذ الأصغر (أقرب slot)
                if ($best === null || $candidate < $best) {
                    $best = $candidate;
                }
            }
        }

        // لو ما وجدنا anchor مناسب (مثلاً $minutes قبل كل الـ anchors)، ارجع $minutes
        return $best ?? $minutes;
    }

    private function subtractIntervals(array $base, array $subtract): array
    {
        $base = $this->normalizeIntervals($base);
        $subtract = $this->normalizeIntervals($subtract);

        $result = $base;

        foreach ($subtract as [$bs, $be]) {
            $new = [];
            foreach ($result as [$s, $e]) {
                if ($be <= $s || $bs >= $e) {
                    $new[] = [$s, $e];
                    continue;
                }
                if ($bs > $s) {
                    $new[] = [$s, $bs];
                }
                if ($be < $e) {
                    $new[] = [$be, $e];
                }
            }
            $result = $this->normalizeIntervals($new);
        }

        return $result;
    }

    private function normalizeIntervals(array $intervals): array
    {
        $clean = [];
        foreach ($intervals as $it) {
            if (!is_array($it) || count($it) < 2)
                continue;
            $s = (int) $it[0];
            $e = (int) $it[1];
            if ($e <= $s)
                continue;
            $clean[] = [$s, $e];
        }

        usort($clean, fn($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($clean as [$s, $e]) {
            if (empty($merged)) {
                $merged[] = [$s, $e];
                continue;
            }
            $lastIndex = count($merged) - 1;
            [$ls, $le] = $merged[$lastIndex];

            if ($s <= $le) {
                $merged[$lastIndex] = [$ls, max($le, $e)];
            } else {
                $merged[] = [$s, $e];
            }
        }

        return $merged;
    }

    private function ceilToStep(int $minutes, int $step): int
    {
        if ($step <= 1)
            return $minutes;
        $r = $minutes % $step;
        return $r === 0 ? $minutes : ($minutes + ($step - $r));
    }

    /**
     * Ray-casting point in polygon
     */
    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (count($polygon) < 3)
            return false;

        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float) ($polygon[$i]['lat'] ?? 0);
            $yi = (float) ($polygon[$i]['lng'] ?? 0);
            $xj = (float) ($polygon[$j]['lat'] ?? 0);
            $yj = (float) ($polygon[$j]['lng'] ?? 0);

            $intersect = (($yi > $lng) !== ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersect)
                $inside = !$inside;
        }

        return $inside;
    }
}