<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Service;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    /**
 * Display a listing of partners
 */
public function index(Request $request)
{
    Gate::authorize('partners.view');

    // إذا كان طلب DataTables
    if ($request->ajax()) {
        $partners = Partner::query()
            ->withCount(['services', 'employees'])
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest();

        return datatables()->of($partners)
            ->addColumn('name', function ($partner) {
                $initial = strtoupper(substr($partner->name, 0, 2));
                $mobile = $partner->mobile ? '<span class="text-muted fs-7">' . $partner->mobile . '</span>' : '';
                
                return '
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                            <div class="symbol-label bg-light-primary">
                                <span class="text-primary fs-4 fw-bold">' . $initial . '</span>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="' . route('dashboard.partners.show', $partner->id) . '" class="text-gray-800 text-hover-primary mb-1 fw-bold">
                                ' . $partner->name . '
                            </a>
                            ' . $mobile . '
                        </div>
                    </div>
                ';
            })
            ->addColumn('username', function ($partner) {
                return '<span class="badge badge-light-info">' . $partner->username . '</span>';
            })
            ->addColumn('mobile', function ($partner) {
                return $partner->mobile ?? '—';
            })
            ->addColumn('daily_booking_limit', function ($partner) {
                return '<span class="badge badge-light-primary">' . number_format($partner->daily_booking_limit) . '</span>';
            })
            ->addColumn('services_count', function ($partner) {
                return '<span class="badge badge-light-success">' . $partner->services_count . '</span>';
            })
            ->addColumn('is_active_badge', function ($partner) {
                if ($partner->is_active) {
                    return '<span class="badge badge-light-success">' . __('partners.active') . '</span>';
                }
                return '<span class="badge badge-light-danger">' . __('partners.inactive') . '</span>';
            })
            ->editColumn('created_at', function ($partner) {
                return $partner->created_at->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($partner) {
                return view('dashboard.partners._actions', compact('partner'))->render();
            })
            ->rawColumns(['name', 'username', 'daily_booking_limit', 'services_count', 'is_active_badge', 'actions'])
            ->make(true);
    }

    return view('dashboard.partners.index');
}

    /**
     * Show the form for creating a new partner
     */
    public function create()
    {
        Gate::authorize('partners.create');

        return view('dashboard.partners.create');
    }

    /**
     * Store a newly created partner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:255|unique:partners,username',
            'email' => 'required|email|max:255|unique:partners,email',
            'mobile' => 'nullable|string|max:20',
            'webhook_url' => 'nullable|url|max:500',
            'daily_booking_limit' => 'required|integer|min:1|max:10000',
            'webhook_type' => 'nullable|in:generic,mismar',
            // 'is_active' => 'boolean',
        ], [
            'username.alpha_dash' => __('partners.username_english_only'),
            'username.unique' => __('partners.username_taken'),
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');
        $partner = Partner::create($validated);

        return redirect()
            ->route('dashboard.partners.show', $partner)
            ->with('success', __('partners.created_successfully'));
    }

    /**
     * Display the specified partner
     */
    public function show(Partner $partner)
    {
        Gate::authorize('partners.view');

        $partner->load([
            'serviceEmployeeAssignments.service',
            'serviceEmployeeAssignments.employee.user',
        ]);

        // تجميع البيانات: خدمة → موظفيها
        $servicesWithEmployees = $partner->serviceEmployeeAssignments
            ->groupBy('service_id')
            ->map(function ($assignments) {
                return [
                    'service' => $assignments->first()->service,
                    'employees' => $assignments->pluck('employee')->unique('id'),
                ];
            });

        return view('dashboard.partners.show', compact('partner', 'servicesWithEmployees'));
    }

    /**
     * Show the form for editing the partner
     */
    public function edit(Partner $partner)
    {
        Gate::authorize('partners.edit');

        return view('dashboard.partners.edit', compact('partner'));
    }

    /**
     * Update the specified partner
     */
    public function update(Request $request, Partner $partner)
    {
        Gate::authorize('partners.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:255|unique:partners,username,' . $partner->id,
            'email' => 'required|email|max:255|unique:partners,email,' . $partner->id,
            'mobile' => 'nullable|string|max:20',
            'webhook_url' => 'nullable|url|max:500',
            'daily_booking_limit' => 'required|integer|min:1|max:10000',
            // 'is_active' => 'boolean',
        ], [
            'username.alpha_dash' => __('partners.username_english_only'),
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');

        $partner->update($validated);

        return redirect()
            ->route('dashboard.partners.show', $partner)
            ->with('success', __('partners.updated_successfully'));
    }

    /**
     * Remove the specified partner
     */
    public function destroy(Partner $partner)
    {
        Gate::authorize('partners.delete');

        $partner->delete();

        return redirect()
            ->route('dashboard.partners.index')
            ->with('success', __('partners.deleted_successfully'));
    }

    /**
     * Regenerate API Token
     */
    public function regenerateToken(Partner $partner)
    {
        Gate::authorize('partners.regenerate_token');

        $newToken = $partner->regenerateToken();

        return response()->json([
            'success' => true,
            'token' => $newToken,
            'message' => __('partners.token_regenerated'),
        ]);
    }

    /**
     * Show assign services page
     */
    public function assignServices(Partner $partner)
    {
        Gate::authorize('partners.assign_services');

        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $employees = Employee::where('is_active', true)
            ->with('user')
            ->get();

        // الخدمات والموظفين المخصصين حالياً
        $currentAssignments = $partner->serviceEmployeeAssignments()
            ->with(['service', 'employee.user'])
            ->get()
            ->groupBy('service_id');

        return view('dashboard.partners.assign-services', compact(
            'partner',
            'services',
            'employees',
            'currentAssignments'
        ));
    }

    /**
     * Store service-employee assignments
     */
    public function storeAssignments(Request $request, Partner $partner)
    {
        Gate::authorize('partners.assign_services');

        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.service_id' => 'required|exists:services,id',
            'assignments.*.employee_ids' => 'required|array|min:1',
            'assignments.*.employee_ids.*' => 'required|exists:employees,id',
        ]);

        DB::transaction(function () use ($partner, $validated) {
            // حذف التخصيصات القديمة
            $partner->serviceEmployeeAssignments()->delete();

            // إضافة التخصيصات الجديدة
            foreach ($validated['assignments'] as $assignment) {
                $serviceId = $assignment['service_id'];
                
                foreach ($assignment['employee_ids'] as $employeeId) {
                    $partner->serviceEmployeeAssignments()->create([
                        'service_id' => $serviceId,
                        'employee_id' => $employeeId,
                    ]);
                }
            }
        });

        return redirect()
            ->route('dashboard.partners.show', $partner)
            ->with('success', __('partners.assignments_updated'));
    }
}