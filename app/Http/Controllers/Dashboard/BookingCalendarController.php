<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Employee;
use App\Services\SlotService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingCalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:bookings.view')->only(['index','resources','events']);
        $this->middleware('can:bookings.edit')->only(['move']);
    }

    public function index()
    {
        view()->share([
            'title' => __('bookings.calendar.title'),
            'page_title' => __('bookings.calendar.title'),
        ]);

        return view('dashboard.bookings.calendar');
    }

    public function resources()
    {
        $rows = Employee::query()
            ->where('is_active', true)
            ->whereHas('user', fn($q) => $q->where('is_active', true)->where('user_type', 'biker'))
            ->with(['user:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn($e) => [
                'id' => (string) $e->id,
                'title' => (string) ($e->user?->name ?? ('#'.$e->id)),
            ])
            ->values();

        return response()->json($rows);
    }

    public function events(Request $request)
    {
        // FullCalendar يرسل start/end ISO
        $start = Carbon::parse($request->query('start'));
        $end   = Carbon::parse($request->query('end'));

        $employeeId = $request->query('employee_id');
        
        $q = Booking::query()
            ->with(['user:id,name', 'service:id,name', 'car:id,plate_number', 'address:id,address_line'])
            ->whereDate('booking_date', '>=', $start->toDateString())
            ->whereDate('booking_date', '<',  $end->toDateString())
            ->whereNotNull('employee_id');

        if ($employeeId !== null && $employeeId !== 'null') {
            $q->where('employee_id', (int)$employeeId);
        }

        $bookings = $q->orderBy('booking_date')->orderBy('start_time')->get();

        $events = $bookings->map(function (Booking $b) {
            $titleParts = [
                $b->user?->name,
                $b->service ? (is_array($b->service->name) ? ($b->service->name['ar'] ?? $b->service->name['en'] ?? '') : $b->service->name) : null,
            ];
            $title = trim(collect($titleParts)->filter()->implode(' - '));

            $start = $b->booking_date->format('Y-m-d') . 'T' . substr((string)$b->start_time, 0, 5) . ':00';
            $end   = $b->booking_date->format('Y-m-d') . 'T' . substr((string)$b->end_time, 0, 5) . ':00';

            return [
                'id' => (string) $b->id,
                'resourceId' => (string) $b->employee_id,
                'title' => $title !== '' ? $title : ('#'.$b->id),
                'start' => $start,
                'end' => $end,
                'url' => route('dashboard.bookings.show', $b->id),

                'extendedProps' => [
                    'status' => (string) $b->status,
                    'total' => (float) ($b->total_snapshot ?? 0),
                ],
            ];
        })->values();

        return response()->json($events);
    }

    /**
     * Drag & Drop move (اختياري)
     * يغيّر: booking_date/start_time/end_time/employee_id مع تحقق Slot
     */
    public function move(Request $request, Booking $booking, SlotService $slotService)
    {
        // ممنوع تحريك المكتمل/الملغي
        if (in_array($booking->status, ['completed','cancelled'])) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.calendar.cannot_move_status'),
            ], 422);
        }

        $data = $request->validate([
            'booking_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $booking->loadMissing(['service:id,duration_minutes', 'address:id,lat,lng']);

        $serviceId = (int) $booking->service_id;
        $duration  = (int) ($booking->service?->duration_minutes ?? 0);

        if ($serviceId <= 0 || $duration <= 0) {
            return response()->json(['ok'=>false,'message'=>'Invalid booking service'], 422);
        }

        $dbDate = $data['booking_date'];
        $apiDate = Carbon::createFromFormat('Y-m-d', $dbDate)->format('d-m-Y');

        $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate.' '.$data['start_time'])
            ->addMinutes($duration)
            ->format('H:i');

        // ✅ تحقق Slot + الموظف داخل slot
        $slots = $slotService->getSlots(
            $apiDate,
            $serviceId,
            (float)$booking->address?->lat,
            (float)$booking->address?->lng
        );

        $slot = collect($slots['items'] ?? [])
            ->first(fn($s) => ($s['start_time'] ?? null) === $data['start_time']);

        if (!$slot) {
            throw ValidationException::withMessages([
                'start_time' => [__('bookings.time_not_available')],
            ]);
        }

        $employees = $slot['employees'] ?? [];
        $found = collect($employees)->first(fn($e) => (int)$e['employee_id'] === (int)$data['employee_id']);
        if (!$found) {
            throw ValidationException::withMessages([
                'employee_id' => [__('bookings.employee_not_available')],
            ]);
        }

        DB::transaction(function () use ($booking, $data, $endTime) {
            $booking->update([
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $endTime,
                'employee_id' => (int)$data['employee_id'],
                'updated_by' => auth()->id(),
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => __('bookings.calendar.moved_successfully'),
        ]);
    }
}
