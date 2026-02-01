<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CarouselItemStoreRequest;
use App\Http\Requests\Dashboard\CarouselItemUpdateRequest;
use App\Models\CarouselItem;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CarouselItemController extends Controller
{
    public function __construct()
    {
        // عدّل الصلاحيات حسب نظامك
        $this->middleware('can:carousel_items.view')->only(['index', 'datatable', 'show']);
        $this->middleware('can:carousel_items.create')->only(['create', 'store']);
        $this->middleware('can:carousel_items.edit')->only(['edit', 'update']);
        $this->middleware('can:carousel_items.delete')->only(['destroy']);
    }

    public function index()
    {
        view()->share([
            'title' => __('carousel.title'),
            'page_title' => __('carousel.title'),
        ]);

        return view('dashboard.carousel_items.index');
    }

    public function datatable(DataTables $datatable, Request $request)
    {
        $q = CarouselItem::query()
            ->select([
                'id',
                'title',
                'label',
                'is_active',
                'sort_order',
                'carouselable_type',
                'carouselable_id',
                'created_at'
            ]);

        if ($request->filled('is_active')) {
            $q->where('is_active', (int) $request->input('is_active') === 1);
        }

        return $datatable->eloquent($q)
            ->addColumn('image', function (CarouselItem $item) {
                $url = $item->getImageUrl(app()->getLocale());
                return view('dashboard.carousel_items.partials.image_cell', compact('url'))->render();
            })
            ->addColumn('title_text', function (CarouselItem $item) {
                return function_exists('i18n') ? i18n($item->title) : ($item->title[app()->getLocale()] ?? ($item->title['ar'] ?? ''));
            })
            ->addColumn('status_badge', function (CarouselItem $item) {
                return $item->is_active
                    ? '<span class="badge badge-light-success">' . __('carousel.active') . '</span>'
                    : '<span class="badge badge-light-danger">' . __('carousel.inactive') . '</span>';
            })
            ->addColumn('actions', function (CarouselItem $item) {
                return view('dashboard.carousel_items.partials.actions', compact('item'))->render();
            })
            ->rawColumns(['image', 'status_badge', 'actions'])
            ->toJson();
    }

    public function create()
    {
        view()->share([
            'title' => __('carousel.create'),
            'page_title' => __('carousel.create'),
        ]);

        $carouselableKeys = array_keys(config('carousel.carouselables', []));
        return view('dashboard.carousel_items.create', compact('carouselableKeys'));
    }

    public function store(CarouselItemStoreRequest $request)
    {
        $data = $request->validated();

        [$type, $id] = $this->resolveCarouselable($data['carouselable_key'] ?? null, $data['carouselable_id'] ?? null);

        $item = new CarouselItem();
        $item->label = $data['label'] ?? null;
        $item->title = $data['title'];
        $item->description = $data['description'] ?? null;
        $item->hint = $data['hint'] ?? null;
        $item->cta = $data['cta'] ?? null;

        $item->carouselable_type = $type;
        $item->carouselable_id = $id;

        $item->is_active = (bool) ($data['is_active'] ?? false);
        $item->sort_order = (int) ($data['sort_order'] ?? 0);

        // حقول التاريخ الجديدة
        $item->starts_at = $data['starts_at'] ?? null;
        $item->ends_at = $data['ends_at'] ?? null;
        $item->display_type = $data['display_type'] ?? null;

        $item->save();

        // images
        if ($request->hasFile('image_ar')) {
            $item->addMediaFromRequest('image_ar')->toMediaCollection('image_ar');
        }
        if ($request->hasFile('image_en')) {
            $item->addMediaFromRequest('image_en')->toMediaCollection('image_en');
        }

        return response()->json([
            'ok' => true,
            'message' => __('carousel.created_successfully'),
            'redirect' => route('dashboard.carousel-items.show', $item->id),
        ]);
    }

    public function show(CarouselItem $carouselItem)
    {
        view()->share([
            'title' => __('carousel.show'),
            'page_title' => __('carousel.show'),
        ]);

        $carouselItem->load('carouselable');

        return view('dashboard.carousel_items.show', compact('carouselItem'));
    }

    public function edit(CarouselItem $carouselItem)
    {
        view()->share([
            'title' => __('carousel.edit'),
            'page_title' => __('carousel.edit'),
        ]);

        $carouselableKeys = array_keys(config('carousel.carouselables', []));

        // key reverse mapping
        $currentKey = null;
        foreach (config('carousel.carouselables', []) as $k => $class) {
            if ($carouselItem->carouselable_type === $class) {
                $currentKey = $k;
                break;
            }
        }

        return view('dashboard.carousel_items.edit', compact('carouselItem', 'carouselableKeys', 'currentKey'));
    }

    public function update(CarouselItemUpdateRequest $request, CarouselItem $carouselItem)
    {
        $data = $request->validated();

        [$type, $id] = $this->resolveCarouselable($data['carouselable_key'] ?? null, $data['carouselable_id'] ?? null);

        $carouselItem->label = $data['label'] ?? null;
        $carouselItem->title = $data['title'];
        $carouselItem->description = $data['description'] ?? null;
        $carouselItem->hint = $data['hint'] ?? null;
        $carouselItem->cta = $data['cta'] ?? null;

        $carouselItem->carouselable_type = $type;
        $carouselItem->carouselable_id = $id;

        $carouselItem->is_active = (bool) ($data['is_active'] ?? false);
        $carouselItem->sort_order = (int) ($data['sort_order'] ?? 0);

        // حقول التاريخ الجديدة
        $carouselItem->starts_at = $data['starts_at'] ?? null;
        $carouselItem->ends_at = $data['ends_at'] ?? null;
        $carouselItem->display_type = $data['display_type'] ?? null;

        $carouselItem->save();

        if ($request->hasFile('image_ar')) {
            $carouselItem->clearMediaCollection('image_ar');
            $carouselItem->addMediaFromRequest('image_ar')->toMediaCollection('image_ar');
        }
        if ($request->hasFile('image_en')) {
            $carouselItem->clearMediaCollection('image_en');
            $carouselItem->addMediaFromRequest('image_en')->toMediaCollection('image_en');
        }

        return response()->json([
            'ok' => true,
            'message' => __('carousel.updated_successfully'),
            'redirect' => route('dashboard.carousel-items.show', $carouselItem->id),
        ]);
    }

    public function destroy(CarouselItem $carouselItem)
    {
        $carouselItem->delete();

        return response()->json([
            'ok' => true,
            'message' => __('carousel.deleted_successfully'),
            'redirect' => route('dashboard.carousel-items.index'),
        ]);
    }

    // ---------------------------
    // AJAX lookup for carouselable
    // ---------------------------
    public function carouselablesLookup(Request $request)
    {
        $key = (string) $request->input('key');
        $qText = trim((string) $request->input('q', ''));

        $map = config('carousel.carouselables', []);
        if (!isset($map[$key])) {
            return response()->json(['results' => []]);
        }

        $class = $map[$key];
        $query = $class::query();

        // محاولة عامة للاسم: name أو title
        if ($qText !== '') {
            $query->where(function ($x) use ($qText) {
                $x->where('name', 'like', "%{$qText}%")
                    ->orWhere('title', 'like', "%{$qText}%")
                    ->orWhere('name->ar', 'like', "%{$qText}%")
                    ->orWhere('name->en', 'like', "%{$qText}%");
            });
        }

        $items = $query->orderByDesc('id')->limit(20)->get();

        $results = $items->map(function ($m) {
            $label = '';
            if (isset($m->name)) {
                $label = is_array($m->name) ? (function_exists('i18n') ? i18n($m->name) : ($m->name[app()->getLocale()] ?? ($m->name['ar'] ?? ''))) : (string) $m->name;
            } elseif (isset($m->title)) {
                $label = is_array($m->title) ? (function_exists('i18n') ? i18n($m->title) : ($m->title[app()->getLocale()] ?? ($m->title['ar'] ?? ''))) : (string) $m->title;
            } else {
                $label = class_basename($m) . ': ' . $m->id;
            }

            return ['id' => $m->id, 'text' => $label];
        });

        return response()->json(['results' => $results]);
    }

    private function resolveCarouselable(?string $key, $id): array
    {
        if (!$key || !$id)
            return [null, null];

        $map = config('carousel.carouselables', []);
        if (!isset($map[$key]))
            return [null, null];

        return [$map[$key], (int) $id];
    }
}