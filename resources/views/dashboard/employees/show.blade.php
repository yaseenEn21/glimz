@extends('base.layout.app')

@section('title', __('employees.show_title', ['name' => $employee->user->name ?? '']))

@push('custom-style')
<style>
    #employee_work_area_map_show {
        width: 100%;
        height: 320px;
        border-radius: 0.75rem;
        border: 1px solid #E4E6EF;
        overflow: hidden;
    }
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08) !important;
    }
</style>
@endpush

@section('content')

@section('top-btns')
    @can('employees.edit')
        <a href="{{ route('dashboard.employees.edit', $employee->id) }}" class="btn btn-primary">
            <i class="fa-solid fa-pen me-2"></i>
            {{ __('employees.edit') }}
        </a>
    @endcan
@endsection

@php
    $locale      = app()->getLocale();
    $isActive    = $employee->is_active && ($employee->user->is_active ?? false);
    $ratingFilled = (int) round((float) $ratingAvg);
    $ratingFilled = max(0, min(5, $ratingFilled));
@endphp


{{-- ══════════════════════════════════════════════════════════════
     Header Card — معلومات سريعة
══════════════════════════════════════════════════════════════ --}}
<div class="card mb-6">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-4">

            {{-- الأفاتار + الاسم --}}
            <div class="d-flex align-items-center gap-5">
                <div class="symbol symbol-70px symbol-circle">
                    <span class="symbol-label bg-light-primary text-primary fs-1 fw-bold">
                        {{ mb_substr($employee->user->name ?? '', 0, 1) }}
                    </span>
                </div>

                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <h2 class="fw-bold mb-0">{{ $employee->user->name ?? '—' }}</h2>

                        @if ($isActive)
                            <span class="badge badge-light-success">{{ __('employees.status_active') }}</span>
                        @else
                            <span class="badge badge-light-danger">{{ __('employees.status_inactive') }}</span>
                        @endif

                        <span class="badge badge-light-primary">{{ __('employees.biker_badge') }}</span>

                        @if ($employee->area_name)
                            <span class="badge badge-primary">{{ $employee->area_name }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-4 text-muted fw-semibold fs-7">
                        <span>
                            <i class="fa-solid fa-phone fs-6 me-1 text-gray-400"></i>
                            {{ $employee->user->mobile ?? '—' }}
                        </span>
                        @if (!empty($employee->user->email))
                            <span>
                                <i class="fa-solid fa-envelope fs-6 me-1 text-gray-400"></i>
                                {{ $employee->user->email }}
                            </span>
                        @endif
                        <span>
                            <i class="fa-solid fa-venus-mars fs-6 me-1 text-gray-400"></i>
                            {{ $employee->user?->gender === 'female' ? __('employees.fields.gender_female') : __('employees.fields.gender_male') }}
                        </span>
                        @if ($employee->user->birth_date)
                            <span>
                                <i class="fa-solid fa-cake-candles fs-6 me-1 text-gray-400"></i>
                                {{ optional($employee->user->birth_date)->format('Y-m-d') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- تاريخ الإنشاء --}}
            <div class="text-muted fs-7 text-md-end">
                <div class="mb-1">
                    <i class="fa-regular fa-calendar me-1"></i>
                    {{ __('employees.created_at') }}: <span class="fw-semibold text-gray-700">{{ optional($employee->created_at)->format('Y-m-d') }}</span>
                </div>
                <div>
                    <i class="fa-regular fa-clock me-1"></i>
                    {{ __('employees.last_update') }}: <span class="fw-semibold text-gray-700">{{ optional($employee->updated_at)->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════
     KPI Cards — الإحصائيات السريعة
══════════════════════════════════════════════════════════════ --}}
<div class="row g-5 mb-6">

    {{-- إجمالي الحجوزات --}}
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-4 p-6">
                <div class="symbol symbol-55px">
                    <span class="symbol-label bg-light-primary">
                        <i class="fa-solid fa-clipboard-list fs-2 text-primary"></i>
                    </span>
                </div>
                <div>
                    <div class="fw-bold fs-2 text-gray-800">{{ number_format($totalBookings) }}</div>
                    <div class="text-muted fs-7">إجمالي الحجوزات</div>
                </div>
            </div>
        </div>
    </div>

    {{-- الحجوزات المنجزة --}}
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-4 p-6">
                <div class="symbol symbol-55px">
                    <span class="symbol-label bg-light-success">
                        <i class="fa-solid fa-circle-check fs-2 text-success"></i>
                    </span>
                </div>
                <div>
                    <div class="fw-bold fs-2 text-gray-800">{{ number_format($completedBookings) }}</div>
                    <div class="text-muted fs-7">منجزة</div>
                </div>
            </div>
        </div>
    </div>

    {{-- الحجوزات المعلقة --}}
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-4 p-6">
                <div class="symbol symbol-55px">
                    <span class="symbol-label bg-light-warning">
                        <i class="fa-solid fa-hourglass-half fs-2 text-warning"></i>
                    </span>
                </div>
                <div>
                    <div class="fw-bold fs-2 text-gray-800">{{ number_format($pendingBookings) }}</div>
                    <div class="text-muted fs-7">قيد التنفيذ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- الحجوزات الملغاة --}}
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-4 p-6">
                <div class="symbol symbol-55px">
                    <span class="symbol-label bg-light-danger">
                        <i class="fa-solid fa-ban fs-2 text-danger"></i>
                    </span>
                </div>
                <div>
                    <div class="fw-bold fs-2 text-gray-800">{{ number_format($cancelledBookings) }}</div>
                    <div class="text-muted fs-7">ملغاة</div>
                </div>
            </div>
        </div>
    </div>

</div>


{{-- Tabs --}}
<div class="card">
    <div class="card-header pt-5 border-0">
        <ul class="nav nav-tabs nav-line-tabs fs-6 mb-0">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab_stats">
                    <i class="fa-solid fa-chart-bar me-2"></i>الإحصائيات
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_overview">
                    <i class="fa-solid fa-user me-2"></i>{{ __('employees.tab_overview') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_schedule">
                    <i class="fa-solid fa-calendar-week me-2"></i>جدول العمل
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            {{-- ════════════════════════════════════════════════════════
                 Tab 1: الإحصائيات
            ════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="tab_stats" role="tabpanel">
                <div class="row g-6">

                    {{-- العمود الأيسر --}}
                    <div class="col-xl-8">

                        {{-- الرسم البياني - آخر 6 أشهر --}}
                        <div class="card card-flush mb-6 shadow-sm">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title fw-bold">الحجوزات المنجزة — آخر 6 أشهر</h3>
                            </div>
                            <div class="card-body pt-0">
                                <canvas id="bookingsChart" height="120"></canvas>
                            </div>
                        </div>

                        {{-- نسبة الإنجاز + الإيرادات --}}
                        <div class="row g-5">

                            {{-- نسبة الإنجاز --}}
                            <div class="col-md-6">
                                <div class="card card-flush shadow-sm h-100">
                                    <div class="card-body p-7">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="fw-bold fs-5 text-gray-700">نسبة الإنجاز</div>
                                            <span class="badge badge-light-{{ $completionRate >= 80 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger') }} fs-6">
                                                {{ $completionRate }}%
                                            </span>
                                        </div>
                                        <div class="progress mb-3" style="height: 12px; border-radius: 6px;">
                                            <div class="progress-bar bg-{{ $completionRate >= 80 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger') }}"
                                                role="progressbar"
                                                style="width: {{ $completionRate }}%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted fs-8">
                                            <span>{{ $completedBookings }} منجزة</span>
                                            <span>{{ $totalBookings }} إجمالي</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- نسبة الإلغاء --}}
                            <div class="col-md-6">
                                <div class="card card-flush shadow-sm h-100">
                                    <div class="card-body p-7">
                                        @php
                                            $cancelRate = $totalBookings > 0
                                                ? round(($cancelledBookings / $totalBookings) * 100, 1)
                                                : 0;
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <div class="fw-bold fs-5 text-gray-700">نسبة الإلغاء</div>
                                            <span class="badge badge-light-{{ $cancelRate <= 10 ? 'success' : ($cancelRate <= 25 ? 'warning' : 'danger') }} fs-6">
                                                {{ $cancelRate }}%
                                            </span>
                                        </div>
                                        <div class="progress mb-3" style="height: 12px; border-radius: 6px;">
                                            <div class="progress-bar bg-danger"
                                                role="progressbar"
                                                style="width: {{ $cancelRate }}%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted fs-8">
                                            <span>{{ $cancelledBookings }} ملغاة</span>
                                            <span>{{ $totalBookings }} إجمالي</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- العمود الأيمن --}}
                    <div class="col-xl-4">

                        {{-- الإيرادات الكلية --}}
                        <div class="card card-flush shadow-sm mb-5">
                            <div class="card-body p-7">
                                <div class="d-flex align-items-center gap-4 mb-5">
                                    <div class="symbol symbol-50px">
                                        <span class="symbol-label bg-light-success">
                                            <i class="fa-solid fa-money-bill-wave fs-2 text-success"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">إجمالي الإيرادات</div>
                                        <div class="fw-bold fs-2 text-success">
                                            {{ number_format((float) $totalRevenue, 2) }}
                                            <span class="fs-7 text-muted">SAR</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator separator-dashed mb-5"></div>

                                <div class="d-flex align-items-center gap-4">
                                    <div class="symbol symbol-50px">
                                        <span class="symbol-label bg-light-info">
                                            <i class="fa-solid fa-calendar-day fs-2 text-info"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">إيرادات هذا الشهر</div>
                                        <div class="fw-bold fs-3 text-info">
                                            {{ number_format((float) $thisMonthRevenue, 2) }}
                                            <span class="fs-7 text-muted">SAR</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- حجوزات هذا الشهر --}}
                        <div class="card card-flush shadow-sm mb-5">
                            <div class="card-body p-7">
                                <div class="d-flex align-items-center gap-4">
                                    <div class="symbol symbol-50px">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="fa-solid fa-calendar-check fs-2 text-primary"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-muted fs-8 mb-1">حجوزات هذا الشهر</div>
                                        <div class="fw-bold fs-2">{{ number_format($thisMonthBookings) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- التقييم --}}
                        <div class="card card-flush shadow-sm">
                            <div class="card-body p-7">
                                <div class="fw-bold fs-5 text-gray-700 mb-5">تقييم الموظف</div>

                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <div class="fw-bold fs-2">{{ $ratingAvg > 0 ? $ratingAvg : '—' }}</div>
                                        <div class="text-muted fs-8">
                                            من {{ $ratingCount }} تقييم
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fa-solid fa-star fs-5 {{ $i <= $ratingFilled ? 'text-warning' : 'text-gray-300' }}"></i>
                                        @endfor
                                    </div>
                                </div>

                                @if ($ratingCount > 0)
                                    @foreach ([5, 4, 3, 2, 1] as $star)
                                        @php
                                            // نحسب توزيع التقييمات تقريبياً (بدون جدول منفصل)
                                            $pct = 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="text-muted fs-8" style="width: 20px;">{{ $star }}</div>
                                            <i class="fa-solid fa-star text-warning fs-8"></i>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-warning" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-muted fs-7">لا توجد تقييمات بعد</div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            {{-- ════════════════════════════════════════════════════════
                 Tab 2: نظرة عامة
            ════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab_overview" role="tabpanel">
                <div class="row g-6">

                    {{-- المعلومات الأساسية --}}
                    <div class="col-xl-4">
                        <div class="card card-flush shadow-sm">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title fw-bold">{{ __('employees.basic_info') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                @foreach ([
                                    [__('employees.fields.name'),         $employee->user->name ?? '—'],
                                    [__('employees.fields.mobile'),       $employee->user->mobile ?? '—'],
                                    [__('employees.fields.email'),        $employee->user->email ?? '—'],
                                    [__('employees.fields.birth_date'),   optional($employee->user->birth_date)->format('Y-m-d') ?? '—'],
                                ] as [$label, $value])
                                    <div class="mb-4">
                                        <div class="text-muted fs-8 mb-1">{{ $label }}</div>
                                        <div class="fw-semibold">{{ $value }}</div>
                                    </div>
                                @endforeach

                                <div class="mb-4">
                                    <div class="text-muted fs-8 mb-1">{{ __('employees.fields.gender') }}</div>
                                    <div class="fw-semibold">
                                        {{ $employee->user?->gender === 'female' ? __('employees.fields.gender_female') : __('employees.fields.gender_male') }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="text-muted fs-8 mb-1">{{ __('employees.fields.is_active') }}</div>
                                    @if ($employee->is_active)
                                        <span class="badge badge-light-success">{{ __('employees.status_active') }}</span>
                                    @else
                                        <span class="badge badge-light-danger">{{ __('employees.status_inactive') }}</span>
                                    @endif
                                </div>

                                <div>
                                    <div class="text-muted fs-8 mb-1">{{ __('employees.fields.notification') }}</div>
                                    @if ($employee->user->notification ?? false)
                                        <span class="badge badge-light-primary">{{ __('employees.notification_on') }}</span>
                                    @else
                                        <span class="badge badge-light">{{ __('employees.notification_off') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- الخدمات + منطقة العمل --}}
                    <div class="col-xl-8">

                        {{-- الخدمات --}}
                        <div class="card card-flush shadow-sm mb-6">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title fw-bold">{{ __('employees.services_card_title') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                @if ($employee->services->isEmpty())
                                    <div class="text-muted fs-7">{{ __('employees.no_services_assigned') }}</div>
                                @else
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($employee->services as $service)
                                            @php
                                                $sName = $service->name[$locale] ?? (is_array($service->name) ? reset($service->name) : $service->name);
                                            @endphp
                                            <span class="badge badge-light-primary fw-semibold">{{ $sName }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- الخريطة --}}
                        <div class="card card-flush shadow-sm">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title fw-bold">{{ __('employees.work_area_card_title') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                <div id="employee_work_area_map_show"></div>
                                @if (empty($workAreaPolygon))
                                    <div class="text-muted fs-7 mt-3">{{ __('employees.no_work_area_defined') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- ════════════════════════════════════════════════════════
                 Tab 3: جدول العمل
            ════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab_schedule" role="tabpanel">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        @php
                            $daysLabels = [
                                'saturday'  => $locale === 'ar' ? 'السبت'    : 'Saturday',
                                'sunday'    => $locale === 'ar' ? 'الأحد'    : 'Sunday',
                                'monday'    => $locale === 'ar' ? 'الاثنين'  : 'Monday',
                                'tuesday'   => $locale === 'ar' ? 'الثلاثاء' : 'Tuesday',
                                'wednesday' => $locale === 'ar' ? 'الأربعاء' : 'Wednesday',
                                'thursday'  => $locale === 'ar' ? 'الخميس'  : 'Thursday',
                                'friday'    => $locale === 'ar' ? 'الجمعة'   : 'Friday',
                            ];
                        @endphp

                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6">
                                <thead>
                                    <tr class="fw-bold text-muted fs-7 text-uppercase">
                                        <th>اليوم</th>
                                        <th>{{ __('employees.working_hours_work') }}</th>
                                        <th>{{ __('employees.working_hours_break') }}</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($daysLabels as $dayKey => $dayLabel)
                                        @php
                                            $work  = $weeklyByDay[$dayKey]['work']  ?? null;
                                            $break = $weeklyByDay[$dayKey]['break'] ?? null;
                                            $isWorkDay = $work && ($work['is_active'] ?? false);
                                        @endphp
                                        <tr class="{{ $isWorkDay ? '' : 'opacity-50' }}">
                                            <td class="fw-bold">{{ $dayLabel }}</td>
                                            <td>
                                                @if ($isWorkDay)
                                                    <span class="badge badge-light-success fw-semibold">
                                                        {{ $work['start_time'] ?? '--:--' }} – {{ $work['end_time'] ?? '--:--' }}
                                                    </span>
                                                @else
                                                    <span class="text-muted fs-8">{{ __('employees.off_day') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($break && ($break['is_active'] ?? false))
                                                    <span class="badge badge-light-warning fw-semibold">
                                                        {{ $break['start_time'] ?? '--:--' }} – {{ $break['end_time'] ?? '--:--' }}
                                                    </span>
                                                @else
                                                    <span class="text-muted fs-8">{{ __('employees.no_break') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($isWorkDay)
                                                    <span class="badge badge-light-success">يعمل</span>
                                                @else
                                                    <span class="badge badge-light-danger">إجازة</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('custom-script')

{{-- Chart.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
(function () {

    // ─── الرسم البياني ──────────────────────────────────────────
    const chartData = @json($last6Months);

    const ctx = document.getElementById('bookingsChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.label),
                datasets: [{
                    label: 'حجوزات منجزة',
                    data: chartData.map(d => d.count),
                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                    borderColor: 'rgba(59, 130, 246, 0.8)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} حجز`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }


    // ─── Google Maps ─────────────────────────────────────────────
    const polygonData = @json($workAreaPolygon);

    window.initEmployeeShowMap = function () {
        const mapEl = document.getElementById('employee_work_area_map_show');
        if (!mapEl) return;

        const map = new google.maps.Map(mapEl, {
            center: { lat: 31.5, lng: 34.47 },
            zoom: 11,
        });

        if (Array.isArray(polygonData) && polygonData.length >= 3) {
            const path = polygonData.map(p => ({ lat: Number(p.lat), lng: Number(p.lng) }));

            new google.maps.Polygon({
                paths: path,
                strokeColor: '#0d6efd',
                strokeOpacity: 0.9,
                strokeWeight: 2,
                fillColor: '#0d6efd',
                fillOpacity: 0.20,
                clickable: false,
                map,
            });

            const bounds = new google.maps.LatLngBounds();
            path.forEach(p => bounds.extend(p));
            map.fitBounds(bounds);
        }
    };

})();
</script>

<script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initEmployeeShowMap"
    async defer></script>

@endpush