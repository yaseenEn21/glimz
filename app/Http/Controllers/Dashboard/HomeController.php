<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\AdmissionApplication;
use Carbon\Carbon;
use App\Models\ChildReport;
use App\Models\ChildReportAnswer;
use App\Models\ReportQuestion;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Zone;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct()
    {
        //  
    }

    public function index()
    {

        view()->share([
            'title' => __('dashboard.title'),
            // 'page_title' => __('dashboard.home.title'),
        ]);

        $from = Carbon::now()->startOfMonth()->toDateString();
        $to = Carbon::now()->endOfMonth()->toDateString();

        return view('dashboard.index', compact('from', 'to'));
    }

    public function kpi(Request $request)
    {
        [$from, $to] = $this->parseRange($request);

        // ====== base bookings in range (flatebiker) ======
        $bookingsBase = Booking::query()
            ->whereDate('booking_date', '>=', $from)
            ->whereDate('booking_date', '<=', $to)
            ->whereNotNull('employee_id')
            ->whereHas('employee.user', fn($q) => $q->where('user_type', 'biker'));

        $totalBookings = (clone $bookingsBase)->count();

        $byStatus = (clone $bookingsBase)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = (int) ($byStatus['completed'] ?? 0);
        $cancelled = (int) ($byStatus['cancelled'] ?? 0);
        $activeCount = $totalBookings - $cancelled;

        $packageBookings = (clone $bookingsBase)->whereNotNull('package_subscription_id')->count();

        // ====== Booking gross (اختياري تبقيه) ======
        $bookingGross = (float) (clone $bookingsBase)
            ->where('status', '!=', 'cancelled')
            ->sum(DB::raw('COALESCE(total_snapshot, 0)'));

        $avgTicket = $activeCount > 0 ? round($bookingGross / $activeCount, 2) : 0.0;

        // =====================================================
        // ✅ Finance (SYSTEM-WIDE) — مش مربوط بالحجوزات
        // =====================================================

        // 1) إجمالي فواتير النظام (ضمن المدة)
        // عدّل statuses حسب نظامك (مثلاً: draft/void/cancelled)
        $systemInvoiced = (float) Invoice::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereNotIn('status', ['cancelled'])
            ->sum(DB::raw('COALESCE(total,0)'));

        // 2) المدفوع على مستوى النظام (ضمن المدة)
        // لو عندك refunds ب status مختلف عدّلها.
        $systemPaid = (float) Payment::query()
            ->where('status', 'paid')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum(DB::raw('COALESCE(amount,0)'));

        // 3) غير مدفوع (Outstanding) على مستوى النظام (ضمن المدة)
        $systemUnpaid = (float) Invoice::query()
            ->where('status', 'unpaid')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->sum(DB::raw('COALESCE(total,0)'));

        // 4) Paid daily trend (system-wide)
        $days = $this->daysLabels($from, $to);

        $bookingsDaily = (clone $bookingsBase)
            ->select(DB::raw('DATE(booking_date) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $systemPaidDailyMap = Payment::query()
            ->where('status', 'paid')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(COALESCE(amount,0)) as s'))
            ->groupBy('d')
            ->pluck('s', 'd')
            ->toArray();

        $seriesBookings = [];
        $seriesSystemPaid = [];
        foreach ($days as $d) {
            $seriesBookings[] = (int) ($bookingsDaily[$d] ?? 0);
            $seriesSystemPaid[] = (float) ($systemPaidDailyMap[$d] ?? 0);
        }

        // ====== top bikers/services/zones (كما عندك) ======
        $topBikers = (clone $bookingsBase)
            ->join('employees', 'employees.id', '=', 'bookings.employee_id')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->selectRaw("
            employees.id as employee_id,
            users.name as name,
            COUNT(bookings.id) as total,
            SUM(CASE WHEN bookings.status='completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN bookings.status='cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(COALESCE(bookings.total_snapshot,0)) as gross
        ")
            ->groupBy('employees.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $nameExpr = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(services.name, '$.ar')),
                 JSON_UNQUOTE(JSON_EXTRACT(services.name, '$.en')))";

        $topServices = (clone $bookingsBase)
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->selectRaw("
            services.id as service_id,
            {$nameExpr} as name,
            COUNT(bookings.id) as total,
            SUM(COALESCE(bookings.total_snapshot,0)) as gross
        ")
            ->groupBy('services.id', DB::raw($nameExpr))
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $zoneNameExpr = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(zones.name, '$.ar')),
                     JSON_UNQUOTE(JSON_EXTRACT(zones.name, '$.en')))";

        $topZones = (clone $bookingsBase)
            ->join('zones', 'zones.id', '=', 'bookings.zone_id')
            ->selectRaw("
            zones.id as zone_id,
            {$zoneNameExpr} as name,
            COUNT(bookings.id) as total
        ")
            ->groupBy('zones.id', DB::raw($zoneNameExpr))
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $statuses = ['pending', 'confirmed', 'moving', 'arrived', 'completed', 'cancelled'];

        $statusChart = collect($statuses)
            ->map(fn($s) => [
                'key' => $s,
                'label' => __('bookings.status.' . $s), // ✅ ترجمة حسب لغة الموقع
                'total' => (int) ($byStatus[$s] ?? 0),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],

                // ✅ حجوزات (flatebiker)
                'cards' => [
                    'total_bookings' => $totalBookings,
                    'active_bookings' => $activeCount,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'cancel_rate' => $totalBookings > 0 ? round(($cancelled * 100) / $totalBookings, 1) : 0.0,
                    'package_bookings' => $packageBookings,

                    // اختياري تبقيها كـ booking finance
                    'booking_gross' => round($bookingGross, 2),
                    'avg_ticket' => round($avgTicket, 2),
                    'currency' => 'SAR',
                ],

                // ✅ مالية (SYSTEM-WIDE)
                'finance' => [
                    'system_invoiced' => round($systemInvoiced, 2),
                    'system_paid' => round($systemPaid, 2),
                    'system_unpaid' => round($systemUnpaid, 2),
                    'currency' => 'SAR',
                ],

                'charts' => [
                    'status' => $statusChart,
                    'trend' => [
                        'labels' => $days,
                        'series' => [
                            'bookings' => $seriesBookings,
                            'paid' => $seriesSystemPaid, // ✅ صار System Paid
                        ],
                    ],
                ],

                'tops' => [
                    'bikers' => $topBikers,
                    'services' => $topServices,
                    'zones' => $topZones,
                ],
            ],
        ]);
    }

    /**
     * Get dashboard statistics for a date range
     */
    public function stats(Request $request)
    {
        [$from, $to] = $this->parseRange($request);

        // Calculate previous period (same number of days before)
        $daysDiff = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $prevFrom = Carbon::parse($from)->subDays($daysDiff)->toDateString();
        $prevTo = Carbon::parse($from)->subDay()->toDateString();

        // 1. Active Bookings (غير مكتملة وغير ملغية)
        $activeBookings = Booking::query()
            ->whereDate('booking_date', '>=', $from)
            ->whereDate('booking_date', '<=', $to)
            ->whereIn('status', ['pending', 'confirmed', 'moving', 'arrived'])
            ->count();

        // Total bookings for percentage calculation
        $totalBookings = Booking::query()
            ->whereDate('booking_date', '>=', $from)
            ->whereDate('booking_date', '<=', $to)
            ->count();

        $activeBookingsPercent = $totalBookings > 0
            ? round(($activeBookings / $totalBookings) * 100, 0)
            : 0;

        // 2. Revenue (إجمالي الفواتير المدفوعة)
        $revenue = Invoice::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->sum('total');

        // Previous period revenue
        $prevRevenue = Invoice::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', '>=', $prevFrom)
            ->whereDate('paid_at', '<=', $prevTo)
            ->sum('total');

        $revenueTrend = $prevRevenue > 0
            ? (($revenue - $prevRevenue) / $prevRevenue) * 100
            : 0;

        // 3. Active Customers (عملاء لديهم حجز واحد على الأقل غير ملغي)
        $activeCustomers = DB::table('bookings')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->whereDate('bookings.booking_date', '>=', $from)
            ->whereDate('bookings.booking_date', '<=', $to)
            ->where('bookings.status', '!=', 'cancelled')
            ->where('users.user_type', '=', 'customer') // ✅ استخدام user_type مباشرة
            ->distinct('bookings.user_id')
            ->count('bookings.user_id');

        // 4. Average Rating (متوسط التقييمات للحجوزات المكتملة)
        $avgRating = Booking::query()
            ->whereDate('booking_date', '>=', $from)
            ->whereDate('booking_date', '<=', $to)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->avg('rating');

        $avgRating = $avgRating ? round($avgRating, 1) : 0;

        // Previous period rating
        $prevAvgRating = Booking::query()
            ->whereDate('booking_date', '>=', $prevFrom)
            ->whereDate('booking_date', '<=', $prevTo)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->avg('rating');

        $prevAvgRating = $prevAvgRating ? round($prevAvgRating, 1) : 0;
        $ratingTrend = $avgRating - $prevAvgRating;

        return response()->json([
            'success' => true,
            'data' => [
                'active_bookings' => $activeBookings,
                'active_bookings_percent' => $activeBookingsPercent,
                'revenue' => round($revenue, 0),
                'revenue_trend' => round($revenueTrend, 1),
                'active_customers' => $activeCustomers,
                'avg_rating' => $avgRating,
                'rating_trend' => round($ratingTrend, 1),
                'period' => [
                    'from' => $from,
                    'to' => $to,
                    'prev_from' => $prevFrom,
                    'prev_to' => $prevTo,
                ],
            ],
        ]);
    }

    /**
     * Get upcoming bookings (next 5 bookings from now OR bookings for specific date)
     */
    public function upcomingBookings(Request $request)
    {
        $date = $request->query('date');

        if ($date) {
            // Get bookings for specific date
            return $this->getBookingsByDate($date);
        }

        // Get next 5 upcoming bookings
        return $this->getUpcomingBookings();
    }

    /**
     * Get bookings for a specific date
     */
    private function getBookingsByDate(string $date)
    {
        try {
            $targetDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            $targetDate = Carbon::today()->toDateString();
        }

        // Get all bookings for this date
        $bookings = Booking::query()
            ->with(['user:id,name', 'service:id,name', 'car:id,vehicle_model_id,vehicle_make_id', 'employee.user:id,name'])
            ->whereDate('booking_date', $targetDate)
            ->whereIn('status', ['pending', 'confirmed', 'moving', 'arrived'])
            ->orderBy('start_time', 'asc')
            ->get();

        // Format bookings
        $formattedBookings = $bookings->map(function ($booking) {
            return $this->formatBooking($booking);
        });

        // Get stats for this specific date
        $dateStats = $this->getStatsForDate($targetDate);

        return response()->json([
            'success' => true,
            'bookings' => $formattedBookings,
            'stats' => $dateStats,
            'date' => $targetDate,
        ]);
    }

    /**
     * Get next 5 upcoming bookings from now
     */
    private function getUpcomingBookings()
    {
        $now = Carbon::now();

        // Get next 5 bookings starting from current date/time
        $bookings = Booking::query()
            ->with(['user:id,name', 'service:id,name', 'car:id,vehicle_model_id,vehicle_make_id', 'employee.user:id,name'])
            ->where(function ($q) use ($now) {
                // Future bookings (today or later)
                $q->where('booking_date', '>', $now->toDateString())
                    // OR today's bookings that haven't started yet
                    ->orWhere(function ($q2) use ($now) {
                    $q2->where('booking_date', '=', $now->toDateString())
                        ->where('start_time', '>=', $now->format('H:i:s'));
                });
            })
            ->whereIn('status', ['pending', 'confirmed', 'moving', 'arrived'])
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        // Format bookings
        $formattedBookings = $bookings->map(function ($booking) {
            return $this->formatBooking($booking);
        });

        // Get today's stats
        $todayStats = $this->getTodayStats();

        return response()->json([
            'success' => true,
            'bookings' => $formattedBookings,
            'stats' => $todayStats,
        ]);
    }

    /**
     * Format a booking for frontend display
     */
    private function formatBooking($booking): array
    {
        $serviceName = is_array($booking->service->name)
            ? ($booking->service->name[app()->getLocale()] ?? $booking->service->name['ar'] ?? 'غير محدد')
            : $booking->service->name;


        $makeNameData = $booking->car->make->name;
        $modelNameData = $booking->car->model->name;

        // بعد
        $makeName = is_array($makeNameData)
            ? ($makeNameData[app()->getLocale()] ?? $makeNameData['ar'] ?? 'غير محدد')
            : $makeNameData;

        $modelName = is_array($modelNameData)
            ? ($modelNameData[app()->getLocale()] ?? $modelNameData['ar'] ?? 'غير محدد')
            : $modelNameData;

        $carInfo = "{$makeName} {$modelName}";

        $startTime = Carbon::parse($booking->start_time);
        $endTime = Carbon::parse($booking->end_time);

        return [
            'id' => $booking->id,
            'time' => $startTime->format('H:i') . ' - ' . $endTime->format('H:i'),
            'period' => $startTime->format('A') === 'AM' ? 'صباحاً' : 'مساءً',
            'title' => "{$serviceName} - {$carInfo}",
            'meta' => $booking->user->name ?? 'غير محدد',
            'status' => $booking->status,
            'date' => $booking->booking_date,
            'employee' => $booking->employee?->user?->name,
        ];
    }

    /**
     * Get statistics for a specific date
     */
    private function getStatsForDate(string $date): array
    {
        $dateBookings = Booking::whereDate('booking_date', $date);

        $completed = (clone $dateBookings)->where('status', 'completed')->count();
        $cancelled = (clone $dateBookings)->where('status', 'cancelled')->count();
        $pending = (clone $dateBookings)->where('status', 'pending')->count();

        // Calculate today's revenue from completed bookings
        $revenue = (clone $dateBookings)
            ->where('status', 'completed')
            ->sum('total_snapshot');

        return [
            'completed' => $completed,
            'cancelled' => $cancelled,
            'pending' => $pending,
            'revenue' => 'SR ' . number_format($revenue, 0),
        ];
    }

    /**
     * Get today's booking statistics
     */
    private function getTodayStats(): array
    {
        $today = Carbon::today()->toDateString();
        return $this->getStatsForDate($today);
    }

    /**
     * Parse date range from request
     */
    private function parseRange(Request $request): array
    {
        $from = $request->query('from');
        $to = $request->query('to');

        try {
            $from = $from ? Carbon::parse($from)->toDateString() : Carbon::now()->startOfMonth()->toDateString();
        } catch (\Throwable $e) {
            $from = Carbon::now()->startOfMonth()->toDateString();
        }

        try {
            $to = $to ? Carbon::parse($to)->toDateString() : Carbon::now()->toDateString();
        } catch (\Throwable $e) {
            $to = Carbon::now()->toDateString();
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function daysLabels(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        $out = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $out[] = $d->toDateString();
        }
        return $out;
    }

    public function reportsStats(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        try {
            $targetDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            $targetDate = now()->toDateString();
        }

        $totalChildren = Student::count();

        // كل التقارير في هذا اليوم (كل القوالب)
        $baseQuery = ChildReport::query()->whereDate('report_date', $targetDate);

        $totalReports = (clone $baseQuery)->count();

        // عدد الأطفال اللي عندهم تقرير في هذا اليوم
        $childrenWithReport = (clone $baseQuery)
            ->select('child_id')
            ->distinct()
            ->count('child_id');

        $coveragePercent = $totalChildren > 0
            ? round($childrenWithReport * 100 / $totalChildren, 1)
            : 0.0;

        // توزيع "مزاج الطفل اليوم" إن وجد سؤال mood_general
        $moodQuestion = ReportQuestion::where('key', 'mood_general')->first();
        $moodDistribution = [];

        if ($moodQuestion) {
            $moodDistribution = ChildReportAnswer::selectRaw(
                'COALESCE(report_question_options.option_text, report_question_options.option_value, ?) as label,
                 COUNT(*) as total',
                ['غير محدد']
            )
                ->join('child_reports', 'child_reports.id', '=', 'child_report_answers.child_report_id')
                ->leftJoin('report_question_options', 'report_question_options.id', '=', 'child_report_answers.option_id')
                ->whereDate('child_reports.report_date', $targetDate)
                ->where('child_report_answers.question_id', $moodQuestion->id)
                ->groupBy('label')
                ->orderByDesc('total')
                ->get()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $targetDate,
                'total_children' => $totalChildren,
                'total_reports' => $totalReports,
                'children_with_report' => $childrenWithReport,
                'coverage_percent' => $coveragePercent,
                'mood_distribution' => $moodDistribution,
            ],
        ]);
    }

    public function reportsCoverageByTeacher(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        try {
            $targetDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            $targetDate = now()->toDateString();
        }

        // نستخدم فقط التقرير اليومي لو موجود
        $dailyTemplateId = ReportTemplate::where('key', 'daily_report')->value('id');

        // كل المعلمات اللي مربوطين بصف
        $teachers = Teacher::with([
            'user:id,name',
            'classroom:id,name',
        ])
            ->whereNotNull('classroom_id')
            ->get();

        if ($teachers->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $targetDate,
                    'rows' => [],
                ],
            ]);
        }

        $classroomIds = $teachers->pluck('classroom_id')->unique()->filter()->values();

        // عدد الطلاب في كل صف
        $studentsPerClassroom = Student::select(
            'classroom_id',
            DB::raw('COUNT(*) as total_children')
        )
            ->whereIn('classroom_id', $classroomIds)
            ->groupBy('classroom_id')
            ->pluck('total_children', 'classroom_id');

        // عدد الأطفال اللي عندهم تقرير في هذا اليوم في كل صف
        $reportsPerClassroom = ChildReport::query()
            ->join('students', 'students.id', '=', 'child_reports.child_id')
            ->select(
                'students.classroom_id',
                DB::raw('COUNT(DISTINCT child_reports.child_id) as reported_children')
            )
            ->whereDate('child_reports.report_date', $targetDate)
            ->whereIn('students.classroom_id', $classroomIds)
            ->when($dailyTemplateId, function ($q) use ($dailyTemplateId) {
                $q->where('child_reports.report_template_id', $dailyTemplateId);
            })
            ->groupBy('students.classroom_id')
            ->pluck('reported_children', 'students.classroom_id');

        // بناء الصفوف
        $rows = $teachers->map(function (Teacher $teacher) use ($studentsPerClassroom, $reportsPerClassroom) {
            $classroomId = $teacher->classroom_id;

            $totalChildren = (int) ($studentsPerClassroom[$classroomId] ?? 0);
            $reported = (int) ($reportsPerClassroom[$classroomId] ?? 0);
            $notReported = max($totalChildren - $reported, 0);

            $coverage = $totalChildren > 0
                ? round($reported * 100 / $totalChildren, 1)
                : 0.0;

            return [
                'teacher_name' => $teacher->user?->name ?? '-',
                'classroom_name' => $teacher->classroom?->name ?? '-',
                'total_children' => $totalChildren,
                'reported' => $reported,
                'not_reported' => $notReported,
                'coverage_percent' => $coverage,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $targetDate,
                'rows' => $rows,
            ],
        ]);
    }

    public function switchLang(string $locale, Request $request)
    {
        if (!in_array($locale, ['ar', 'en'], true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
