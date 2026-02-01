<?php

namespace App\Observers;

use App\Models\CarouselItem;
use Illuminate\Support\Facades\Cache;

class CarouselItemObserver
{
    private function clearCarouselCache(): void
    {
        Cache::forget('carousel.slider.active');
        Cache::forget('carousel.popup.active');
        Cache::put('carousel.last_updated', now());
    }

    public function created(CarouselItem $item): void
    {
        $this->clearCarouselCache();
    }

    public function updated(CarouselItem $item): void
    {
        $this->clearCarouselCache();
    }

    public function deleted(CarouselItem $item): void
    {
        $this->clearCarouselCache();
    }
}