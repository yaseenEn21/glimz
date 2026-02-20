<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ServiceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:service_categories.view')->only(['index', 'show']);
        $this->middleware('can:service_categories.create')->only(['create', 'store']);
        $this->middleware('can:service_categories.edit')->only(['edit', 'update']);
        $this->middleware('can:service_categories.delete')->only(['destroy']);
    }

    public function index(DataTables $datatable, Request $request)
    {
        if ($request->ajax()) {
            $query = ServiceCategory::query()
                ->select('service_categories.*')
                ->orderBy('sort_order');

            if ($search = $request->get('search_custom')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('name->ar', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%");
                });
            }

            if ($status = $request->get('status')) {
                $query->where('is_active', $status === 'active');
            }

            return $datatable->eloquent($query)
                ->addIndexColumn()
                ->editColumn('name', function (ServiceCategory $row) {
                    $locale = app()->getLocale();
                    return e($row->name[$locale] ?? reset($row->name ?? []) ?? '');
                })
                ->addColumn('services_count', fn(ServiceCategory $row) => $row->services()->count())
                ->addColumn('is_active_badge', function (ServiceCategory $row) {
                    if ($row->is_active) {
                        return '<span class="badge badge-light-success">' . __('service_categories.active') . '</span>';
                    }
                    return '<span class="badge badge-light-danger">' . __('service_categories.inactive') . '</span>';
                })
                ->editColumn('created_at', fn(ServiceCategory $row) => optional($row->created_at)->format('Y-m-d'))
                ->addColumn(
                    'actions',
                    fn(ServiceCategory $row) =>
                    view('dashboard.service-categories._actions', ['category' => $row])->render()
                )
                ->rawColumns(['is_active_badge', 'actions'])
                ->make(true);
        }

        view()->share([
            'title' => __('service_categories.title'),
            'page_title' => __('service_categories.title'),
        ]);

        return view('dashboard.service-categories.index');
    }

    public function create()
    {
        view()->share([
            'title' => __('service_categories.create_new'),
            'page_title' => __('service_categories.create_new'),
        ]);

        return view('dashboard.service-categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name.ar' => ['required', 'string', 'max:190'],
            'name.en' => ['nullable', 'string', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $desiredPosition = $data['sort_order'] ?? null;

        if ($desiredPosition) {
            ServiceCategory::where('sort_order', '>=', $desiredPosition)->increment('sort_order');
        } else {
            $desiredPosition = (ServiceCategory::max('sort_order') ?? 0) + 1;
        }

        $category = ServiceCategory::create([
            'name' => $request->input('name', []),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $desiredPosition,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message' => __('service_categories.created_successfully'),
                'redirect' => route('dashboard.service-categories.index'),
                'data' => ['id' => $category->id],
            ]);
        }

        return redirect()
            ->route('dashboard.service-categories.index')
            ->with('success', __('service_categories.created_successfully'));
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        view()->share([
            'title' => __('service_categories.edit'),
            'page_title' => __('service_categories.edit'),
        ]);

        return view('dashboard.service-categories.edit', compact('serviceCategory'));
    }

    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $data = $request->validate([
            'name.ar' => ['required', 'string', 'max:190'],
            'name.en' => ['nullable', 'string', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $oldPosition = $serviceCategory->sort_order ?? (ServiceCategory::max('sort_order') + 1);
        $newPosition = $data['sort_order'] ?? $oldPosition;

        if ($newPosition != $oldPosition) {
            if ($newPosition < $oldPosition) {
                ServiceCategory::where('id', '!=', $serviceCategory->id)
                    ->whereNotNull('sort_order')
                    ->whereBetween('sort_order', [$newPosition, $oldPosition - 1])
                    ->increment('sort_order');
            } else {
                ServiceCategory::where('id', '!=', $serviceCategory->id)
                    ->whereNotNull('sort_order')
                    ->whereBetween('sort_order', [$oldPosition + 1, $newPosition])
                    ->decrement('sort_order');
            }
        }

        $serviceCategory->update([
            'name' => $request->input('name', []),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $newPosition,
            'updated_by' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'message' => __('service_categories.updated_successfully'),
                'redirect' => route('dashboard.service-categories.index'),
                'data' => ['id' => $serviceCategory->id],
            ]);
        }

        return redirect()
            ->route('dashboard.service-categories.index')
            ->with('success', __('service_categories.updated_successfully'));
    }

    public function show(ServiceCategory $serviceCategory)
    {
        view()->share([
            'title' => __('service_categories.show'),
            'page_title' => __('service_categories.show'),
        ]);

        $servicesCount = $serviceCategory->services()->count();
        $activeCount = $serviceCategory->services()->where('is_active', true)->count();

        return view('dashboard.service-categories.show', compact(
            'serviceCategory',
            'servicesCount',
            'activeCount'
        ));
    }

    public function servicesDatatable(ServiceCategory $serviceCategory, DataTables $datatable, Request $request)
    {
        $query = Service::query()
            ->where('service_category_id', $serviceCategory->id)
            ->select('services.*')
            ->orderBy('sort_order');

        return $datatable->eloquent($query)
            ->addIndexColumn()
            ->editColumn('name', function (Service $row) {
                $locale = app()->getLocale();
                return e($row->name[$locale] ?? reset($row->name ?? []) ?? '');
            })
            ->editColumn('price', fn(Service $row) => number_format((float) $row->price, 2) . ' SAR')
            ->editColumn('discounted_price', function (Service $row) {
                return $row->discounted_price !== null
                    ? number_format((float) $row->discounted_price, 2) . ' SAR'
                    : '—';
            })
            ->editColumn('duration_minutes', fn(Service $row) => $row->duration_minutes . ' ' . __('services.minutes_suffix'))
            ->addColumn('is_active_badge', function (Service $row) {
                if ($row->is_active) {
                    return '<span class="badge badge-light-success">' . __('services.active') . '</span>';
                }
                return '<span class="badge badge-light-danger">' . __('services.inactive') . '</span>';
            })
            ->addColumn('actions', function (Service $row) {
                return '
                <a href="' . route('dashboard.services.show', $row->id) . '" class="btn btn-sm btn-icon btn-light-info" title="عرض">
                    <i class="fa-solid fa-eye fs-5"></i>
                </a>
                <a href="' . route('dashboard.services.edit', $row->id) . '" class="btn btn-sm btn-icon btn-light-warning ms-1" title="تعديل">
                    <i class="fa-solid fa-pen fs-5"></i>
                </a>
            ';
            })
            ->rawColumns(['is_active_badge', 'actions'])
            ->make(true);
    }

    public function destroy(Request $request, ServiceCategory $serviceCategory)
    {
        // منع حذف التصنيف إذا فيه خدمات مرتبطة فيه
        if ($serviceCategory->services()->exists()) {
            $message = __('service_categories.cannot_delete_has_services');
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $serviceCategory->delete();

        if ($request->ajax()) {
            return response()->json(['message' => __('service_categories.deleted_successfully')]);
        }

        return redirect()
            ->route('dashboard.service-categories.index')
            ->with('success', __('service_categories.deleted_successfully'));
    }
}