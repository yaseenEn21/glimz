<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
// use App\Http\Requests\Dashboard\CustomerStoreRequest;
use App\Http\Requests\Dashboard\CustomerUpdateRequest;
use App\Models\Address;
use App\Models\Booking;
use App\Models\CustomerGroup;
use App\Models\Invoice;
use App\Models\PackageSubscription;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\PointTransaction;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:customers.view')->only([
            'index',
            'datatable',
            'show',
            'bookingsDatatable',
            'invoicesDatatable',
            'paymentsDatatable',
            'walletTransactionsDatatable',
            'pointTransactionsDatatable',
            'packageSubscriptionsDatatable',
            'carsDatatable',
            'addressesDatatable'
        ]);

        $this->middleware('can:customers.create')->only(['create', 'store']);
        $this->middleware('can:customers.edit')->only(['edit', 'update']);
        $this->middleware('can:customers.delete')->only(['destroy']);

        view()->share([
            'title' => __('customers.title'),
            'page_title' => __('customers.title'),
        ]);
    }

    private function assertCustomer(User $user): void
    {
        if ($user->user_type !== 'customer')
            abort(404);
    }

    public function index()
    {
        return view('dashboard.customers.index');
    }

    public function datatable(DataTables $datatable, Request $request)
    {
        $q = User::query()
            ->where('user_type', 'customer')
            ->with(['customerGroup:id,name'])
            ->withCount(['cars', 'addresses'])
            ->select('users.*');

        // ✅ فلتر الحالة
        if ($request->filled('is_active')) {
            $q->where('is_active', (int) $request->input('is_active') === 1);
        }

        // ✅ فلتر بحث نصي (اسم/جوال/ID)
        if ($request->filled('search_custom')) {
            $term = trim((string) $request->input('search_custom'));

            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%");

                if (is_numeric($term)) {
                    $qq->orWhere('id', (int) $term);
                }
            });
        }

        if ($request->filled('customer_group_id')) {
            $q->where('customer_group_id', (int) $request->input('customer_group_id'));
        }

        return $datatable->eloquent($q)
            ->addColumn('group', function (User $u) {
                return $u->customerGroup?->name ?? '—';
            })
            ->addColumn('cars_count', fn(User $u) => (int) $u->cars_count)
            ->addColumn('addresses_count', fn(User $u) => (int) $u->addresses_count)
            ->addColumn('status_badge', function (User $u) {
                return $u->is_active
                    ? '<span class="badge badge-light-success">' . e(__('customers.active')) . '</span>'
                    : '<span class="badge badge-light-danger">' . e(__('customers.inactive')) . '</span>';
            })
            ->addColumn('actions', function (User $u) {
                return view('dashboard.customers._actions', ['row' => $u])->render();
            })
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create()
    {
        $groups = CustomerGroup::query()->where('is_active', 1)->orderBy('id', 'desc')->get();
        return view('dashboard.customers.create', compact('groups'));
    }

    public function store(Request $request)
    {
        // $data = $request->validated();

        // $customer = DB::transaction(function () use ($data, $request) {
        //     $u = User::create([
        //         'name' => $data['name'],
        //         'user_type' => 'customer',
        //         'mobile' => $data['mobile'],
        //         'email' => $data['email'] ?? null,
        //         'password' => Hash::make($data['password']),
        //         'is_active' => $data['is_active'] ?? true,
        //         'notification' => $data['notification'] ?? true,
        //         'birth_date' => $data['birth_date'] ?? null,
        //         'gender' => $data['gender'] ?? 'male',
        //         'customer_group_id' => $data['customer_group_id'] ?? null,
        //         'created_by' => auth()->id(),
        //         'updated_by' => auth()->id(),
        //     ]);

        //     if ($request->hasFile('profile_image')) {
        //         $u->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        //     }

        //     return $u;
        // });

        // return redirect()
        //     ->route('dashboard.customers.show', $customer->id)
        //     ->with('success', __('customers.created_successfully'));
    }

    public function show(User $customer)
    {
        $this->assertCustomer($customer);

        $customer->loadMissing([
            'customerGroup:id,name',
            'wallet:id,user_id,balance,currency,is_active',
            'pointWallet:id,user_id,balance_points,total_earned_points,total_spent_points',
        ])->loadCount(['cars', 'addresses', 'packageSubscriptions']);

        // quick stats
        $bookingStats = Booking::query()
            ->where('user_id', $customer->id)
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status IN ('pending','confirmed','moving','arrived') THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status IN ('completed') THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status IN ('cancelled') THEN 1 ELSE 0 END) as cancelled")
            ->first();

        $activeSub = PackageSubscription::query()
            ->where('user_id', $customer->id)
            ->where('status', 'active')
            ->orderByDesc('ends_at')
            ->with('package:id,name')
            ->first();

        return view('dashboard.customers.show', compact('customer', 'bookingStats', 'activeSub'));
    }

    public function edit(User $customer)
    {
        $this->assertCustomer($customer);

        $groups = CustomerGroup::query()->where('is_active', 1)->orderBy('id', 'desc')->get();
        return view('dashboard.customers.edit', compact('customer', 'groups'));
    }

    public function update(CustomerUpdateRequest $request, User $customer)
    {
        $this->assertCustomer($customer);

        $data = $request->validated();

        DB::transaction(function () use ($customer, $data, $request) {
            $customer->update([
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,

                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? 'male',

                'customer_group_id' => $data['customer_group_id'] ?? null,
                'is_active' => $data['is_active'] ?? $customer->is_active,
                'notification' => $data['notification'] ?? $customer->notification,

                'updated_by' => auth()->id(),
            ]);

            if (!empty($data['password'])) {
                $customer->update([
                    'password' => Hash::make($data['password']),
                ]);
            }

            if ($request->hasFile('profile_image')) {
                $customer->clearMediaCollection('profile_image');
                $customer->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
            }
        });

        return redirect()
            ->route('dashboard.customers.show', $customer->id)
            ->with('success', __('customers.updated_successfully'));
    }

    public function destroy(User $customer)
    {
        $this->assertCustomer($customer);

        // Soft delete + deactivate (بدون لمس الحجوزات)
        DB::transaction(function () use ($customer) {
            $customer->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);
            $customer->delete();
        });

        return response()->json([
            'ok' => true,
            'message' => __('customers.deleted_successfully'),
            'redirect' => route('dashboard.customers.index'),
        ]);
    }

    // ==========================
    // Tabs Datatables
    // ==========================

    public function bookingsDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = Booking::query()
            ->where('user_id', $customer->id)
            ->with([
                    'service:id,name',
                    'employee:id,user_id',
                    'employee.user:id,name,mobile',
                ])
            ->select('bookings.*');

        return $datatable->eloquent($q)
            ->addColumn('service', fn(Booking $b) => function_exists('i18n') ? i18n($b->service?->name) : (data_get($b->service?->name, 'ar') ?? '—'))
            ->addColumn('employee', fn(Booking $b) => $b->employee?->user?->name ?? '—')
            ->addColumn('status_badge', function (Booking $b) {
                $map = [
                    'pending' => 'badge-light-warning',
                    'confirmed' => 'badge-light-primary',
                    'moving' => 'badge-light-info',
                    'arrived' => 'badge-light-info',
                    'completed' => 'badge-light-success',
                    'cancelled' => 'badge-light-danger',
                ];
                $cls = $map[$b->status] ?? 'badge-light';
                return '<span class="badge ' . $cls . '">' . e(__('bookings.status.' . $b->status)) . '</span>';
            })
            ->addColumn('total', fn(Booking $b) => number_format((float) ($b->total_snapshot ?? 0), 2))
            ->addColumn('actions', function (Booking $b) {
                return '<a class="btn btn-sm btn-light" href="' . route('dashboard.bookings.show', $b->id) . '">' . e(__('customers.view')) . '</a>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function invoicesDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = Invoice::query()
            ->where('invoiceable_type', Booking::class)
            ->whereIn('invoiceable_id', function ($sub) use ($customer) {
                $sub->from('bookings')->select('id')->where('user_id', $customer->id);
            })
            ->select('invoices.*');

        return $datatable->eloquent($q)
            ->addColumn('status_badge', function (Invoice $inv) {
                $map = [
                    'unpaid' => 'badge-light-warning',
                    'paid' => 'badge-light-success',
                    'cancelled' => 'badge-light-danger',
                    'refunded' => 'badge-light-info',
                ];
                $cls = $map[$inv->status] ?? 'badge-light';
                return '<span class="badge ' . $cls . '">' . e(__('invoices.status.' . $inv->status)) . '</span>';
            })
            ->addColumn('amount', fn(Invoice $inv) => number_format((float) ($inv->total ?? $inv->amount ?? 0), 2))
            ->addColumn('actions', function (Invoice $inv) {
                // عدّل route حسب نظامك للفواتير
                return '<a class="btn btn-sm btn-light" href="#">' . e(__('customers.view')) . '</a>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function paymentsDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = Payment::query()
            ->where('payable_type', Booking::class)
            ->whereIn('payable_id', function ($sub) use ($customer) {
                $sub->from('bookings')->select('id')->where('user_id', $customer->id);
            })
            ->select('payments.*');

        return $datatable->eloquent($q)
            ->addColumn('status_badge', function (Payment $p) {
                $map = [
                    'paid' => 'badge-light-success',
                    'unpaid' => 'badge-light-warning',
                    'refunded' => 'badge-light-info',
                    'failed' => 'badge-light-danger',
                ];
                $cls = $map[$p->status] ?? 'badge-light';
                return '<span class="badge ' . $cls . '">' . e(__('payments.statuses.' . $p->status)) . '</span>';
            })
            ->addColumn('amount', fn(Payment $p) => number_format((float) ($p->amount ?? 0), 2))
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function walletTransactionsDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = WalletTransaction::query()
            ->where('user_id', $customer->id)
            ->select('wallet_transactions.*');

        return $datatable->eloquent($q)->toJson();
    }

    public function pointTransactionsDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = PointTransaction::query()
            ->where('user_id', $customer->id)
            ->select('point_transactions.*');

        return $datatable->eloquent($q)->toJson();
    }

    public function packageSubscriptionsDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = PackageSubscription::query()
            ->where('user_id', $customer->id)
            ->with('package:id,name')
            ->select('package_subscriptions.*');

        return $datatable->eloquent($q)
            ->addColumn('package', fn(PackageSubscription $s) => function_exists('i18n') ? i18n($s->package?->name) : (data_get($s->package?->name, 'ar') ?? '—'))
            ->toJson();
    }

    public function carsDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = Car::query()->where('user_id', $customer->id)->select('cars.*');
        return $datatable->eloquent($q)->toJson();
    }

    public function addressesDatatable(DataTables $datatable, User $customer)
    {
        $this->assertCustomer($customer);

        $q = Address::query()->where('user_id', $customer->id)->select('addresses.*');
        return $datatable->eloquent($q)->toJson();
    }
}