<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CarouselItemResource;
use App\Models\CarouselItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CarouselController extends Controller
{
    public function index(Request $request)
    {
        $locale = app()->getLocale();

        $items = CarouselItem::query()
            ->select([
                'id',
                'label',
                'title',
                'description',
                'hint',
                'cta',
                'carouselable_type',
                'carouselable_id',
                'sort_order',
                'is_active',
                'starts_at',
                'ends_at',
                'display_type',
            ])
            ->where('display_type', 'slider')
            ->active()
            ->with('media')
            ->orderBy('sort_order')
            ->get();

        return api_success(CarouselItemResource::collection($items), 'Home carousel');
    }
}
