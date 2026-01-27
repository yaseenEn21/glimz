<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class BranchController extends Controller
{
    protected string $title;
    protected string $page_title;

    public function __construct()
    {
        $this->middleware('can:branches.view')->only(['index']);
        $this->middleware('can:branches.create')->only(['create', 'store']);
        $this->middleware('can:branches.edit')->only(['edit', 'update']);
        $this->middleware('can:branches.delete')->only(['destroy']);

        $this->title = __('branches.list');
        $this->page_title = __('branches.title');
    }

    public function index(DataTables $datatable, Request $request)
    {
        if ($request->ajax()) {

            $query = Branch::query()
                ->select('branches.*')
                ->orderByDesc('id');

            // 🔍 بحث بالاسم
            if ($search = $request->get('search_custom')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('name->ar', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%");
                });
            }

            return $datatable->eloquent($query)
                ->editColumn('name', function (Branch $row) {
                    $locale = app()->getLocale();
                    return e($row->name[$locale] ?? reset($row->name ?? []));
                })
                ->editColumn(
                    'created_at',
                    fn($row) =>
                    optional($row->created_at)->format('Y-m-d')
                )
                ->addColumn('actions', function (Branch $row) {
                    // return view('dashboard.branches._actions', [
                    //     'branch' => $row
                    // ])->render();
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        view()->share([
            'title' => __('branches.title'),
            'page_title' => __('branches.title'),
        ]);

        return view('dashboard.branches.index');
    }

    public function create()
    {
        view()->share([
            'title' => __('branches.create'),
            'page_title' => __('branches.create'),
        ]);

        return view('dashboard.branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name.ar' => ['required', 'string', 'max:190'],
            'name.en' => ['nullable', 'string', 'max:190'],
        ]);

        $branch = Branch::create([
            'name' => $request->input('name'),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'تم إنشاء الفرع بنجاح',
            'redirect' => route('dashboard.branches.index'),
            'data' => ['id' => $branch->id],
        ]);
    }

    public function edit(Branch $branch)
    {
        view()->share([
            'title' => __('branches.edit'),
            'page_title' => __('branches.edit'),
        ]);

        return view('dashboard.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name.ar' => ['required', 'string', 'max:190'],
            'name.en' => ['nullable', 'string', 'max:190'],
        ]);

        $branch->update([
            'name' => $request->input('name'),
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => __('messages.updated_successfully'),
            'redirect' => route('dashboard.branches.index'),
        ]);
    }

    public function destroy(Request $request, Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'message' => __('messages.delete_success_text'),
        ]);
    }
}