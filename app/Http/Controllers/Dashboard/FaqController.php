<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

class FaqController extends Controller
{
    /**
     * Display a listing of FAQs
     */
    public function index()
    {
        Gate::authorize('faqs.view');

        return view('dashboard.faqs.index');
    }

    /**
     * DataTable
     */
    public function datatable(DataTables $datatable, Request $request)
    {
        Gate::authorize('faqs.view');

        $query = FAQ::query()
            ->select('faqs.*')
            ->orderBy('sort_order')
            ->orderBy('id');

        // Search
        if ($search = trim((string) $request->get('search_custom'))) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('faqs.id', (int) $search);
                }

                $q->orWhere('question->ar', 'like', "%{$search}%")
                    ->orWhere('question->en', 'like', "%{$search}%")
                    ->orWhere('answer->ar', 'like', "%{$search}%")
                    ->orWhere('answer->en', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return DataTables::of($query)
            ->addColumn('question_label', function (FAQ $row) {
                $ar = is_array($row->question) ? ($row->question['ar'] ?? '') : '';
                $en = is_array($row->question) ? ($row->question['en'] ?? '') : '';
                
                return '<div class="fw-bold">' . e($ar) . '</div>' .
                       '<div class="text-muted fs-7">' . e($en) . '</div>';
            })
            ->addColumn('status_badge', function (FAQ $row) {
                if ($row->is_active) {
                    return '<span class="badge badge-light-success">' . __('faqs.active') . '</span>';
                }
                return '<span class="badge badge-light-danger">' . __('faqs.inactive') . '</span>';
            })
            ->addColumn('actions', function (FAQ $row) {
                return view('dashboard.faqs._actions', ['faq' => $row])->render();
            })
            ->rawColumns(['question_label', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new FAQ
     */
    public function create()
    {
        Gate::authorize('faqs.create');

        return view('dashboard.faqs.create');
    }

    /**
     * Store a newly created FAQ
     */
    public function store(Request $request)
    {
        Gate::authorize('faqs.create');

        $validated = $request->validate([
            'question_ar' => 'required|string|max:500',
            'question_en' => 'required|string|max:500',
            'answer_ar' => 'nullable|string',
            'answer_en' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        FAQ::create([
            'question' => [
                'ar' => $validated['question_ar'],
                'en' => $validated['question_en'],
            ],
            'answer' => [
                'ar' => $validated['answer_ar'] ?? '',
                'en' => $validated['answer_en'] ?? '',
            ],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('dashboard.faqs.index')
            ->with('success', __('faqs.created_successfully'));
    }

    /**
     * Display the specified FAQ
     */
    public function show(FAQ $faq)
    {
        Gate::authorize('faqs.view');

        return view('dashboard.faqs.show', compact('faq'));
    }

    /**
     * Show the form for editing the specified FAQ
     */
    public function edit(FAQ $faq)
    {
        Gate::authorize('faqs.edit');

        return view('dashboard.faqs.edit', compact('faq'));
    }

    /**
     * Update the specified FAQ
     */
    public function update(Request $request, FAQ $faq)
    {
        Gate::authorize('faqs.edit');

        $validated = $request->validate([
            'question_ar' => 'required|string|max:500',
            'question_en' => 'required|string|max:500',
            'answer_ar' => 'nullable|string',
            'answer_en' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq->update([
            'question' => [
                'ar' => $validated['question_ar'],
                'en' => $validated['question_en'],
            ],
            'answer' => [
                'ar' => $validated['answer_ar'] ?? '',
                'en' => $validated['answer_en'] ?? '',
            ],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('dashboard.faqs.index')
            ->with('success', __('faqs.updated_successfully'));
    }

    /**
     * Remove the specified FAQ
     */
    public function destroy(FAQ $faq)
    {
        Gate::authorize('faqs.delete');

        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => __('faqs.deleted_successfully'),
        ]);
    }
}