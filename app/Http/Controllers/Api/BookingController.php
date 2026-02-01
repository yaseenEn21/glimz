<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookingCancelRequest;
use App\Http\Requests\Api\BookingProductsUpdateRequest;
use App\Http\Requests\Api\BookingRescheduleRequest;
use App\Http\Requests\Api\BookingStoreRequest;
use App\Http\Resources\Api\BookingProductResource;
use App\Http\Resources\Api\BookingResource;
use App\Http\Resources\Api\ProductResource;
use App\Jobs\AutoCancelPendingBookingJob;
use App\Models\Address;
use App\Models\Booking;
use App\Models\BookingProduct;
use App\Models\Car;
use App\Models\PackageSubscription;
use App\Models\Product;
use App\Models\Service;
use App\Services\BookingCancellationService;
use App\Services\InvoiceService;
use App\Services\SlotService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Requests\Api\BookingUpdateRequest;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;
use App\Jobs\SyncBookingToRekazJob;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * GET /api/v1/bookings?type=all|upcoming|completed|cancelled
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $v = validator($request->all(), [
            'type' => ['nullable', 'string', Rule::in(['all', 'upcoming', 'completed', 'cancelled'])],
        ]);

        if ($v->fails())
            return api_error($v->errors()->first(), 422);

        $q = Booking::query()
            ->where('user_id', $user->id)
            ->with(['service', 'products.product', 'invoices', 'car']);

        if ($request->filled('type')) {
            $type = $request->input('type');

            switch ($type) {
                case 'upcoming':
                    // الحجوزات القادمة = pending + confirmed + moving + arrived
                    $q->whereIn('status', ['pending', 'confirmed', 'moving', 'arrived']);
                    break;

                case 'completed':
                    // الحجوزات المكتملة فقط
                    $q->where('status', 'completed');
                    break;

                case 'cancelled':
                    // الحجوزات الملغاة فقط
                    $q->where('status', 'cancelled');
                    break;

                case 'all':
                default:
                    // لا نطبق أي فلتر، نعرض الكل
                    break;
            }
        }

        $p = $q->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20);

        $p->setCollection($p->getCollection()->map(fn($b) => new BookingResource($b)));

        return api_paginated($p, 'Bookings');
    }

    public function show(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);
        if ($booking->user_id !== $user->id)
            return api_error('Not found', 404);

        $booking->load(['service', 'products.product', 'invoices', 'car']);

        return api_success(new BookingResource($booking), 'Booking');
    }

    /**
     * POST /api/v1/bookings
     */
    // public function store(
    //     BookingStoreRequest $request,
    //     SlotService $slotService,
    //     InvoiceService $invoiceService
    // ) {
    //     $user = $request->user();
    //     if (!$user)
    //         return api_error('Unauthenticated', 401);

    //     $data = $request->validated();

    //     $car = Car::query()->where('id', $data['car_id'])->where('user_id', $user->id)->first();
    //     if (!$car)
    //         return api_error('Car not found', 404);

    //     $address = Address::query()->where('id', $data['address_id'])->where('user_id', $user->id)->first();
    //     if (!$address)
    //         return api_error('Address not found', 404);

    //     $service = Service::query()->where('id', $data['service_id'])->where('is_active', true)->first();
    //     if (!$service)
    //         return api_error('Service not found', 404);

    //     $day = Carbon::createFromFormat('d-m-Y', $data['date']);
    //     $dbDate = $day->toDateString();

    //     $startTime = $data['time']; // HH:MM
    //     $duration = (int) $service->duration_minutes;

    //     $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
    //         ->addMinutes($duration)
    //         ->format('H:i');

    //     // ✅ تحقق slot متاح + احصل موظفين
    //     $slots = $slotService->getSlots($data['date'], (int) $service->id, (float) $address->lat, (float) $address->lng);

    //     // ✅ لو ما في slots أصلاً: رجّع رسالة حسب السبب
    //     if (empty($slots['items'])) {
    //         $code = $slots['meta']['error_code'] ?? null;

    //         if ($code === 'OUT_OF_COVERAGE') {
    //             return api_error('Address is outside service coverage area', 422);
    //         }

    //         if ($code === 'NO_WORKING_HOURS') {
    //             return api_error('No working hours available for the selected date', 422);
    //         }

    //         return api_error('No slots available for the selected date', 422);
    //     }

    //     // بعدها افحص الوقت المحدد
    //     $slot = collect($slots['items'])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
    //     if (!$slot) {
    //         return api_error('Selected time is not available', 422);
    //     }

    //     $employees = $slot['employees'] ?? [];
    //     if (empty($employees))
    //         return api_error('No employee available for this slot', 422);

    //     $pickedEmployeeId = $data['employee_id'] ?? null;
    //     if ($pickedEmployeeId) {
    //         $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $pickedEmployeeId);
    //         if (!$found)
    //             return api_error('Selected employee is not available in this slot', 422);
    //     } else {
    //         $pickedEmployeeId = (int) $employees[0]['employee_id'];
    //     }

    //     $subscriptionId = $data['package_subscription_id'] ?? null;

    //     // أسعار الخدمة snapshot
    //     $price = (float) $service->price;
    //     $disc = $service->discounted_price !== null ? (float) $service->discounted_price : null;
    //     $final = $disc ?? $price;

    //     // لو عنده باقة تغطي الخدمة → final=0
    //     $meta = [];
    //     if ($subscriptionId) {
    //         $meta['package_covers_service'] = true;
    //         $final = 0;
    //     }

    //     $address = Address::query()
    //         ->where('id', $request->integer('address_id'))
    //         ->where('user_id', $user->id)
    //         ->firstOrFail();

    //     $service = Service::query()
    //         ->where('id', $request->integer('service_id'))
    //         ->where('is_active', true)
    //         ->firstOrFail();

    //     $pricing = app(\App\Services\BookingPricingService::class)
    //         ->resolve($service, $user, $address, $request->input('time'));

    //     // السعر الحقيقي للخدمة
    //     $final = (float) $pricing['final_unit_price'];

    //     // إذا الحجز باستخدام باقة صالحة تغطي الخدمة -> charge = 0
    //     $usingPackage = (bool) $request->filled('package_subscription_id'); // (مع تحققك الكامل)
    //     $chargeAmount = $usingPackage ? 0.0 : $final;

    //     $booking = DB::transaction(function () use ($user, $car, $address, $service, $dbDate, $startTime, $endTime, $duration, $price, $disc, $final, $subscriptionId, $pickedEmployeeId, $data, $meta, $invoiceService, $pricing, $chargeAmount, $usingPackage) {
    //         $booking = Booking::create([
    //             'user_id' => $user->id,
    //             'car_id' => $car->id,
    //             'address_id' => $address->id,
    //             'service_id' => $service->id,

    //             'zone_id' => $pricing['zone_id'],
    //             'time_period' => $pricing['time_period'],

    //             'service_unit_price_snapshot' => $pricing['unit_price'],
    //             'service_discounted_price_snapshot' => $pricing['discounted_price'],
    //             'service_final_price_snapshot' => $final,

    //             'service_charge_amount_snapshot' => $chargeAmount,
    //             'service_pricing_source' => $usingPackage ? 'package' : $pricing['pricing_source'],
    //             'service_pricing_meta' => [
    //                 'applied_id' => $pricing['applied_id'],
    //                 'lat' => (float) $address->lat,
    //                 'lng' => (float) $address->lng,
    //             ],

    //             'employee_id' => $pickedEmployeeId,
    //             'package_subscription_id' => $subscriptionId,

    //             'status' => 'pending',

    //             'booking_date' => $dbDate,
    //             'start_time' => $startTime,
    //             'end_time' => $endTime,
    //             'duration_minutes' => $duration,

    //             'service_price_snapshot' => $price,

    //             'currency' => 'SAR',
    //             'meta' => $meta,

    //             'created_by' => $user->id,
    //             'updated_by' => $user->id,
    //         ]);

    //         // Products
    //         $productsSubtotal = 0;
    //         $productsInput = $data['products'] ?? [];

    //         foreach ($productsInput as $p) {
    //             $prod = Product::query()->where('id', (int) $p['product_id'])->where('is_active', true)->first();
    //             if (!$prod)
    //                 continue;

    //             $qty = (int) $p['qty'];
    //             $unit = (float) $prod->price;
    //             $line = $qty * $unit;

    //             BookingProduct::create([
    //                 'booking_id' => $booking->id,
    //                 'product_id' => $prod->id,
    //                 'qty' => $qty,
    //                 'unit_price_snapshot' => $unit,
    //                 'title' => $prod->name, // JSON
    //                 'line_total' => $line,
    //             ]);

    //             $productsSubtotal += $line;
    //         }

    //         $subtotal = (float) $final + (float) $productsSubtotal;
    //         $total = $subtotal; // tax لاحقاً

    //         $booking->update([
    //             'products_subtotal_snapshot' => $productsSubtotal,
    //             'subtotal_snapshot' => $subtotal,
    //             'total_snapshot' => $total,
    //         ]);

    //         // ✅ إن كانت total = 0 → أكد الحجز مباشرة (باقة بدون منتجات)
    //         if ($total <= 0.0) {
    //             $m = (array) ($booking->meta ?? []);
    //             $m['package_deducted'] = false; // رح يتم خصمها عند التأكيد/fulfillment (اختياري)
    //             $booking->update([
    //                 'status' => 'confirmed',
    //                 'confirmed_at' => now(),
    //                 'meta' => $m,
    //             ]);
    //         } else {
    //             // ✅ اصدار فاتورة
    //             $invoiceService->createBookingInvoice($booking->fresh(['service', 'products']), $user->id);

    //             // ✅ Job إلغاء بعد 10 دقائق لو ما اندفع
    //             AutoCancelPendingBookingJob::dispatch($booking->id)
    //                 ->delay(now()->addMinutes((int) config('booking.pending_auto_cancel_minutes', 10)));
    //         }

    //         return $booking->fresh(['service', 'products.product', 'invoices']);
    //     });

    //     return api_success(new BookingResource($booking), 'Booking created', 201);
    // }

    // public function store(
    //     BookingStoreRequest $request,
    //     SlotService $slotService,
    //     InvoiceService $invoiceService
    // ) {
    //     $user = $request->user();
    //     if (!$user)
    //         return api_error('Unauthenticated', 401);

    //     $data = $request->validated();

    //     $car = Car::query()
    //         ->where('id', (int) $data['car_id'])
    //         ->where('user_id', $user->id)
    //         ->first();
    //     if (!$car)
    //         return api_error('Car not found', 404);

    //     $address = Address::query()
    //         ->where('id', (int) $data['address_id'])
    //         ->where('user_id', $user->id)
    //         ->first();
    //     if (!$address)
    //         return api_error('Address not found', 404);

    //     $subscriptionId = $data['package_subscription_id'] ?? null;
    //     $usingPackage = !empty($subscriptionId);

    //     // -----------------------------
    //     // 1) Resolve service
    //     // -----------------------------
    //     $subscription = null;

    //     if ($usingPackage) {
    //         $subscription = PackageSubscription::query()
    //             ->where('id', (int) $subscriptionId)
    //             ->where('user_id', $user->id)
    //             ->with([
    //                 'package.services' => function ($q) {
    //                     $q->where('services.is_active', true)->orderBy('services.id');
    //                 }
    //             ])
    //             ->first();

    //         if (!$subscription)
    //             return api_error('Package subscription not found', 404);

    //         // صلاحية الاشتراك
    //         if ($subscription->status !== 'active')
    //             return api_error('Package subscription is not active', 422);
    //         if (!$subscription->ends_at || $subscription->ends_at->endOfDay()->lt(now()))
    //             return api_error('Package subscription has expired', 422);
    //         if ((int) $subscription->remaining_washes <= 0)
    //             return api_error('No remaining washes in this subscription', 422);

    //         // أول خدمة من الباقة
    //         $service = $subscription->package?->services?->first();
    //         if (!$service)
    //             return api_error('No active service found in this package', 422);

    //     } else {
    //         $service = Service::query()
    //             ->where('id', (int) $data['service_id'])
    //             ->where('is_active', true)
    //             ->first();
    //         if (!$service)
    //             return api_error('Service not found', 404);
    //     }

    //     // -----------------------------
    //     // 2) Date/time + duration
    //     // -----------------------------
    //     $day = Carbon::createFromFormat('d-m-Y', $data['date']);
    //     $dbDate = $day->toDateString();

    //     $startTime = $data['time']; // HH:MM
    //     $duration = (int) $service->duration_minutes;

    //     $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
    //         ->addMinutes($duration)
    //         ->format('H:i');

    //     // -----------------------------
    //     // 3) Slots validation + pick employee
    //     // -----------------------------
    //     $slots = $slotService->getSlots($data['date'], (int) $service->id, (float) $address->lat, (float) $address->lng);

    //     if (empty($slots['items'])) {
    //         $code = $slots['meta']['error_code'] ?? null;

    //         if ($code === 'OUT_OF_COVERAGE')
    //             return api_error('Address is outside service coverage area', 422);
    //         if ($code === 'NO_WORKING_HOURS')
    //             return api_error('No working hours available for the selected date', 422);

    //         return api_error('No slots available for the selected date', 422);
    //     }

    //     $slot = collect($slots['items'])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
    //     if (!$slot)
    //         return api_error('Selected time is not available', 422);

    //     $employees = $slot['employees'] ?? [];
    //     if (empty($employees))
    //         return api_error('No employee available for this slot', 422);

    //     $pickedEmployeeId = $data['employee_id'] ?? null;
    //     if ($pickedEmployeeId) {
    //         $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $pickedEmployeeId);
    //         if (!$found)
    //             return api_error('Selected employee is not available in this slot', 422);
    //     } else {
    //         $pickedEmployeeId = (int) $employees[0]['employee_id'];
    //     }

    //     // -----------------------------
    //     // 4) Pricing (كما هو)
    //     // -----------------------------
    //     $pricing = app(\App\Services\BookingPricingService::class)
    //         ->resolve($service, $user, $address, $startTime);

    //     $finalUnitPrice = (float) $pricing['final_unit_price'];
    //     $chargeAmount = $usingPackage ? 0.0 : $finalUnitPrice;

    //     // -----------------------------
    //     // 5) Create booking (transaction) + deduct wash immediately
    //     // -----------------------------
    //     $booking = DB::transaction(function () use ($user, $car, $address, $service, $dbDate, $startTime, $endTime, $duration, $pickedEmployeeId, $data, $subscriptionId, $usingPackage, $pricing, $finalUnitPrice, $chargeAmount, $invoiceService) {

    //         $meta = (array) ($data['meta'] ?? []);

    //         // ✅ إذا باستخدام باقة: اقفل الاشتراك وخصم غسلة فوراً
    //         if ($usingPackage) {
    //             /** @var \App\Models\PackageSubscription $sub */
    //             $sub = PackageSubscription::query()
    //                 ->where('id', (int) $subscriptionId)
    //                 ->where('user_id', $user->id)
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (!$sub)
    //                 throw new \Exception('Package subscription not found');
    //             if ($sub->status !== 'active')
    //                 throw new \Exception('Package subscription is not active');
    //             if (!$sub->ends_at || $sub->ends_at->endOfDay()->lt(now()))
    //                 throw new \Exception('Package subscription has expired');
    //             if ((int) $sub->remaining_washes <= 0)
    //                 throw new \Exception('No remaining washes in this subscription');

    //             $before = (int) $sub->remaining_washes;
    //             $after = $before - 1;

    //             $sub->update([
    //                 'remaining_washes' => $after,
    //                 'updated_at' => now(),
    //                 'updated_by' => $user->id,
    //             ]);

    //             $meta['package_covers_service'] = true;
    //             $meta['package_subscription_id'] = (int) $sub->id;
    //             $meta['package_id'] = (int) $sub->package_id;

    //             $meta['remaining_washes_before'] = $before;
    //             $meta['remaining_washes_after'] = $after;

    //             $meta['package_deducted'] = true;
    //             $meta['package_deducted_at'] = now()->toDateTimeString();
    //             $meta['package_deducted_by'] = $user->id;
    //         }

    //         $booking = Booking::create([
    //             'user_id' => $user->id,
    //             'car_id' => $car->id,
    //             'address_id' => $address->id,
    //             'service_id' => $service->id,

    //             'zone_id' => $pricing['zone_id'],
    //             'time_period' => $pricing['time_period'],

    //             'service_unit_price_snapshot' => $pricing['unit_price'],
    //             'service_discounted_price_snapshot' => $pricing['discounted_price'],
    //             'service_final_price_snapshot' => $finalUnitPrice,
    //             'service_points_snapshot' => (int) ($service->points ?? 0),

    //             'service_charge_amount_snapshot' => $chargeAmount,
    //             'service_pricing_source' => $usingPackage ? 'package' : $pricing['pricing_source'],
    //             'service_pricing_meta' => [
    //                 'applied_id' => $pricing['applied_id'],
    //                 'lat' => (float) $address->lat,
    //                 'lng' => (float) $address->lng,
    //             ],

    //             'employee_id' => $pickedEmployeeId,
    //             'package_subscription_id' => $subscriptionId,

    //             'status' => 'pending',

    //             'booking_date' => $dbDate,
    //             'start_time' => $startTime,
    //             'end_time' => $endTime,
    //             'duration_minutes' => $duration,

    //             'service_price_snapshot' => (float) $service->price,
    //             'currency' => 'SAR',
    //             'meta' => $meta,

    //             'created_by' => $user->id,
    //             'updated_by' => $user->id,
    //         ]);

    //         // Products (كما هو)
    //         $productsSubtotal = 0;
    //         $productsInput = $data['products'] ?? [];

    //         foreach ($productsInput as $p) {
    //             $prod = Product::query()->where('id', (int) $p['product_id'])->where('is_active', true)->first();
    //             if (!$prod)
    //                 continue;

    //             $qty = (int) $p['qty'];
    //             $unit = (float) $prod->price;
    //             $line = $qty * $unit;

    //             BookingProduct::create([
    //                 'booking_id' => $booking->id,
    //                 'product_id' => $prod->id,
    //                 'qty' => $qty,
    //                 'unit_price_snapshot' => $unit,
    //                 'title' => $prod->name,
    //                 'line_total' => $line,
    //             ]);

    //             $productsSubtotal += $line;
    //         }

    //         $subtotal = (float) $chargeAmount + (float) $productsSubtotal;
    //         $total = $subtotal;

    //         $booking->update([
    //             'products_subtotal_snapshot' => $productsSubtotal,
    //             'subtotal_snapshot' => $subtotal,
    //             'total_snapshot' => $total,
    //         ]);

    //         // ✅ total = 0 => أكد مباشرة (كما هو)
    //         if ($total <= 0.0) {
    //             $booking->update([
    //                 'status' => 'confirmed',
    //                 'confirmed_at' => now(),
    //             ]);
    //         } else {
    //             // إصدار فاتورة (كما هو)
    //             $invoiceService->createBookingInvoice($booking->fresh(['service', 'products']), $user->id);

    //             AutoCancelPendingBookingJob::dispatch($booking->id)
    //                 ->delay(now()->addMinutes((int) config('booking.pending_auto_cancel_minutes', 10)));
    //         }

    //         return $booking->fresh(['service', 'products.product', 'invoices', 'car']);
    //     });

    //     return api_success(new BookingResource($booking), 'Booking created', 201);
    // }

    public function store(
        BookingStoreRequest $request,
        SlotService $slotService,
        InvoiceService $invoiceService
    ) {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $data = $request->validated();

        $car = Car::query()
            ->where('id', (int) $data['car_id'])
            ->where('user_id', $user->id)
            ->first();
        if (!$car)
            return api_error('Car not found', 404);

        $address = Address::query()
            ->where('id', (int) $data['address_id'])
            ->where('user_id', $user->id)
            ->first();
        if (!$address)
            return api_error('Address not found', 404);

        $subscriptionId = $data['package_subscription_id'] ?? null;
        $usingPackage = !empty($subscriptionId);

        // -----------------------------
        // 1) Resolve service
        // -----------------------------
        $subscription = null;

        if ($usingPackage) {
            $subscription = PackageSubscription::query()
                ->where('id', (int) $subscriptionId)
                ->where('user_id', $user->id)
                ->with([
                    'package.services' => function ($q) {
                        $q->where('services.is_active', true)->orderBy('services.id');
                    }
                ])
                ->first();

            if (!$subscription)
                return api_error('Package subscription not found', 404);

            // صلاحية الاشتراك
            if ($subscription->status !== 'active')
                return api_error('Package subscription is not active', 422);
            if (!$subscription->ends_at || $subscription->ends_at->endOfDay()->lt(now()))
                return api_error('Package subscription has expired', 422);
            if ((int) $subscription->remaining_washes <= 0)
                return api_error('No remaining washes in this subscription', 422);

            // أول خدمة من الباقة
            $service = $subscription->package?->services?->first();
            if (!$service)
                return api_error('No active service found in this package', 422);

        } else {
            $service = Service::query()
                ->where('id', (int) $data['service_id'])
                ->where('is_active', true)
                ->first();
            if (!$service)
                return api_error('Service not found', 404);
        }

        // -----------------------------
        // 2) Date/time + duration
        // -----------------------------
        $day = Carbon::createFromFormat('d-m-Y', $data['date']);
        $dbDate = $day->toDateString();

        $startTime = $data['time']; // HH:MM
        $duration = (int) $service->duration_minutes;

        $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
            ->addMinutes($duration)
            ->format('H:i');

        // -----------------------------
        // 3) Slots validation + pick employee
        // -----------------------------
        $slots = $slotService->getSlots($data['date'], (int) $service->id, (float) $address->lat, (float) $address->lng);

        if (empty($slots['items'])) {
            $code = $slots['meta']['error_code'] ?? null;

            if ($code === 'OUT_OF_COVERAGE')
                return api_error('Address is outside service coverage area', 422);
            if ($code === 'NO_WORKING_HOURS')
                return api_error('No working hours available for the selected date', 422);

            return api_error('No slots available for the selected date', 422);
        }

        $slot = collect($slots['items'])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
        if (!$slot)
            return api_error('Selected time is not available', 422);

        $employees = $slot['employees'] ?? [];
        if (empty($employees))
            return api_error('No employee available for this slot', 422);

        $pickedEmployeeId = $data['employee_id'] ?? null;
        if ($pickedEmployeeId) {
            $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $pickedEmployeeId);
            if (!$found)
                return api_error('Selected employee is not available in this slot', 422);
        } else {
            $pickedEmployeeId = (int) $employees[0]['employee_id'];
        }

        // -----------------------------
        // 4) Pricing
        // -----------------------------
        $pricing = app(\App\Services\BookingPricingService::class)
            ->resolve($service, $user, $address, $startTime);

        $finalUnitPrice = (float) $pricing['final_unit_price'];
        $chargeAmount = $usingPackage ? 0.0 : $finalUnitPrice;

        // -----------------------------
        // 5) Create booking (transaction) + deduct wash immediately
        // -----------------------------
        $booking = DB::transaction(function () use ($user, $car, $address, $service, $dbDate, $startTime, $endTime, $duration, $pickedEmployeeId, $data, $subscriptionId, $usingPackage, $pricing, $finalUnitPrice, $chargeAmount, $invoiceService) {

            $meta = (array) ($data['meta'] ?? []);

            // ✅ إذا باستخدام باقة: اقفل الاشتراك وخصم غسلة فوراً
            if ($usingPackage) {
                /** @var \App\Models\PackageSubscription $sub */
                $sub = PackageSubscription::query()
                    ->where('id', (int) $subscriptionId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$sub)
                    throw new \Exception('Package subscription not found');
                if ($sub->status !== 'active')
                    throw new \Exception('Package subscription is not active');
                if (!$sub->ends_at || $sub->ends_at->endOfDay()->lt(now()))
                    throw new \Exception('Package subscription has expired');
                if ((int) $sub->remaining_washes <= 0)
                    throw new \Exception('No remaining washes in this subscription');

                $before = (int) $sub->remaining_washes;
                $after = $before - 1;

                $sub->update([
                    'remaining_washes' => $after,
                    'updated_at' => now(),
                    'updated_by' => $user->id,
                ]);

                $meta['package_covers_service'] = true;
                $meta['package_subscription_id'] = (int) $sub->id;
                $meta['package_id'] = (int) $sub->package_id;

                $meta['remaining_washes_before'] = $before;
                $meta['remaining_washes_after'] = $after;

                $meta['package_deducted'] = true;
                $meta['package_deducted_at'] = now()->toDateTimeString();
                $meta['package_deducted_by'] = $user->id;
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'car_id' => $car->id,
                'address_id' => $address->id,
                'service_id' => $service->id,

                'zone_id' => $pricing['zone_id'],
                'time_period' => $pricing['time_period'],

                'service_unit_price_snapshot' => $pricing['unit_price'],
                'service_discounted_price_snapshot' => $pricing['discounted_price'],
                'service_final_price_snapshot' => $finalUnitPrice,
                'service_points_snapshot' => (int) ($service->points ?? 0),

                'service_charge_amount_snapshot' => $chargeAmount,
                'service_pricing_source' => $usingPackage ? 'package' : $pricing['pricing_source'],
                'service_pricing_meta' => [
                    'applied_id' => $pricing['applied_id'],
                    'lat' => (float) $address->lat,
                    'lng' => (float) $address->lng,
                ],

                'employee_id' => $pickedEmployeeId,
                'package_subscription_id' => $subscriptionId,

                'status' => 'pending',

                'booking_date' => $dbDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,

                'service_price_snapshot' => (float) $service->price,
                'currency' => 'SAR',
                'meta' => $meta,

                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Products
            $productsSubtotal = 0;
            $productsInput = $data['products'] ?? [];

            foreach ($productsInput as $p) {
                $prod = Product::query()->where('id', (int) $p['product_id'])->where('is_active', true)->first();
                if (!$prod)
                    continue;

                $qty = (int) $p['qty'];
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

            $subtotal = (float) $chargeAmount + (float) $productsSubtotal;
            $total = $subtotal;

            $booking->update([
                'products_subtotal_snapshot' => $productsSubtotal,
                'subtotal_snapshot' => $subtotal,
                'total_snapshot' => $total,
            ]);

            // ✅ total = 0 => أكد مباشرة
            if ($total <= 0.0) {
                $booking->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
            } else {
                // إصدار فاتورة
                $invoiceService->createBookingInvoice($booking->fresh(['service', 'products']), $user->id);

                AutoCancelPendingBookingJob::dispatch($booking->id)
                    ->delay(now()->addMinutes((int) config('booking.pending_auto_cancel_minutes', 10)));
            }

            return $booking->fresh(['service', 'products.product', 'invoices', 'car']);
        });

        // -----------------------------
        // 6) 🚀 Sync to Rekaz (بعد اكتمال الستوور)
        // -----------------------------
        // نرسل Job للمزامنة مع ركاز بشكل asynchronous
        // الـ Observer لن يزامن في created() لأن الحجز ليس له rekaz_booking_id بعد
        // لذلك نحن نزامن هنا مباشرة بعد اكتمال الإنشاء
        // if (config('services.rekaz.sync.enabled', true) && config('services.rekaz.sync.on_create', true)) {
        //     try {
        //         $delay = config('services.rekaz.sync.delay_seconds', 2);
        //         $queue = config('services.rekaz.sync.queue', 'rekaz-sync');

        //         SyncBookingToRekazJob::dispatch($booking, 'create')
        //             ->onQueue($queue)
        //             ->delay(now()->addSeconds($delay));

        //         Log::info('Rekaz sync job dispatched from controller', [
        //             'booking_id' => $booking->id,
        //             'action' => 'create',
        //         ]);
        //     } catch (\Exception $e) {
        //         // لو فشل dispatch الـ job، نسجل الخطأ لكن ما نوقف الـ response
        //         Log::error('Failed to dispatch Rekaz sync job from controller', [
        //             'booking_id' => $booking->id,
        //             'error' => $e->getMessage(),
        //             'trace' => $e->getTraceAsString(),
        //         ]);
        //     }
        // }

        return api_success(new BookingResource($booking), 'Booking created', 201);
    }

    public function edit(int $id)
    {
        $user = request()->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $booking = Booking::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->with(['service', 'products.product', 'car', 'address', 'invoices'])
            ->first();

        if (!$booking)
            return api_error('Booking not found', 404);

        // سياسة التعديل (اقترحها): فقط pending/confirmed + قبل وقت البداية + بدون paid invoices
        $paidExists = $booking->relationLoaded('invoices')
            ? $booking->invoices->where('status', 'paid')->count() > 0
            : false;

        $startDt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $booking->booking_date->format('Y-m-d') . ' ' . substr((string) $booking->start_time, 0, 5)
        );

        $editable = in_array($booking->status, ['pending', 'confirmed'], true)
            && $startDt->gt(now())
            && !$paidExists;

        return api_success([
            'booking' => new BookingResource($booking),
            'editable' => $editable,
            'reasons' => [
                'status' => (string) $booking->status,
                'has_paid_invoice' => $paidExists,
                'starts_in_future' => $startDt->gt(now()),
            ],
        ], 'OK');
    }



    public function update(
        BookingUpdateRequest $request,
        SlotService $slotService,
        InvoiceService $invoiceService,
        int $id
    ) {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $booking = Booking::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->with(['products', 'invoices'])
            ->first();

        if (!$booking)
            return api_error('Booking not found', 404);

        // ✅ Policy: امنع التعديل بعد الدفع أو للحالات المتقدمة
        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            return api_error('Booking cannot be edited in current status', 422);
        }

        $paidExists = $booking->relationLoaded('invoices')
            ? $booking->invoices->where('status', 'paid')->count() > 0
            : Invoice::query()
                ->where('invoiceable_type', Booking::class)
                ->where('invoiceable_id', $booking->id)
                ->where('status', 'paid')
                ->exists();

        if ($paidExists) {
            return api_error('Booking cannot be edited after payment', 422);
        }

        $startDt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $booking->booking_date->format('Y-m-d') . ' ' . substr((string) $booking->start_time, 0, 5)
        );

        if ($startDt->lte(now())) {
            return api_error('Booking cannot be edited after it starts', 422);
        }

        // -----------------------------
        // 1) Resolve new basic fields (fallback to old)
        // -----------------------------
        $newCarId = $request->filled('car_id') ? (int) $request->car_id : (int) $booking->car_id;
        $newAddressId = $request->filled('address_id') ? (int) $request->address_id : (int) $booking->address_id;

        $car = Car::query()->where('id', $newCarId)->where('user_id', $user->id)->first();
        if (!$car)
            return api_error('Car not found', 404);

        $address = Address::query()->where('id', $newAddressId)->where('user_id', $user->id)->first();
        if (!$address)
            return api_error('Address not found', 404);

        // -----------------------------
        // 2) Resolve package/service (mobile rules)
        // -----------------------------
        $newSubscriptionId = $request->has('package_subscription_id')
            ? ($request->input('package_subscription_id') ? (int) $request->input('package_subscription_id') : null)
            : ($booking->package_subscription_id ? (int) $booking->package_subscription_id : null);

        $usingPackage = !empty($newSubscriptionId);
        $subscription = null;

        if ($usingPackage) {
            $subscription = PackageSubscription::query()
                ->where('id', $newSubscriptionId)
                ->where('user_id', $user->id)
                ->with(['package.services' => fn($q) => $q->where('services.is_active', true)->orderBy('services.id')])
                ->first();

            if (!$subscription)
                return api_error('Package subscription not found', 404);
            if ($subscription->status !== 'active')
                return api_error('Package subscription is not active', 422);
            if (!$subscription->ends_at || $subscription->ends_at->endOfDay()->lt(now()))
                return api_error('Package subscription has expired', 422);
            if ((int) $subscription->remaining_washes <= 0)
                return api_error('No remaining washes in this subscription', 422);

            $service = $subscription->package?->services?->first();
            if (!$service)
                return api_error('No active service found in this package', 422);

        } else {
            // إذا لم يرسل service_id نحتفظ بالخدمة الحالية
            $newServiceId = $request->filled('service_id') ? (int) $request->service_id : (int) $booking->service_id;

            $service = Service::query()
                ->where('id', $newServiceId)
                ->where('is_active', true)
                ->first();

            if (!$service)
                return api_error('Service not found', 404);
        }

        // -----------------------------
        // 3) Date/time (fallback to old)
        // -----------------------------
        $apiDate = $request->filled('date')
            ? (string) $request->date
            : Carbon::parse($booking->booking_date)->format('d-m-Y');

        $startTime = $request->filled('time')
            ? (string) $request->time
            : substr((string) $booking->start_time, 0, 5);

        $day = Carbon::createFromFormat('d-m-Y', $apiDate);
        $dbDate = $day->toDateString();

        $duration = (int) $service->duration_minutes;

        $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
            ->addMinutes($duration)
            ->format('H:i');

        // -----------------------------
        // 4) Slots validation (exclude current booking)
        // -----------------------------
        $slots = $slotService->getSlots(
            $apiDate,
            (int) $service->id,
            (float) $address->lat,
            (float) $address->lng,
            null,
            'blocks',
            (int) $booking->id
        );

        if (empty($slots['items'])) {
            $code = $slots['meta']['error_code'] ?? null;
            if ($code === 'OUT_OF_COVERAGE')
                return api_error('Address is outside service coverage area', 422);
            if ($code === 'NO_WORKING_HOURS')
                return api_error('No working hours available for the selected date', 422);
            return api_error('No slots available for the selected date', 422);
        }

        $slot = collect($slots['items'])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
        if (!$slot)
            return api_error('Selected time is not available', 422);

        $employees = $slot['employees'] ?? [];
        if (empty($employees))
            return api_error('No employee available for this slot', 422);

        // الأفضل: لو ما أرسل employee_id حاول احتفظ بالموظف الحالي إذا متاح
        $preferredEmployeeId = $request->filled('employee_id')
            ? (int) $request->employee_id
            : (int) $booking->employee_id;

        $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $preferredEmployeeId);
        $pickedEmployeeId = $found ? (int) $preferredEmployeeId : (int) $employees[0]['employee_id'];

        // -----------------------------
        // 5) Pricing
        // -----------------------------
        $pricing = app(\App\Services\BookingPricingService::class)
            ->resolve($service, $user, $address, $startTime);

        $finalUnitPrice = (float) $pricing['final_unit_price'];
        $chargeAmount = $usingPackage ? 0.0 : $finalUnitPrice;

        // -----------------------------
        // 6) Transaction update + package refund/deduct + products + invoice
        // -----------------------------
        $updated = DB::transaction(function () use ($booking, $request, $user, $car, $address, $service, $dbDate, $apiDate, $startTime, $endTime, $duration, $pickedEmployeeId, $newSubscriptionId, $usingPackage, $pricing, $finalUnitPrice, $chargeAmount, $invoiceService) {
            $b = Booking::query()
                ->where('id', $booking->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $meta = (array) ($b->meta ?? []);

            $oldSubId = $b->package_subscription_id ? (int) $b->package_subscription_id : null;
            $oldUsingPackage = !empty($oldSubId);
            $oldDeducted = (bool) data_get($meta, 'package_deducted', false);

            $newUsingPackage = !empty($newSubscriptionId);

            // ✅ 6.1) Refund old wash if needed (changing/removing package)
            if ($oldUsingPackage && $oldDeducted && (!$newUsingPackage || (int) $newSubscriptionId !== (int) $oldSubId)) {
                $oldSub = PackageSubscription::query()
                    ->where('id', (int) $oldSubId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($oldSub) {
                    $before = (int) $oldSub->remaining_washes;
                    $after = $before + 1;

                    $oldSub->update([
                        'remaining_washes' => $after,
                        'updated_at' => now(),
                        'updated_by' => $user->id,
                    ]);

                    $meta['package_refunded'] = true;
                    $meta['package_refunded_at'] = now()->toDateTimeString();
                    $meta['package_refunded_by'] = $user->id;
                    $meta['package_refunded_to_subscription_id'] = (int) $oldSub->id;

                    // امنع double-refund لاحقاً
                    $meta['package_deducted'] = false;
                }
            }

            // ✅ 6.2) Deduct new wash if needed (new/changed package OR was not deducted)
            if ($newUsingPackage) {
                $needDeduct = (!$oldUsingPackage)
                    || ((int) $newSubscriptionId !== (int) $oldSubId)
                    || (!$oldDeducted);

                if ($needDeduct) {
                    $sub = PackageSubscription::query()
                        ->where('id', (int) $newSubscriptionId)
                        ->where('user_id', $user->id)
                        ->lockForUpdate()
                        ->with(['package.services' => fn($q) => $q->where('services.is_active', true)->orderBy('services.id')])
                        ->first();

                    if (!$sub) {
                        throw ValidationException::withMessages([
                            'package_subscription_id' => ['Package subscription not found'],
                        ]);
                    }

                    $isActive = $sub->status === 'active'
                        && $sub->ends_at
                        && $sub->ends_at->endOfDay()->gte(now());

                    if (!$isActive) {
                        throw ValidationException::withMessages([
                            'package_subscription_id' => ['Package subscription is not active'],
                        ]);
                    }

                    if ((int) $sub->remaining_washes <= 0) {
                        throw ValidationException::withMessages([
                            'package_subscription_id' => ['No remaining washes in this subscription'],
                        ]);
                    }

                    // بما أننا اخترنا الخدمة من أول خدمة في الباقة، فقط تأكيد أمان
                    $firstService = $sub->package?->services?->first();
                    if (!$firstService || (int) $firstService->id !== (int) $service->id) {
                        throw ValidationException::withMessages([
                            'package_subscription_id' => ['Package does not cover this service'],
                        ]);
                    }

                    $before = (int) $sub->remaining_washes;
                    $after = $before - 1;

                    $sub->update([
                        'remaining_washes' => $after,
                        'updated_at' => now(),
                        'updated_by' => $user->id,
                    ]);

                    $meta['package_covers_service'] = true;
                    $meta['package_subscription_id'] = (int) $sub->id;
                    $meta['package_id'] = (int) $sub->package_id;

                    $meta['remaining_washes_before'] = $before;
                    $meta['remaining_washes_after'] = $after;

                    $meta['package_deducted'] = true;
                    $meta['package_deducted_at'] = now()->toDateTimeString();
                    $meta['package_deducted_by'] = $user->id;
                }
            } else {
                // لو ما عاد يستخدم باقة
                $meta['package_covers_service'] = false;
            }

            // ✅ 6.3) Update booking core + pricing snapshots
            $b->update([
                'car_id' => $car->id,
                'address_id' => $address->id,
                'service_id' => $service->id,

                'zone_id' => $pricing['zone_id'] ?? null,
                'time_period' => $pricing['time_period'] ?? 'all',

                'service_unit_price_snapshot' => (float) ($pricing['unit_price'] ?? 0),
                'service_discounted_price_snapshot' => $pricing['discounted_price'] !== null ? (float) $pricing['discounted_price'] : null,
                'service_final_price_snapshot' => $finalUnitPrice,
                'service_points_snapshot' => (int) ($service->points ?? 0),

                'service_charge_amount_snapshot' => $chargeAmount,
                'service_pricing_source' => $newUsingPackage ? 'package' : ($pricing['pricing_source'] ?? 'base'),
                'service_pricing_meta' => [
                    'applied_id' => $pricing['applied_id'] ?? null,
                    'lat' => (float) $address->lat,
                    'lng' => (float) $address->lng,
                    'package_subscription_id' => $newUsingPackage ? (int) $newSubscriptionId : null,
                ],

                'employee_id' => (int) $pickedEmployeeId,
                'package_subscription_id' => $newUsingPackage ? (int) $newSubscriptionId : null,

                'booking_date' => $dbDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,

                'meta' => $meta,
                'updated_by' => $user->id,
            ]);

            // ✅ 6.4) Products update (only if sent)
            if ($request->has('products')) {
                BookingProduct::query()->where('booking_id', $b->id)->delete();

                $productsSubtotal = 0.0;
                $productsInput = $request->input('products') ?? [];

                foreach ($productsInput as $p) {
                    $prod = Product::query()
                        ->where('id', (int) $p['product_id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$prod)
                        continue;

                    $qty = max(1, (int) $p['qty']);
                    $unit = (float) $prod->price;
                    $line = $qty * $unit;

                    BookingProduct::create([
                        'booking_id' => $b->id,
                        'product_id' => $prod->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $unit,
                        'title' => $prod->name,
                        'line_total' => $line,
                    ]);

                    $productsSubtotal += $line;
                }

                $b->update([
                    'products_subtotal_snapshot' => $productsSubtotal,
                ]);
            }

            // احسب subtotal/total اعتماداً على snapshot الحالي للمنتجات
            $productsSubtotalNow = (float) ($b->products_subtotal_snapshot ?? 0);
            $subtotal = (float) $chargeAmount + $productsSubtotalNow;
            $total = $subtotal;

            $b->update([
                'subtotal_snapshot' => $subtotal,
                'total_snapshot' => $total,
            ]);

            // ✅ 6.5) Invoice logic (pending/unpaid only)
            if ($total <= 0.0) {
                // مثل store: confirmed + لا حاجة لفاتورة
                $b->update([
                    'status' => 'confirmed',
                    'confirmed_at' => $b->confirmed_at ?? now(),
                ]);

                // احذف الفاتورة unpaid (اختياري لكن مريح للـ UI)
                $inv = Invoice::query()
                    ->where('invoiceable_type', Booking::class)
                    ->where('invoiceable_id', $b->id)
                    ->where('status', 'unpaid')
                    ->orderByDesc('id')
                    ->first();

                if ($inv) {
                    $inv->items()->delete();
                    $inv->delete();
                }
            } else {
                // لازم يبقى pending (للدفع)
                $b->update([
                    'status' => 'pending',
                    'confirmed_at' => null,
                ]);

                $invoice = $invoiceService->syncBookingUnpaidInvoice($b->fresh(['service', 'products.product']), $user->id);

                if (!$invoice) {
                    $invoiceService->createBookingInvoice($b->fresh(['service', 'products.product']), $user->id);
                }
            }

            return $b->fresh(['service', 'products.product', 'invoices', 'car']);
        });

        return api_success(new BookingResource($updated), 'Booking updated', 200);
    }


    /**
     * PATCH /api/v1/bookings/{booking}/reschedule
     */
    public function reschedule(BookingRescheduleRequest $request, Booking $booking, SlotService $slotService)
    {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);
        if ($booking->user_id !== $user->id)
            return api_error('Not found', 404);

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return api_error('Booking cannot be rescheduled', 409);
        }

        // شرط 100 دقيقة
        $startAt = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
        if (now()->diffInMinutes($startAt, false) < (int) config('booking.reschedule_min_minutes', 100)) {
            return api_error('Reschedule not allowed (too late)', 409);
        }

        $data = $request->validated();

        $address = $booking->address()->first();
        if (!$address)
            return api_error('Address missing', 409);

        $service = $booking->service()->first();
        if (!$service)
            return api_error('Service missing', 409);

        $duration = (int) $booking->duration_minutes;

        $day = Carbon::createFromFormat('d-m-Y', $data['date']);
        $dbDate = $day->toDateString();

        $startTime = $data['time'];
        $endTime = Carbon::createFromFormat('Y-m-d H:i', $dbDate . ' ' . $startTime)
            ->addMinutes($duration)
            ->format('H:i');

        $slots = $slotService->getSlots($data['date'], (int) $service->id, (float) $address->lat, (float) $address->lng);

        $slot = collect($slots['items'] ?? [])->first(fn($s) => ($s['start_time'] ?? null) === $startTime);
        if (!$slot)
            return api_error('Selected time is not available', 422);

        $employees = $slot['employees'] ?? [];
        if (empty($employees))
            return api_error('No employee available for this slot', 422);

        $pickedEmployeeId = $data['employee_id'] ?? (int) $employees[0]['employee_id'];
        $found = collect($employees)->first(fn($e) => (int) $e['employee_id'] === (int) $pickedEmployeeId);
        if (!$found)
            return api_error('Selected employee is not available in this slot', 422);

        $booking->update([
            'booking_date' => $dbDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'employee_id' => $pickedEmployeeId,
            'updated_by' => $user->id,
        ]);

        $booking->load(['service', 'products', 'invoices']);

        return api_success(new BookingResource($booking), 'Booking rescheduled');
    }

    public function productsEdit(Request $request, Booking $booking)
    {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);
        if ($booking->user_id !== $user->id)
            return api_error('Not found', 404);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->get();

        $booking->load(['products.product']); // علاقة booking_products + product

        return api_success([
            'products' => ProductResource::collection($products),
            'selected' => BookingProductResource::collection($booking->products),

            // (اختياري) للمراجعة
            'totals' => [
                'service_charge_amount' => number_format((float) $booking->service_charge_amount_snapshot, 2, '.', ''),
                'products_subtotal' => number_format((float) $booking->products_subtotal_snapshot, 2, '.', ''),
                'total' => number_format((float) $booking->total_snapshot, 2, '.', ''),
            ],
        ], 'Booking products edit data');
    }

    /**
     * PUT /api/v1/bookings/{booking}/products
     */
    public function updateProducts(
        BookingProductsUpdateRequest $request,
        Booking $booking,
        InvoiceService $invoiceService,
        WalletService $walletService
    ) {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);
        if ($booking->user_id !== $user->id)
            return api_error('Not found', 404);

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return api_error('Booking cannot be updated', 409);
        }

        // منع التعديل إذا فيه فاتورة unpaid موجودة (لو confirmed وبعدها طلب زيادة، لازم يدفع أولاً)
        $hasUnpaid = $booking->invoices()
            ->where('status', 'unpaid')
            ->exists();

        if ($booking->status !== 'pending' && $hasUnpaid) {
            return api_error('You have an unpaid invoice for this booking', 409);
        }

        $data = $request->validated();

        $booking = DB::transaction(function () use ($booking, $data, $invoiceService, $walletService, $user) {

            $booking = Booking::query()->where('id', $booking->id)->lockForUpdate()->firstOrFail();

            $oldProducts = (float) $booking->products_subtotal_snapshot;

            // sync booking_products
            $incoming = collect($data['products'])->keyBy('product_id');

            if ($incoming->isEmpty()) {

                BookingProduct::query()
                    ->where('booking_id', $booking->id)
                    ->delete();

                $newProductsSubtotal = 0;

            } else {

                // delete removed
                BookingProduct::query()
                    ->where('booking_id', $booking->id)
                    ->whereNotIn('product_id', $incoming->keys()->all())
                    ->delete();

                $newProductsSubtotal = 0;

                foreach ($incoming as $pid => $row) {
                    $prod = Product::query()->where('id', (int) $pid)->where('is_active', true)->first();
                    if (!$prod)
                        continue;

                    $qty = (int) $row['qty'];
                    $unit = (float) $prod->price;
                    $line = $qty * $unit;

                    BookingProduct::query()->updateOrCreate(
                        ['booking_id' => $booking->id, 'product_id' => (int) $pid],
                        [
                            'qty' => $qty,
                            'unit_price_snapshot' => $unit,
                            'title' => $prod->name,
                            'line_total' => $line,
                        ]
                    );

                    $newProductsSubtotal += $line;
                }

            }

            // totals update
            $serviceFinal = (float) $booking->service_final_price_snapshot;
            $newTotal = $serviceFinal + $newProductsSubtotal;

            $booking->update([
                'products_subtotal_snapshot' => $newProductsSubtotal,
                'subtotal_snapshot' => $newTotal,
                'total_snapshot' => $newTotal,
                'updated_by' => $user->id,
            ]);

            $diff = $newProductsSubtotal - $oldProducts;

            // ✅ لو pending: حدّث آخر فاتورة unpaid بدل ما تعمل فاتورة جديدة
            if ($booking->status === 'pending') {
                $invoiceService->syncBookingUnpaidInvoice($booking->fresh(['service', 'products']), $user->id);
                return $booking->fresh(['service', 'products', 'invoices']);
            }

            // ✅ لو confirmed:
            if (abs($diff) < 0.0001) {
                return $booking->fresh(['service', 'products', 'invoices']);
            }

            if ($diff > 0) {
                // delta invoice
                $deltaItems = [];
                // أبسط دقة: اصدر فاتورة بقيمة diff كبند custom واحد (لكن الأفضل delta per product)
                // هنا رح نعمل delta per product مقارنةً بالقديم بشكل سريع:
                $current = BookingProduct::query()->where('booking_id', $booking->id)->get()->keyBy('product_id');

                // مبدئيًا: اصدر بند واحد custom (سريع وعملي)
                $deltaItems[] = [
                    'product_id' => (int) ($current->first()?->product_id ?? 0),
                    'qty' => 1,
                    'unit_price' => (float) $diff,
                    'title' => ['ar' => 'إضافة منتجات', 'en' => 'Products update'],
                ];

                $invoiceService->createBookingProductsDeltaInvoice($booking, $deltaItems, $user->id);

            } else {
                // refund to wallet
                $refund = abs((float) $diff);
                $walletService->credit(
                    $user,
                    $refund,
                    'refund',
                    ['ar' => 'استرجاع فرق منتجات', 'en' => 'Products refund difference'],
                    $booking,
                    null,
                    $user->id,
                    ['booking_id' => $booking->id]
                );

                $invoiceService->createBookingCreditNoteToWallet($booking, $refund, $user->id, [
                    'reason' => 'products_decrease',
                ]);
            }

            return $booking->fresh(['service', 'products', 'invoices']);
        });

        return api_success(new BookingResource($booking), 'Booking products updated');
    }

    /**
     * POST /api/v1/bookings/{booking}/cancel
     */
    public function cancel(
        BookingCancelRequest $request,
        Booking $booking,
        BookingCancellationService $cancellationService,
        WalletService $walletService,
        InvoiceService $invoiceService
    ) {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);
        if ($booking->user_id !== $user->id)
            return api_error('Not found', 404);

        if ($booking->status === 'cancelled') {
            return api_success(new BookingResource($booking->load(['service', 'products', 'invoices'])), 'Already cancelled');
        }

        // شرط 120 دقيقة
        $startAt = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
        if (now()->diffInMinutes($startAt, false) < (int) config('booking.cancel_min_minutes', 120)) {
            return api_error('Cancel not allowed (too late)', 409);
        }

        $data = $request->validated();

        $booking = $cancellationService->cancel(
            $booking,
            $data['reason'],
            $data['note'] ?? null,
            $user->id,
            $walletService,
            $invoiceService
        );

        $booking->load(['service', 'products.product', 'invoices']);

        return api_success(new BookingResource($booking), 'Booking cancelled');
    }

    /**
     * GET /api/v1/bookings/eligible-packages
     */
    public function eligiblePackages(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return api_error('Unauthenticated', 401);
        }

        $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $serviceId = (int) $request->input('service_id');

        $service = Service::query()
            ->whereKey($serviceId)
            ->where('is_active', true)
            ->first();

        if (!$service) {
            return api_error('Service not found', 404);
        }

        $sub = PackageSubscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->where('remaining_washes', '>', 0)
            ->whereHas('package.services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId)
                    ->where('services.is_active', true);
            })
            ->with(['package:id,name,washes_count'])
            ->orderBy('ends_at')
            ->orderByDesc('remaining_washes')
            ->first();

        if (!$sub) {
            return api_success(null, 'OK');
        }

        $locale = $user->locale ?? app()->getLocale() ?? 'ar';

        $pkgName = data_get($sub->package?->name, $locale)
            ?? data_get($sub->package?->name, 'ar')
            ?? data_get($sub->package?->name, 'en')
            ?? '';

        $item = [
            'id' => (int) $sub->id,
            'package_id' => (int) $sub->package_id,
            'package_name' => $pkgName,
            'remaining_washes' => (int) $sub->remaining_washes,
            'ends_at' => $sub->ends_at?->format('Y-m-d'),
            'text' => trim($pkgName . ' - متبقي ' . (int) $sub->remaining_washes . ' - ينتهي ' . ($sub->ends_at?->format('Y-m-d') ?? '')),
        ];

        return api_success($item, 'OK');
    }

}