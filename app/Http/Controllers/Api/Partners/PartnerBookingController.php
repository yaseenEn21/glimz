<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Partner;
use App\Services\PartnerBookingService;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerBookingController extends Controller
{
    public function __construct(
        protected PartnerBookingService $partnerBookingService,
        protected SlotService $slotService
    ) {
    }

    /**
     * GET /api/partners/v1/slots
     * الأوقات المتاحة
     */
    public function getSlots(Request $request)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:d-m-Y',
            'service_id' => 'required|integer|exists:services,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
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

        $slots = $this->slotService->getPartnerSlots(
            $data['date'],
            (int) $data['service_id'],
            (float) $data['lat'],
            (float) $data['lng'],
            $data['step_minutes'] ?? null,
            'blocks',
            null,
            $partner->id // ✅ فلتر الشريك
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

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|string|max:255|unique:bookings,external_id',
            'service_id' => 'required|integer|exists:services,id',
            'date' => 'required|date_format:d-m-Y',
            'start_time' => 'required|date_format:H:i',
            'employee_id' => 'nullable|integer|exists:employees,id',

            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.mobile' => 'required|string',
            'customer.email' => 'nullable|email',

            'address' => 'required|array',
            'address.address' => 'required|string',
            'address.lat' => 'required|numeric',
            'address.lng' => 'required|numeric',

            'car' => 'required|array',
            'car.plate_number' => 'required|string|max:50',
            'car.color' => 'nullable|string|max:50',
            'car.model' => 'nullable|string|max:100',

            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->partnerBookingService->createBooking($partner, $validator->validated());

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($result['booking']),
            'message' => 'Booking created successfully',
        ], 201);
    }

    /**
     * PUT /api/partners/v1/bookings/{external_id}/reschedule
     * تعديل وقت الحجز
     */
    public function rescheduleBooking(Request $request, string $externalId)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:d-m-Y',
            'start_time' => 'required|date_format:H:i',
            'employee_id' => 'nullable|integer|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->partnerBookingService->rescheduleBooking($partner, $externalId, $validator->validated());

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
     * POST /api/partners/v1/bookings/{external_id}/cancel
     * إلغاء الحجز
     */
    public function cancelBooking(Request $request, string $externalId)
    {
        $partner = $request->input('partner');

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
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
            $externalId,
            $request->input('reason')
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
     * GET /api/partners/v1/bookings/{external_id}
     * جلب حجز معين
     */
    public function getBooking(Request $request, string $externalId)
    {
        $partner = $request->input('partner');

        $booking = Booking::query()
            ->where('partner_id', $partner->id)
            ->where('external_id', $externalId)
            ->with(['service', 'employee.user', 'car', 'address'])
            ->first();

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
        return [
            'id' => $booking->id,
            'external_id' => $booking->external_id,
            'status' => $booking->status,
            'booking_date' => $booking->booking_date->format('Y-m-d'),
            'start_time' => substr($booking->start_time, 0, 5),
            'end_time' => substr($booking->end_time, 0, 5),
            'duration_minutes' => $booking->duration_minutes,

            'service' => [
                'id' => $booking->service->id,
                'name' => $booking->service->name,
            ],

            'employee' => $booking->employee ? [
                'id' => $booking->employee->id,
                'name' => $booking->employee->user->name ?? null,
            ] : null,

            'customer' => [
                'name' => $booking->user->name,
                'mobile' => $booking->user->mobile,
                'email' => $booking->user->email,
            ],

            'car' => [
                'plate_number' => $booking->car->plate_number,
                'color' => $booking->car->color,
                'model' => $booking->car->model,
            ],

            'address' => [
                'address' => $booking->address->address,
                'lat' => (float) $booking->address->lat,
                'lng' => (float) $booking->address->lng,
            ],

            'created_at' => $booking->created_at->toDateTimeString(),
            'updated_at' => $booking->updated_at->toDateTimeString(),
        ];
    }
}