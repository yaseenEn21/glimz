<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AppPageController extends Controller
{
    public function __construct()
    {

        view()->share([
            'title' => __('app-pages.title'),
            'page_title' => __('app-pages.title'),
        ]);
        
        // 🔐 Permissions middleware
        $this->middleware('can:app_pages.view')->only(['index', 'show']);
        $this->middleware('can:app_pages.create')->only(['create', 'store']);
        $this->middleware('can:app_pages.edit')->only(['edit', 'update']);
        $this->middleware('can:app_pages.delete')->only(['destroy']);
    }

    protected function pages(): array
    {
        return [
            'about_us' => [
                'name'        => 'من نحن',
                'description' => 'صفحة تعريفية عن Glimz.',
            ],
            'policies_and_terms' => [
                'name'        => 'الشروط والأحكام',
                'description' => 'اتفاقية الالتحاق وسياسة الدوام والدفع.',
            ],
        ];
    }

    protected function findPageOrFail(string $key): array
    {
        $pages = $this->pages();

        if (! isset($pages[$key])) {
            abort(404);
        }

        return $pages[$key] + ['key' => $key];
    }

    public function index()
    {
        $pages = [];

        foreach ($this->pages() as $key => $meta) {
            $setting = Setting::where('key', $key)->first();

            $pages[] = [
                'key'         => $key,
                'name'        => $meta['name'],
                'description' => $meta['description'],
                'updated_at'  => $setting?->updated_at,
                'has_value'   => ! empty($setting?->value),
            ];
        }

        return view('dashboard.app-pages.index', compact('pages'));
    }

    public function edit(string $pageKey)
    {
        $pageMeta = $this->findPageOrFail($pageKey);

        $setting = Setting::firstOrNew(['key' => $pageKey]);

        return view('dashboard.app-pages.edit', [
            'page'    => $pageMeta,
            'setting' => $setting,
        ]);
    }

    public function update(Request $request, string $pageKey)
    {
        $pageMeta = $this->findPageOrFail($pageKey);

        $data = $request->validate(
            [
                'value' => ['required', 'string'],
            ],
            [
                'value.required' => 'محتوى الصفحة مطلوب.',
            ]
        );

        Setting::updateOrCreate(
            ['key' => $pageKey],
            ['value' => $data['value']]
        );

        return redirect()
            ->route('dashboard.app-pages.edit', $pageKey)
            ->with('success', 'تم حفظ محتوى صفحة "' . $pageMeta['name'] . '" بنجاح.');
    }
}