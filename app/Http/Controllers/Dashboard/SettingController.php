<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:settings.view')->only(['index']);
        $this->middleware('can:settings.edit')->only(['edit', 'update']);
    }

    public function index(DataTables $datatable, Request $request)
    {
        if ($request->ajax()) {
            $query = Setting::query()->select('settings.*');

            if ($search = $request->get('search_custom')) {
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%");
                });
            }

            return $datatable->eloquent($query)
                ->addIndexColumn()
                ->editColumn('label', fn(Setting $row) => e($row->label ?? '—'))
                ->editColumn('value', function (Setting $row) {
                    $val = (string) ($row->value ?? '');
                    if (strlen($val) > 60) {
                        return '<span class="text-truncate d-inline-block" style="max-width:300px" title="'
                            . e($val) . '">' . e(substr($val, 0, 60)) . '…</span>';
                    }
                    return e($val ?: '—');
                })
                ->editColumn(
                    'type',
                    fn(Setting $row) => $row->type
                    ? '<span class="badge badge-light-info">' . e($row->type) . '</span>'
                    : '—'
                )
                ->editColumn('updated_at', fn(Setting $row) => optional($row->updated_at)->format('Y-m-d H:i'))
                ->addColumn('actions', function (Setting $row) {
                    return '
                        <button type="button"
                            class="btn btn-sm btn-icon btn-light-warning js-edit-setting"
                            data-id="' . $row->id . '"
                            title="' . __('messages.actions-btn.edit') . '">
                            <i class="fa-solid fa-pen fs-5"></i>
                        </button>';
                })
                ->rawColumns(['value', 'type', 'actions'])
                ->make(true);
        }

        // جلب الـ types المتاحة من الكونفيج عشان نعرضها في المودال
        $types = collect(config('settings', []))
            ->pluck('type')
            ->unique()
            ->filter()
            ->values();

        view()->share([
            'title' => __('settings.title'),
            'page_title' => __('settings.title'),
        ]);

        return view('dashboard.settings.index', compact('types'));
    }

    public function edit(Setting $setting)
    {
        return response()->json([
            'ok' => true,
            'data' => $setting,
        ]);
    }

    public function update(Request $request, Setting $setting)
    {
        // ✅ validation ديناميكي حسب النوع
        $valueRule = match ($setting->type) {
            'integer' => ['nullable', 'integer'],
            'boolean' => ['nullable', 'in:true,false,1,0'],
            'json' => ['nullable', 'json'],
            default => ['nullable', 'string', 'max:5000'],
        };

        $request->validate([
            'value' => $valueRule,
        ], [
            'value.integer' => 'القيمة يجب أن تكون رقماً صحيحاً.',
            'value.in' => 'القيمة يجب أن تكون: true أو false.',
            'value.json' => 'القيمة يجب أن تكون JSON صحيح.',
        ]);

        $setting->update([
            'value' => $request->input('value'),
        ]);

        if (str_starts_with($setting->key, 'bookings.')) {
            Cache::forget('app.booking_config');
        }

        return response()->json([
            'ok' => true,
            'message' => __('settings.updated_successfully'),
        ]);
    }
}