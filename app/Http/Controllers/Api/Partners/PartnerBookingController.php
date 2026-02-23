<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Partner;
use App\Models\Service;
use App\Services\PartnerBookingService;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerBookingController extends Controller
{
    public function __construct(
        protected PartnerBookingService $partnerBookingService,
        protected SlotService $slotService
    ) {
    }

    /**
     * GET /api/partners/v1/services
     * جلب جميع الخدمات المخصصة للشريك
     */
    public function getServices(Request $request)
    {
        $partner = $request->input('partner');

        // ✅ جلب الخدمات الفريدة المخصصة للشريك
        $services = Service::query()
            ->whereHas('partnerAssignments', function ($q) use ($partner) {
                $q->where('partner_id', $partner->id);
            })
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->distinct()
            ->orderBy('id')
            ->get(['id', 'name', 'duration_minutes']);

        $data = $services->map(function ($service) {
            // ✅ استخراج الاسم العربي
            $nameAr = is_array($service->name)
                ? ($service->name['ar'] ?? $service->name['en'] ?? '')
                : $service->name;

            return [
                'id' => $service->id,
                'name' => $nameAr,
                'duration_minutes' => $service->duration_minutes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/partners/v1/slots
     * الأوقات المتاحة
     */
    public function getSlots(Request $request)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d|after_or_equal:today', // ✅ نفس صيغة الحجز + منع الماضي
            'service_id' => 'required|integer|exists:services,id',
            'lat' => 'required|numeric|between:-90,90', // ✅ تغيير الاسم
            'lng' => 'required|numeric|between:-180,180', // ✅ تغيير الاسم
            'step_minutes' => 'nullable|integer|min:5|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // التحقق من أن الشريك مخصص له هذه الخدمة
        $hasService = $partner->serviceEmployeeAssignments()
            ->where('service_id', $data['service_id'])
            ->exists();

        if (!$hasService) {
            return response()->json([
                'success' => false,
                'error' => 'Partner is not authorized for this service',
                'error_code' => 'SERVICE_NOT_AUTHORIZED',
            ], 403);
        }

        // ✅ تحويل التاريخ من Y-m-d إلى d-m-Y للـ SlotService
        $dateFormatted = \Carbon\Carbon::parse($data['date'])->format('d-m-Y');

        $slots = $this->slotService->getPartnerSlots(
            $dateFormatted, // ✅ التاريخ بصيغة d-m-Y
            (int) $data['service_id'],
            (float) $data['lat'],
            (float) $data['lng'],
            $data['step_minutes'] ?? null,
            'blocks',
            null,
            $partner->id
        );

        $times = collect($slots['items'])->pluck('start_time')->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $times,
            'meta' => $slots['meta'],
        ]);
    }

    /**
     * POST /api/partners/v1/bookings
     * إنشاء حجز
     */
    public function createBooking(Request $request)
    {
        $partner = $request->input('partner');

        // ✅ 1. تسجيل البيانات القادمة
        \Log::info('[PartnerBooking] Incoming request', [
            'partner_id' => $partner?->id,
            'partner_name' => $partner?->name,
            'ip' => $request->ip(),
            'payload' => $request->except(['partner']), // كل البيانات ما عدا الـ partner object
            'timestamp' => now()->toDateTimeString(),
            'correlation_id' => $request->header('X-Correlation-Id'),
        ]);


        $validator = Validator::make($request->all(), [
            'external_booking_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bookings', 'external_id')->where(function ($query) {
                    return $query->where('status', '!=', 'cancelled')
                        ->whereNull('deleted_at'); // تجاهل المحذوفات soft delete
                }),
            ],
            'service_id' => 'required|integer|exists:services,id',
            'start_date_time' => 'required|date_format:Y-m-d H:i:s|after:now', // ✅ منع التاريخ القديم

            // Customer
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => ['required', 'regex:/^(05\d{8}|9665\d{8})$/'],
            'customer_email' => 'nullable|email',

            // Location
            'location_name' => 'required|string|max:500',
            'location_latitude' => 'required|numeric|between:-90,90',
            'location_longitude' => 'required|numeric|between:-180,180',

            // Car
            'plate_number' => 'required|string|max:50',
            'car_color' => 'nullable|string|max:50',
            'car_brand' => 'nullable|string|max:100',
            'car_model' => 'nullable|string|max:100',

            // Optional
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ✅ تحويل البيانات للـ format القديم
        $transformed = $this->transformCreateRequest($validator->validated());

        $result = $this->partnerBookingService->createBooking($partner, $transformed);

        if (!$result['success']) {

            // ✅ 3. رُفض من الـ Service
            \Log::warning('[PartnerBooking] Booking rejected by service', [
                'partner_id' => $partner?->id,
                'external_booking_id' => $request->input('external_booking_id'),
                'error_code' => $result['error_code'] ?? 'UNKNOWN',
                'error' => $result['error'] ?? null,
            ]);

            return response()->json($result, 422);
        }

        // ✅ 4. نجح الحجز
        \Log::info('[PartnerBooking] Booking created successfully', [
            'partner_id' => $partner?->id,
            'booking_id' => $result['booking']->id,
            'external_booking_id' => $request->input('external_booking_id'),
            'booking_date' => $result['booking']->booking_date,
            'start_time' => $result['booking']->start_time,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($result['booking']),
            'message' => 'Booking created successfully',
        ], 201);
    }

    /**
     * تحويل Request المبسط إلى Format القديم
     */
    protected function transformCreateRequest(array $data): array
    {
        // ✅ فصل التاريخ والوقت
        $dateTime = \Carbon\Carbon::parse($data['start_date_time']);

        return [
            'external_id' => $data['external_booking_id'],
            'service_id' => $data['service_id'],
            'date' => $dateTime->format('d-m-Y'),
            'start_time' => $dateTime->format('H:i'),
            // 'employee_id' => $data['employee_id'] ?? null,

            'customer' => [
                'name' => $data['customer_name'],
                'mobile' => $data['customer_mobile'],
                'email' => $data['customer_email'] ?? null,
            ],

            'address' => [
                'address' => $data['location_name'],
                'lat' => $data['location_latitude'],
                'lng' => $data['location_longitude'],
            ],

            'car' => [
                'plate_number' => $data['plate_number'],
                'color' => $data['car_color'] ?? null,
                'model' => $this->combineBrandModel($data['car_brand'] ?? null, $data['car_model'] ?? null),
            ],

            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * دمج Brand و Model
     */
    protected function combineBrandModel(?string $brand, ?string $model): ?string
    {
        if ($brand && $model) {
            return trim($brand . ' ' . $model);
        }

        return $model ?? $brand ?? null;
    }

    /**
     * PUT /api/partners/v1/bookings/{external_booking_id}/reschedule
     * تعديل وقت الحجز
     */
    public function rescheduleBooking(Request $request, string $externalBookingId)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'start_date_time' => 'required|date_format:Y-m-d H:i:s|after:now',

            // ✅ تفاصيل الموقع (اختيارية - لو تغير الموقع)
            'location_name' => 'nullable|string|max:500',
            'location_latitude' => 'nullable|numeric|between:-90,90',
            'location_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dateTime = \Carbon\Carbon::parse($validator->validated()['start_date_time']);

        $transformed = [
            'date' => $dateTime->format('d-m-Y'),
            'start_time' => $dateTime->format('H:i'),

            // ✅ الموقع الجديد إذا تم إرساله
            'location' => null,
        ];

        // إذا تم إرسال تفاصيل موقع جديدة
        if ($request->filled('location_latitude') && $request->filled('location_longitude')) {
            $transformed['location'] = [
                'address' => $request->input('location_name'),
                'lat' => $request->input('location_latitude'),
                'lng' => $request->input('location_longitude'),
            ];
        }

        $result = $this->partnerBookingService->rescheduleBooking($partner, $externalBookingId, $transformed);

        if (!$result['success']) {
            return response()->json($result, $result['error_code'] === 'BOOKING_NOT_FOUND' ? 404 : 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($result['booking']),
            'message' => 'Booking rescheduled successfully',
        ]);
    }

    /**
     * POST /api/partners/v1/bookings/{external_booking_id}/cancel
     * إلغاء الحجز
     */
    public function cancelBooking(Request $request, string $externalBookingId)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->partnerBookingService->cancelBooking(
            $partner,
            $externalBookingId,
            $request->input('cancel_reason')
        );

        if (!$result['success']) {
            return response()->json($result, $result['error_code'] === 'BOOKING_NOT_FOUND' ? 404 : 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($result['booking']),
            'message' => 'Booking cancelled successfully',
        ]);
    }

    /**
     * GET /api/partners/v1/bookings
     * قائمة الحجوزات
     */
    public function listBookings(Request $request)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,confirmed,moving,arrived,completed,cancelled',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Booking::query()
            ->where('partner_id', $partner->id)
            ->with(['service', 'employee.user', 'car', 'address']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('booking_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('booking_date', '<=', $request->input('to_date'));
        }

        $perPage = $request->input('per_page', 20);
        $bookings = $query->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bookings->map(fn($b) => $this->transformBooking($b)),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * GET /api/partners/v1/bookings/{external_booking_id}
     * جلب حجز معين
     */
    public function getBooking(Request $request, string $externalBookingId)
    {
        $partner = $request->input('partner');

        // ✅ أولاً: حاول تجيب الحجز النشط (غير ملغي)
        $booking = Booking::query()
            ->where('partner_id', $partner->id)
            ->where('external_id', $externalBookingId)
            ->where('status', '!=', 'cancelled')
            ->with(['service', 'employee.user', 'car.make', 'car.model', 'address', 'user'])
            ->orderBy('id', 'desc')
            ->first();

        // ✅ إذا ما لقيت، جيب الملغي
        if (!$booking) {
            $booking = Booking::query()
                ->where('partner_id', $partner->id)
                ->where('external_id', $externalBookingId)
                ->where('status', 'cancelled')
                ->with(['service', 'employee.user', 'car.make', 'car.model', 'address', 'user'])
                ->orderBy('id', 'desc')
                ->first();
        }

        if (!$booking) {
            return response()->json([
                'success' => false,
                'error' => 'Booking not found',
                'error_code' => 'BOOKING_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($booking),
        ]);
    }

    /**
     * تحويل Booking إلى JSON Response
     */
    protected function transformBooking(Booking $booking): array
    {
        // دمج Brand + Model
        $carBrand = $booking->car->make->name ?? null;
        $carModel = $booking->car->model->name ?? null;

        // استخراج النص حسب اللغة
        $locale = app()->getLocale();
        $brandName = is_array($carBrand) ? ($carBrand[$locale] ?? $carBrand['en'] ?? '') : $carBrand;
        $modelName = is_array($carModel) ? ($carModel[$locale] ?? $carModel['en'] ?? '') : $carModel;

        return [
            'id' => $booking->id,
            'external_booking_id' => $booking->external_id,
            'service_id' => $booking->service_id,
            'status' => $booking->status,

            // ✅ دمج التاريخ والوقت
            'start_date_time' => $booking->booking_date->format('Y-m-d') . ' ' . substr($booking->start_time, 0, 5) . ':00',
            'duration_minutes' => $booking->duration_minutes,

            // Customer
            'customer_name' => $booking->user->name,
            'customer_mobile' => $booking->user->mobile,
            'customer_email' => $booking->user->email,

            // Location
            'location_name' => $booking->address->address_line,
            'location_latitude' => (float) $booking->address->lat,
            'location_longitude' => (float) $booking->address->lng,

            // Car
            'plate_number' => $booking->car->plate_number . ' ' . $booking->car->plate_letters,
            'car_color' => $booking->car->color,
            'car_brand' => $brandName,
            'car_model' => $modelName,

            // Employee
            'employee_id' => $booking->employee_id,
            'employee_name' => $booking->employee?->user?->name,

            // Metadata
            'notes' => $booking->meta['customer_notes'] ?? null,
            'created_at' => $booking->created_at->toDateTimeString(),
            'updated_at' => $booking->updated_at->toDateTimeString(),
        ];
    }

    public function updateStatus(Request $request, string $externalId)
    {
        $partner = $request->input('partner');

        \Log::info('[PartnerBooking] Status update received', [
            'partner_id' => $partner->id,
            'external_id' => $externalId,
            'status' => $request->input('status'),
            'correlation_id' => $request->header('X-Correlation-Id'), // ← أضف هاد
        ]);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:InProgress,Completed,Cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $booking = Booking::where('partner_id', $partner->id)
            ->where('external_id', $externalId)
            ->first();

        if (!$booking) {
            return response()->json(['success' => false, 'error' => 'Booking not found'], 404);
        }

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot update status of a completed or cancelled booking',
                'error_code' => 'BOOKING_NOT_UPDATABLE',
                'current_status' => $booking->status,
            ], 422);
        }

        $statusMap = [
            'InProgress' => 'moving',
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
        ];

        $booking->update(['status' => $statusMap[$request->status]]);

        \Log::info('[PartnerBooking] Status updated successfully', [
            'correlation_id' => $request->header('X-Correlation-Id'), // ← وهون
            'booking_id' => $booking->id,
            'new_status' => $statusMap[$request->status],
        ]);

        // أطلق الـ webhook لو عندك events
        // event(new BookingStatusUpdated($booking));

        return response()->json(['success' => true], 200);
    }
}