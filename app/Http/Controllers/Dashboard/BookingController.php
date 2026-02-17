<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PackageSubscription;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Zone;
use App\Services\BookingCancellationService;
use App\Services\WalletService;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use App\Http\Requests\Dashboard\BookingStoreRequest;
use App\Http\Requests\Dashboard\QuickCustomerRequest;
use App\Http\Requests\Dashboard\DashboardCarStoreRequest;
use App\Http\Requests\Dashboard\DashboardAddressStoreRequest;
use App\Models\Address;
use App\Models\BookingProduct;
use App\Models\Car;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\SlotService;
use Illuminate\Validation\ValidationException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BookingDeletionService;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:bookings.view')->only(['index', 'datatable', 'show']);
        $this->middleware('can:bookings.edit')->only(['updateStatus', 'edit', 'update']);
    }

    public function lookupUserPackageSubscriptions(User $user, Request $request)
    {
        $serviceId = (int) $request->query('service_id');

        $q = $user->packageSubscriptions()
            ->activeWithRemaining()
            ->with(['package:id,name,washes_count']);

        // ✅ فلترة: رجّع فقط الاشتراكات اللي باقتها تشمل الخدمة المختارة (لو تم اختيار خدمة)
        if ($serviceId > 0) {
            $q->whereHas('package.services', fn($qq) => $qq->where('services.id', $serviceId));
        }

        $subs = $q->orderByDesc('ends_at')
            ->limit(100)
            ->get(['id', 'package_id', 'ends_at', 'remaining_washes', 'status']);

        $isAr = app()->getLocale() === 'ar';

        $items = $subs->map(function (PackageSubscription $s) use ($isAr) {
            $pkgName = $s->package?->getNameForLocale() ?? ('#' . $s->package_id);
            $ends = $s->ends_at?->format('Y-m-d') ?? '—';
            $rem = (int) $s->remaining_washes;
            $total = (int) ($s->package?->washes_count ?? 0);

            $text = $isAr
                ? "{$pkgName} • متبقي {$rem}/{$total} • ينتهي {$ends}"
                : "{$pkgName} • remaining {$rem}/{$total} • ends {$ends}";

            return [
                'id' => $s->id,
                'text' => $text,
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function index()
    {
        // للفلاتر (اختياري)
        $services = Service::query()->select('id', 'name')->where('is_active', true)->orderBy('sort_order')->get();
        $employees = Employee::query()->select('id')->get(); // إن عندك حقول اسم/موبايل اعرضهم بالواجهة
        $zones = class_exists(Zone::class) ? Zone::query()->select('id', 'name')->orderBy('sort_order')->get() : collect();

        view()->share([
            'title' => __('bookings.title'),
            'page_title' => __('bookings.title'),
        ]);

        return view('dashboard.bookings.index', compact('services', 'employees', 'zones'));
    }

    public function datatable(DataTables $datatable, Request $request)
    {
        $query = Booking::query()
            ->select('bookings.*')
            ->with([
                'user:id,name,mobile',
                'service:id,name',
                'employee:id,user_id',
                'employee.user:id,name,mobile',
                'partner:id,name', // ✅ إضافة
                'createdBy:id,name', // ✅ إضافة
            ])
            ->latest('id');

        // -------- Filters --------
        if ($status = $request->get('status')) {
            if (in_array($status, ['pending', 'confirmed', 'moving', 'arrived', 'completed', 'cancelled'], true)) {
                $query->where('status', $status);
            }
        }

        if ($tp = $request->get('time_period')) {
            if (in_array($tp, ['morning', 'evening', 'all'], true)) {
                $query->where('time_period', $tp);
            }
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', (int) $request->get('service_id'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->get('employee_id'));
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', (int) $request->get('zone_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('booking_date', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('booking_date', '<=', $request->get('to'));
        }

        if ($search = trim((string) $request->get('search_custom'))) {
            $query->where(function ($q) use ($search) {
                // id
                if (is_numeric($search)) {
                    $q->orWhere('bookings.id', (int) $search);
                }

                // user name/mobile
                $q->orWhereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });

                // service name (json)
                $q->orWhereHas('service', function ($s) use ($search) {
                    $s->where('name->ar', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%");
                });

                // cancel reason/note
                $q->orWhere('cancel_reason', 'like', "%{$search}%")
                    ->orWhere('cancel_note', 'like', "%{$search}%");
            });
        }

        return $datatable->eloquent($query)
            ->addColumn('customer', function (Booking $row) {
                return view('dashboard.partials.user_cell', [
                    'user' => $row->user,
                ])->render();
            })
            ->addColumn('service_name', function (Booking $row) {
                return e($this->i18n($row->service?->name));
            })
            ->addColumn('schedule', function (Booking $row) {
                $d = $row->booking_date ? Carbon::parse($row->booking_date)->format('Y-m-d') : '—';
                $s = $row->start_time ? substr((string) $row->start_time, 0, 5) : '—';

                return e($d) . '<br>' . e($s);
            })
            ->addColumn('status_badge', function (Booking $row) {
                return $this->statusBadge($row->status);
            })
            ->addColumn('status_control', function (Booking $row) {
                return view('dashboard.bookings._status_control', ['booking' => $row])->render();
            })
            ->addColumn('total', function (Booking $row) {
                $totalAmount = format_currency($row->total_snapshot, $row->currency);

                // ✅ تحديد حالة الدفع
                $invoices = $row->invoices;

                if ($invoices->isEmpty()) {
                    $paymentStatus = '';
                } else {
                    // تحقق من وجود فاتورة واحدة على الأقل غير مدفوعة
                    $hasUnpaid = $invoices->contains('status', 'unpaid');

                    if ($hasUnpaid) {
                        $paymentStatus = '<span class="badge badge-light-danger fs-8 mt-1">' .
                            __('bookings.payment_status.unpaid') .
                            '</span>';
                    } else {
                        // جميع الفواتير مدفوعة
                        $paymentStatus = '<span class="badge badge-light-success fs-8 mt-1">' .
                            __('bookings.payment_status.paid') .
                            '</span>';
                    }
                }

                return '<div class="d-flex flex-column align-items-center">
                <span class="fw-bold">' . $totalAmount . '</span>
                ' . $paymentStatus . '
            </div>';
            })
            ->addColumn('employee_label', function (Booking $row) {
                return e($row->employee?->user?->name ?? '—');
            })
            // ✅ إضافة عمود مصدر الحجز
            ->addColumn('booking_source', function (Booking $row) {
                // الأولوية للـ partner
                if ($row->partner_id && $row->partner) {
                    return '<span class="badge badge-light-success">' .
                        e($row->partner->name) .
                        '</span>';
                }

                // ثانياً: created_by
                if ($row->created_by && $row->createdBy) {
                    return '<span class="badge badge-light-primary">' .
                        e($row->createdBy->name) .
                        '</span>';
                }

                // تطبيق الموبايل
                return '<span class="badge badge-light-info">' .
                    __('bookings.source.mobile_app') .
                    '</span>';
            })
            ->addColumn('actions', function (Booking $row) {
                return view('dashboard.bookings._actions', ['booking' => $row])->render();
            })
            ->rawColumns(['customer', 'total', 'schedule', 'status_badge', 'status_control', 'booking_source', 'actions'])
            ->make(true);
    }

    public function create()
    {
        view()->share([
            'title' => __('bookings.create.title'),
            'page_title' => __('bookings.create.title'),
        ]);

        $services = Service::query()
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'duration_minutes', 'price', 'discounted_price']);

        return view('dashboard.bookings.create', compact('services'));
    }

    // --------------------------------------------
    // AJAX: users lookup (select2)
    // --------------------------------------------
    public function usersLookup(Request $request)
    {
        $search = trim((string) $request->input('q'));

        $q = User::query()->where('is_active', true);

        // لو عندك user_type
        $q->whereIn('user_type', ['customer', 'user', 'client'])->orWhereNull('user_type');

        if ($search !== '') {
            $q->where(function ($x) use ($search) {
                $x->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%"); // ✅ عدّل لو اسم عمود الجوال مختلف
            });
        }

        $items = $q->orderBy('id', 'desc')->limit(20)->get(['id', 'name', 'mobile']);

        return response()->json([
            'results' => $items->map(fn($u) => [
                'id' => $u->id,
                'text' => trim($u->name . ' - ' . ($u->mobile ?? '')),
            ]),
        ]);
    }

    public function userCars(User $user)
    {
        $cars = Car::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get(['id', 'vehicle_make_id', 'vehicle_model_id', 'plate_letters', 'plate_number']);

        return response()->json([
            'items' => $cars->map(fn($c) => [
                'id' => $c->id,
                'text' => "{$c->plate_letters}-{$c->plate_number}",
            ]),
        ]);
    }

    public function userAddresses(User $user)
    {
        $addresses = Address::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get(['id', 'type', 'city', 'area', 'address_line', 'lat', 'lng']);

        return response()->json([
            'items' => $addresses->map(fn($a) => [
                'id' => $a->id,
                'text' => trim(($a->city ?? '') . ' - ' . ($a->area ?? '') . ' - ' . ($a->address_line ?? '')),
                'lat' => (string) $a->lat,
                'lng' => (string) $a->lng,
            ]),
        ]);
    }

    // --------------------------------------------
    // AJAX: products lookup (select2)
    // --------------------------------------------
    public function productsLookup(Request $request)
    {
        $search = trim((string) $request->input('q'));

        $q = Product::query()->where('is_active', true);

        if ($search !== '') {
            // لو name json استخدم like على raw أو عندك helper i18n
            $q->where(function ($x) use ($search) {
                $x->where('name->ar', 'like', "%{$search}%")
                    ->orWhere('name->en', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('id')->limit(20)->get(['id', 'name', 'price']);

        return response()->json([
            'results' => $items->map(fn($p) => [
                'id' => $p->id,
                'text' => (function () use ($p) {
                    $name = function_exists('i18n') ? i18n($p->name) : ($p->name['ar'] ?? $p->name['en'] ?? '');
                    return $name . ' - ' . rtrim(rtrim((string) $p->price, '0'), '.');
                })(),
            ]),
        ]);
    }

    // --------------------------------------------
    // AJAX: slots (from SlotService)
    // --------------------------------------------
    public function slots(Request $request, SlotService $slotService)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'booking_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $address = Address::query()->select(['id', 'lat', 'lng'])->findOrFail($data['address_id']);

        $date = Carbon::createFromFormat('Y-m-d', $data['booking_date'])->format('d-m-Y');

        $slots = $slotService->getSlots(
            $date,
            (int) $data['service_id'],
            (float) $address->lat,
            (float) $address->lng,
            null,
            'blocks'
        );
        return response()->json($slots);
    }

    // --------------------------------------------
    // MODAL: create quick customer
    // --------------------------------------------
    public function storeQuickCustomer(QuickCustomerRequest $request)
    {
        $data = $request->validated();

        $u = DB::transaction(function () use ($data) {
            $user = new User();
            $user->name = $data['name'];
            $user->mobile = $data['mobile']; // ✅ عدّل لو اسم العمود مختلف
            $user->user_type = $user->user_type ?? 'customer';
            $user->password = Hash::make('12345678');
            $user->is_active = true;
            $user->created_by = auth()->id();
            $user->updated_by = auth()->id();
            $user->save();

            return $user;
        });

        return response()->json([
            'ok' => true,
            'id' => $u->id,
            'text' => trim($u->name . ' - ' . ($u->mobile ?? '')),
            'message' => __('bookings.customer_created'),
        ]);
    }

    // --------------------------------------------
    // MODAL: create car for user
    // --------------------------------------------
    public function storeUserCar(DashboardCarStoreRequest $request, User $user)
    {
        $data = $request->validated();

        // unique per user + plate_number combo (مثل api)
        $exists = Car::query()
            ->where('user_id', $user->id)
            ->where('plate_number', $data['plate_number'])
            ->where('plate_letters', strtoupper($data['plate_letters']))
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.car_duplicate'),
                'errors' => ['plate_letters' => [__('bookings.car_duplicate')]],
            ], 422);
        }

        $car = DB::transaction(function () use ($user, $data, $request) {
            if ($request->boolean('is_default')) {
                Car::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $data['user_id'] = $user->id;
            $data['plate_letters'] = strtoupper((string) $data['plate_letters']);
            $data['is_default'] = $request->boolean('is_default');

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            return Car::create($data);
        });

        return response()->json([
            'ok' => true,
            'id' => $car->id,
            'text' => "{$car->plate_letters}-{$car->plate_number}",
            'message' => __('bookings.car_created'),
        ]);
    }

    // --------------------------------------------
    // MODAL: create address for user
    // --------------------------------------------
    public function storeUserAddress(DashboardAddressStoreRequest $request, User $user)
    {
        $data = $request->validated();

        $link = trim((string) ($data['address_link'] ?? ''));

        $coords = extract_lat_lng_from_maps_link($link);

        \Log::info('Coords :: ', $coords);

        if (!$coords || empty($coords['lat']) || empty($coords['lng'])) {
            throw ValidationException::withMessages([
                'address_link' => [__('bookings.address.link_invalid_or_failed')],
            ]);
        }

        $lat = (float) $coords['lat'];
        $lng = (float) $coords['lng'];

        $address = DB::transaction(function () use ($user, $data, $request, $lat, $lng, $link) {

            $makeDefault = $request->boolean('is_default', false);

            $hasAny = Address::where('user_id', $user->id)->exists();
            if (!$hasAny)
                $makeDefault = true;

            if ($makeDefault) {
                Address::where('user_id', $user->id)->update(['is_default' => false]);
            }

            // ✅ خزّن الرابط (meta أو عمود)
            $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
            $meta['address_link'] = $link;

            unset($data['address_link']);

            $data['user_id'] = $user->id;
            $data['is_default'] = $makeDefault;

            // ✅ الإحداثيات من الرابط
            $data['lat'] = $lat;
            $data['lng'] = $lng;

            $data['meta'] = $meta ?: null;
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            return Address::create($data);
        });

        $label = trim(($address->city ?? '') . ' - ' . ($address->area ?? '') . ' - ' . ($address->address_line ?? ''));

        return response()->json([
            'ok' => true,
            'id' => $address->id,
            'text' => $label,
            'lat' => (string) $address->lat,
            'lng' => (string) $address->lng,
            'message' => __('bookings.address_created'),
        ]);
    }

    // --------------------------------------------
    // Vehicle lookups
    // --------------------------------------------
    public function vehicleMakesLookup(Request $request)
    {
        $search = trim((string) $request->input('q'));

        $q = \App\Models\VehicleMake::query();

        if ($search !== '') {
            $q->where('name', 'like', "%{$search}%");
        }

        $items = $q->orderBy('name')->limit(25)->get(['id', 'name']);

        return response()->json([
            'results' => $items->map(fn($m) => ['id' => $m->id, 'text' => $m->name]),
        ]);
    }

    public function vehicleModelsLookup(Request $request)
    {
        $makeId = (int) $request->input('make_id');
        $search = trim((string) $request->input('q'));

        $q = \App\Models\VehicleModel::query();

        if ($makeId) {
            $q->where('vehicle_make_id', $makeId);
        }

        if ($search !== '') {
            $q->where('name', 'like', "%{$search}%");
        }

        $items = $q->orderBy('name')->limit(25)->get(['id', 'name', 'vehicle_make_id']);

        return response()->json([
            'results' => $items->map(fn($m) => ['id' => $m->id, 'text' => $m->name]),
        ]);
    }

    // --------------------------------------------
    // STORE booking (dashboard)
    // --------------------------------------------

    public function store(
        BookingStoreRequest $request,
        SlotService $slotService,
        InvoiceService $invoiceService
    ) {
        $data = $request->validated();

        $user = User::query()
            ->whereKey($data['user_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $car = Car::query()
            ->whereKey($data['car_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $address = Address::query()
            ->whereKey($data['address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->firstOrFail();

        $duration = (int) $service->duration_minutes;
        $startTime = $data['start_time']; // H:i (24h)

        // ✅ نستخدم التاريخ المرسل من الفورم لجلب الـ slots
        $apiDate = Carbon::createFromFormat('Y-m-d', $data['booking_date'])->format('d-m-Y');

        $slots = $slotService->getSlots(
            $apiDate,
            (int) $service->id,
            (float) $address->lat,
            (float) $address->lng,
            null,
            'blocks'
        );

        if (empty($slots['items'])) {
            $code = $slots['meta']['error_code'] ?? null;
            $msg = __('bookings.no_slots');
            if ($code === 'OUT_OF_COVERAGE')
                $msg = __('bookings.out_of_coverage');
            if ($code === 'NO_WORKING_HOURS')
                $msg = __('bookings.no_working_hours');

            return response()->json([
                'ok' => false,
                'message' => $msg,
                'errors' => ['start_time' => [$msg]],
            ], 422);
        }

        // ✅ نطابق بالـ start_time (24h format)
        $slot = collect($slots['items'])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
        if (!$slot) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.time_not_available'),
                'errors' => ['start_time' => [__('bookings.time_not_available')]],
            ], 422);
        }

        // ✅ التاريخ الفعلي للحجز (من الـ slot — قد يكون اليوم التالي)
        $dbDate = $slot['booking_date'];
        // ✅ وقت النهاية (من الـ slot — يتعامل مع منتصف الليل صح)
        $endTime = $slot['end_time'];

        $employees = $slot['employees'] ?? [];
        if (empty($employees)) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.no_employee_for_slot'),
                'errors' => ['employee_id' => [__('bookings.no_employee_for_slot')]],
            ], 422);
        }

        $pickedEmployeeId = $data['employee_id'] ?? null;
        if ($pickedEmployeeId) {
            $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $pickedEmployeeId);
            if (!$found) {
                return response()->json([
                    'ok' => false,
                    'message' => __('bookings.employee_not_available'),
                    'errors' => ['employee_id' => [__('bookings.employee_not_available')]],
                ], 422);
            }
        } else {
            $pickedEmployeeId = (int) $employees[0]['employee_id'];
        }

        // ✅ pricing resolve (كما في api)
        $pricing = app(\App\Services\BookingPricingService::class)
            ->resolve($service, $user, $address, $startTime);

        $finalUnit = (float) ($pricing['final_unit_price'] ?? 0.0);

        // ============================
        // ✅ PACKAGE VALIDATION (مهم)
        // ============================
        $subscriptionId = !empty($data['package_subscription_id']) ? (int) $data['package_subscription_id'] : null;
        $usingPackage = false;
        $sub = null;

        if ($subscriptionId) {
            $sub = PackageSubscription::query()
                ->activeWithRemaining()
                ->whereKey($subscriptionId)
                ->where('user_id', $user->id)
                ->whereHas('package.services', fn($q) => $q->where('services.id', $service->id))
                ->with(['package:id,name,washes_count'])
                ->first();

            if (!$sub) {
                return response()->json([
                    'ok' => false,
                    'message' => __('bookings.package.invalid_subscription'),
                    'errors' => ['package_subscription_id' => [__('bookings.package.invalid_subscription')]],
                ], 422);
            }

            $usingPackage = true;
        }

        // ✅ what customer pays for service line
        $chargeAmount = $usingPackage ? 0.0 : $finalUnit;

        $booking = DB::transaction(function () use ($data, $user, $car, $address, $service, $dbDate, $startTime, $endTime, $duration, $pricing, $finalUnit, $usingPackage, $chargeAmount, $pickedEmployeeId, $invoiceService, $sub, $subscriptionId) {

            $meta = [];

            // ============================
            // ✅ خصم الغسلة فورًا (Dashboard)
            // ============================
            if ($usingPackage && $subscriptionId) {

                $sub = PackageSubscription::query()
                    ->whereKey($subscriptionId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->with(['package:id,washes_count', 'package.services:id'])
                    ->first();

                // تحقق قوي داخل الترانزاكشن (race-safe)
                $isActive = $sub
                    && $sub->status === 'active'
                    && $sub->ends_at
                    && $sub->ends_at->endOfDay()->gte(now());

                if (!$isActive) {
                    throw ValidationException::withMessages([
                        'package_subscription_id' => [__('bookings.package.invalid_subscription')],
                    ]);
                }

                if ((int) $sub->remaining_washes <= 0) {
                    throw ValidationException::withMessages([
                        'package_subscription_id' => [__('bookings.package.no_remaining_washes')],
                    ]);
                }

                // هل الباقة تغطي الخدمة؟
                $covers = $sub->package
                    && $sub->package->services
                    && $sub->package->services->contains('id', $service->id);

                if (!$covers) {
                    throw ValidationException::withMessages([
                        'package_subscription_id' => [__('bookings.package.invalid_subscription')],
                    ]);
                }

                $before = (int) $sub->remaining_washes;
                $after = $before - 1;

                // ✅ الخصم الفعلي
                $sub->update([
                    'remaining_washes' => $after,
                    'updated_at' => now(),
                ]);

                // ✅ meta (وبنخلي deducted=true عشان ما يخصم مرة ثانية عند الدفع)
                $meta['package_covers_service'] = true;
                $meta['package_subscription_id'] = (int) $sub->id;
                $meta['package_id'] = (int) $sub->package_id;
                $meta['remaining_washes_before'] = $before;
                $meta['remaining_washes_after'] = $after;

                $meta['package_deducted'] = true;
                $meta['package_deducted_at'] = now()->toDateTimeString();
                $meta['package_deducted_by'] = auth()->id();
            }

            $servicePoints = (int) ($service->points ?? 0);

            $booking = Booking::create([
                'user_id' => $user->id,
                'car_id' => $car->id,
                'address_id' => $address->id,
                'service_id' => $service->id,

                'zone_id' => $pricing['zone_id'] ?? null,
                'time_period' => $pricing['time_period'] ?? 'all',

                'service_unit_price_snapshot' => (float) ($pricing['unit_price'] ?? 0),
                'service_discounted_price_snapshot' => $pricing['discounted_price'] !== null ? (float) $pricing['discounted_price'] : null,
                'service_final_price_snapshot' => $finalUnit,
                'service_points_snapshot' => $servicePoints,

                'service_charge_amount_snapshot' => $chargeAmount,

                'service_pricing_source' => $usingPackage ? 'package' : ($pricing['pricing_source'] ?? 'base'),
                'service_pricing_meta' => [
                    'applied_id' => $pricing['applied_id'] ?? null,
                    'lat' => (float) $address->lat,
                    'lng' => (float) $address->lng,
                    'package_subscription_id' => $usingPackage ? $subscriptionId : null,
                ],

                'employee_id' => $pickedEmployeeId,
                'package_subscription_id' => $usingPackage ? $subscriptionId : null,

                'status' => 'confirmed',
                'booking_date' => $dbDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,

                'service_price_snapshot' => (float) $service->price,
                'currency' => 'SAR',

                'meta' => $meta,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Products
            $productsSubtotal = 0.0;
            $productsInput = $data['products'] ?? [];

            foreach ($productsInput as $row) {
                $prod = Product::query()
                    ->whereKey((int) $row['product_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$prod)
                    continue;

                $qty = max(1, (int) $row['qty']);
                $unit = (float) $prod->price;
                $line = $qty * $unit;

                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $prod->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unit,
                    'title' => $prod->name,
                    'line_total' => $line,
                ]);

                $productsSubtotal += $line;
            }

            // ✅ مهم: subtotal/total = ما سيدفعه العميل فعلياً
            $subtotal = (float) $chargeAmount + (float) $productsSubtotal;
            $total = $subtotal; // tax لاحقاً

            $booking->update([
                'products_subtotal_snapshot' => $productsSubtotal,
                'subtotal_snapshot' => $subtotal,
                'total_snapshot' => $total,
            ]);

            // لو total = 0 -> confirmed
            if ($total <= 0.0) {
                $booking->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
            } else {
                $invoiceService->createBookingInvoice(
                    $booking->fresh(['service', 'products']),
                    auth()->id()
                );
            }

            return $booking->fresh(['service', 'products', 'user', 'car', 'address', 'invoices']);
        });

        return response()->json([
            'ok' => true,
            'message' => __('bookings.created_successfully'),
            'redirect' => route('dashboard.bookings.show', $booking->id),
        ]);
    }

    public function updateStatus(
        Request $request,
        Booking $booking,
        BookingCancellationService $cancelService,
        WalletService $walletService,
        InvoiceService $invoiceService
    ) {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'moving', 'arrived', 'completed', 'cancelled'])],
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.status_locked'),
            ], 409);
        }

        return DB::transaction(function () use ($booking, $data, $cancelService, $walletService, $invoiceService) {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->first();

            if (in_array($booking->status, ['completed', 'cancelled'], true)) {
                return response()->json([
                    'ok' => false,
                    'message' => __('bookings.status_locked'),
                ], 409);
            }

            if ($data['status'] === 'cancelled') {
                $reason = $data['cancel_reason'] ?: 'cancelled_by_admin';
                $note = $data['note'] ?? null;

                $cancelService->cancel($booking, $reason, $note, auth()->id(), $walletService, $invoiceService);

                return response()->json([
                    'ok' => true,
                    'message' => __('bookings.cancelled_successfully'),
                    'html' => view('dashboard.bookings._status_control', ['booking' => $booking->refresh()])->render(),
                ]);
            }

            $from = $booking->status;
            $to = $data['status'];

            if ($from === $to) {
                return response()->json([
                    'ok' => true,
                    'message' => __('bookings.status_no_change'),
                    'html' => view('dashboard.bookings._status_control', ['booking' => $booking])->render(),
                ]);
            }

            $update = ['status' => $to, 'updated_by' => auth()->id()];

            if ($to === 'confirmed' && !$booking->confirmed_at) {
                $update['confirmed_at'] = now();
            }

            $booking->update($update);

            $booking->statusLogs()->create([
                'from_status' => $from,
                'to_status' => $to,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $booking->refresh();

            return response()->json([
                'ok' => true,
                'message' => __('bookings.status_updated'),
                'html' => view('dashboard.bookings._status_control', ['booking' => $booking])->render(),
            ]);
        });
    }

    public function show(Booking $booking)
    {
        $this->title = __('bookings.view');
        $this->page_title = $this->title;

        view()->share([
            'title' => $this->page_title,
            'page_title' => $this->page_title,
        ]);

        $booking->loadMissing([
            'user',
            'car',
            'address',
            'service.category',
            'employee',
            'packageSubscription',
            'products.product',
            'statusLogs' => fn($q) => $q->orderByDesc('created_at'),
            'invoices' => fn($q) => $q->orderByDesc('id')->with('payments'),
        ]);

        $latestInvoice = $booking->invoices->first();
        $latestUnpaid = $booking->invoices->firstWhere('status', 'unpaid');

        return view('dashboard.bookings.show', compact('booking', 'latestInvoice', 'latestUnpaid'));
    }

    public function edit(Booking $booking)
    {
        // منع تعديل المحجوزات الملغية/المكتملة (عدّل حسب رغبتك)
        abort_if(in_array($booking->status, ['cancelled', 'completed'], true), 403);

        $booking->load([
            'user:id,name,mobile,is_active',
            'car',
            'address',
            'service',
            'employee',
            'products.product', // BookingProduct -> product
        ]);

        $services = Service::query()
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->get(['id', 'name', 'duration_minutes']);

        return view('dashboard.bookings.edit', compact('booking', 'services'));
    }

    public function update(
        Request $request,
        Booking $booking,
        SlotService $slotService,
        InvoiceService $invoiceService
    ) {

        // منع تعديل المحجوزات الملغية/المكتملة
        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.cannot_edit_this_status'),
            ], 403);
        }

        // (اختياري قوي) منع التعديل إذا في مدفوعات paid
        $hasPaid = Payment::query()
            ->where('payable_type', Booking::class)
            ->where('payable_id', $booking->id)
            ->where('status', 'paid')
            ->exists();

        if ($hasPaid) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.cannot_edit_paid_booking'),
            ], 422);
        }

        $data = validator($request->all(), [
            'user_id' => ['required', 'integer'], // موجود hidden
            'car_id' => ['required', 'integer'],
            'address_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'booking_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'employee_id' => ['nullable', 'integer'],
            'package_subscription_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:255'],

            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required_with:products', 'integer'],
            'products.*.qty' => ['required_with:products', 'integer', 'min:1'],
        ])->validate();

        // المستخدم ثابت للحجز (اعتمادًا على سياسة الداشبورد)
        $user = $booking->user()->where('is_active', true)->firstOrFail();

        $car = $user->cars()->whereKey($data['car_id'])->firstOrFail();
        $address = $user->addresses()->whereKey($data['address_id'])->firstOrFail();

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->firstOrFail();

        $duration = (int) $service->duration_minutes;

        $dbDate = Carbon::createFromFormat('Y-m-d', $data['booking_date'])->toDateString();
        $startTime = $data['start_time'];

        $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
            ->addMinutes($duration)
            ->format('H:i');

        // ✅ Validate slot fresh
        $apiDate = Carbon::createFromFormat('Y-m-d', $dbDate)->format('d-m-Y');

        $slots = $slotService->getSlots(
            $apiDate,
            (int) $service->id,
            (float) $address->lat,
            (float) $address->lng,
            null,
            'blocks'
        );

        if (empty($slots['items'])) {
            $code = $slots['meta']['error_code'] ?? null;
            $msg = __('bookings.no_slots');
            if ($code === 'OUT_OF_COVERAGE')
                $msg = __('bookings.out_of_coverage');
            if ($code === 'NO_WORKING_HOURS')
                $msg = __('bookings.no_working_hours');

            return response()->json([
                'ok' => false,
                'message' => $msg,
                'errors' => ['start_time' => [$msg]],
            ], 422);
        }

        $slot = collect($slots['items'])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
        if (!$slot) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.time_not_available'),
                'errors' => ['start_time' => [__('bookings.time_not_available')]],
            ], 422);
        }

        $employees = $slot['employees'] ?? [];
        if (empty($employees)) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.no_employee_for_slot'),
                'errors' => ['employee_id' => [__('bookings.no_employee_for_slot')]],
            ], 422);
        }

        $pickedEmployeeId = $data['employee_id'] ?? null;
        if ($pickedEmployeeId) {
            $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $pickedEmployeeId);
            if (!$found) {
                return response()->json([
                    'ok' => false,
                    'message' => __('bookings.employee_not_available'),
                    'errors' => ['employee_id' => [__('bookings.employee_not_available')]],
                ], 422);
            }
        } else {
            $pickedEmployeeId = (int) $employees[0]['employee_id'];
        }

        // ✅ pricing resolve
        $pricing = app(\App\Services\BookingPricingService::class)
            ->resolve($service, $user, $address, $startTime);

        $finalUnit = (float) ($pricing['final_unit_price'] ?? 0.0);

        $subscriptionId = !empty($data['package_subscription_id']) ? (int) $data['package_subscription_id'] : null;

        $updated = DB::transaction(function () use ($booking, $data, $user, $car, $address, $service, $dbDate, $startTime, $endTime, $duration, $pricing, $finalUnit, $pickedEmployeeId, $invoiceService, $subscriptionId) {
            /** @var Booking $booking */
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $actorId = auth()->id();

            // ---------- PACKAGE immediate deduct/refund ----------
            $bmeta = (array) ($booking->meta ?? []);

            $oldSubId = $booking->package_subscription_id ? (int) $booking->package_subscription_id : null;
            $oldDeducted = (bool) ($bmeta['package_deducted'] ?? false);
            $oldCovers = (bool) ($bmeta['package_covers_service'] ?? false);

            // لو كان في خصم سابق و المستخدم غيّر/أزال الباقة -> رجّع الغسلة
            if ($oldSubId && $oldCovers && $oldDeducted && (!$subscriptionId || $subscriptionId !== $oldSubId)) {
                $oldSub = PackageSubscription::query()
                    ->whereKey($oldSubId)
                    ->where('user_id', $booking->user_id)
                    ->lockForUpdate()
                    ->with(['package:id,washes_count'])
                    ->first();

                if ($oldSub) {
                    $max = (int) ($oldSub->package?->washes_count ?? 0);
                    $new = (int) $oldSub->remaining_washes + 1;
                    if ($max > 0)
                        $new = min($new, $max);

                    $oldSub->update([
                        'remaining_washes' => $new,
                        'updated_at' => now(),
                    ]);
                }

                $bmeta['package_deducted'] = false;
                $bmeta['package_refunded_at'] = now()->toDateTimeString();
            }

            $usingPackage = false;

            if ($subscriptionId) {
                // هل نحتاج خصم جديد؟
                $needsDeduct = !($oldSubId === $subscriptionId && (bool) ($bmeta['package_deducted'] ?? false));

                // تحقّق + lock
                $subQ = PackageSubscription::query()
                    ->whereKey($subscriptionId)
                    ->where('user_id', $booking->user_id)
                    ->lockForUpdate()
                    ->whereHas('package.services', fn($q) => $q->where('services.id', $service->id))
                    ->with(['package:id,name,washes_count']);

                $sub = $subQ->first();

                if (!$sub) {
                    throw ValidationException::withMessages([
                        'package_subscription_id' => [__('bookings.package.invalid_subscription')],
                    ]);
                }

                if ($needsDeduct) {
                    if ((int) $sub->remaining_washes <= 0) {
                        throw ValidationException::withMessages([
                            'package_subscription_id' => [__('bookings.package.no_remaining_washes')],
                        ]);
                    }

                    $before = (int) $sub->remaining_washes;
                    $after = $before - 1;

                    $sub->update([
                        'remaining_washes' => $after,
                        'updated_at' => now(),
                    ]);

                    $bmeta['remaining_washes_before'] = $before;
                    $bmeta['remaining_washes_after'] = $after;
                    $bmeta['package_deducted'] = true;
                    $bmeta['package_deducted_at'] = now()->toDateTimeString();
                }

                $bmeta['package_covers_service'] = true;
                $bmeta['package_subscription_id'] = (int) $sub->id;
                $bmeta['package_id'] = (int) $sub->package_id;

                $usingPackage = true;
            } else {
                // إزالة الباقة
                $bmeta['package_covers_service'] = false;
                $bmeta['package_subscription_id'] = null;
                $bmeta['package_id'] = null;
            }

            // note
            $bmeta['dashboard_note'] = $data['note'] ?? null;
            $bmeta['base_pricing_source'] = $pricing['pricing_source'] ?? null;
            $bmeta['base_applied_id'] = $pricing['applied_id'] ?? null;

            // payable (سعر الخدمة الذي سيدفعه العميل فعليًا)
            $chargeAmount = $usingPackage ? 0.0 : $finalUnit;

            // تحديث الحجز
            $booking->update([
                'car_id' => $car->id,
                'address_id' => $address->id,
                'service_id' => $service->id,

                'zone_id' => $pricing['zone_id'] ?? null,
                'time_period' => $pricing['time_period'] ?? 'all',

                'service_unit_price_snapshot' => (float) ($pricing['unit_price'] ?? 0),
                'service_discounted_price_snapshot' => $pricing['discounted_price'] !== null ? (float) $pricing['discounted_price'] : null,
                'service_final_price_snapshot' => (float) $finalUnit,

                'service_points_snapshot' => (int) ($service->points ?? 0),

                'service_charge_amount_snapshot' => (float) $chargeAmount,
                'service_pricing_source' => $usingPackage ? 'package' : ($pricing['pricing_source'] ?? 'base'),
                'service_pricing_meta' => [
                    'applied_id' => $pricing['applied_id'] ?? null,
                    'lat' => (float) $address->lat,
                    'lng' => (float) $address->lng,
                    'package_subscription_id' => $usingPackage ? $subscriptionId : null,
                ],

                'employee_id' => $pickedEmployeeId,
                'package_subscription_id' => $usingPackage ? $subscriptionId : null,

                'booking_date' => $dbDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,

                'service_price_snapshot' => (float) $service->price,

                'meta' => $bmeta,
                'updated_by' => $actorId,
            ]);

            // ---------- Products: replace ----------
            BookingProduct::query()->where('booking_id', $booking->id)->delete();

            $productsSubtotal = 0.0;
            $productsInput = $data['products'] ?? [];

            foreach ($productsInput as $row) {
                $prod = Product::query()
                    ->whereKey((int) $row['product_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$prod)
                    continue;

                $qty = max(1, (int) $row['qty']);
                $unit = (float) $prod->price;
                $line = $qty * $unit;

                BookingProduct::create([
                    'booking_id' => $booking->id,
                    'product_id' => $prod->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unit,
                    'title' => $prod->name, // json
                    'line_total' => $line,
                ]);

                $productsSubtotal += $line;
            }

            $subtotal = (float) $chargeAmount + (float) $productsSubtotal;
            $total = $subtotal;

            $booking->update([
                'products_subtotal_snapshot' => $productsSubtotal,
                'subtotal_snapshot' => $subtotal,
                'total_snapshot' => $total,
            ]);

            // ---------- invoices: cancel unpaid + recreate if needed ----------
            Invoice::query()
                ->where('invoiceable_type', Booking::class)
                ->where('invoiceable_id', $booking->id)
                ->where('status', 'unpaid')
                ->update([
                    'status' => 'cancelled',
                    'is_locked' => true,
                    'updated_at' => now(),
                    'updated_by' => $actorId,
                ]);

            if ($total > 0.0) {
                $invoiceService->createBookingInvoice(
                    $booking->fresh(['service', 'products']),
                    $actorId
                );
            } else {
                // لو total=0 و كان pending -> confirmed
                if ($booking->status === 'pending') {
                    $booking->update([
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                        'updated_by' => $actorId,
                    ]);
                }
            }

            return $booking->fresh(['service', 'products', 'user', 'car', 'address', 'invoices']);
        });

        return response()->json([
            'ok' => true,
            'message' => __('bookings.updated_successfully'),
            'redirect' => route('dashboard.bookings.show', $updated->id),
        ]);
    }

    // ================= Delete =================

    public function destroy(
        Booking $booking,
        BookingDeletionService $deletionService,
        BookingCancellationService $cancellationService,
        WalletService $walletService,
        InvoiceService $invoiceService
    ) {

        if (in_array($booking->status, ['completed'], true)) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.cannot_delete_completed'),
            ], 422);
        }

        $actorId = auth()->id();

        $deletionService->deleteLikeCancel(
            booking: $booking,
            reason: 'deleted_by_admin',
            note: 'Deleted from dashboard',
            actorId: $actorId,
            walletService: $walletService,
            invoiceService: $invoiceService,
            cancellationService: $cancellationService
        );

        return response()->json([
            'ok' => true,
            'message' => __('bookings.deleted_successfully'),
            'redirect' => route('dashboard.bookings.index'),
        ]);
    }

    // ================= Helpers =================

    private function i18n($json, string $fallback = '—'): string
    {
        if (!$json)
            return $fallback;
        $locale = app()->getLocale();

        if (is_array($json)) {
            return (string) ($json[$locale] ?? (collect($json)->first() ?? $fallback));
        }

        return (string) $json;
    }

    private function statusBadge(?string $status): string
    {
        $status = $status ?: 'pending';

        $map = [
            'pending' => 'badge-light-warning',
            'confirmed' => 'badge-light-primary',
            'moving' => 'badge-light-info',
            'arrived' => 'badge-light-primary',
            'completed' => 'badge-light-success',
            'cancelled' => 'badge-light-danger',
        ];

        $class = $map[$status] ?? 'badge-light';

        return '<span class="badge ' . e($class) . '">' . e(__('bookings.status.' . $status)) . '</span>';
    }
}