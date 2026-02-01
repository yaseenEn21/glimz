<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarouselItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو تحقق من الصلاحيات
    }

    public function rules(): array
    {
        return [

            'display_type' => ['required', Rule::in(['slider', 'popup', 'both'])],

            'label.ar' => ['nullable', 'string', 'max:120'],
            'label.en' => ['nullable', 'string', 'max:120'],

            'title.ar' => ['required', 'string', 'max:190'],
            'title.en' => ['required', 'string', 'max:190'],

            'description.ar' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],

            'hint.ar' => ['nullable', 'string', 'max:255'],
            'hint.en' => ['nullable', 'string', 'max:255'],

            'cta.ar' => ['nullable', 'string', 'max:255'],
            'cta.en' => ['nullable', 'string', 'max:255'],

            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // حقول التاريخ
            'starts_at' => ['nullable', 'date', 'after_or_equal:today'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            // polymorphic (اختياري)
            'carouselable_key' => ['nullable', Rule::in(array_keys(config('carousel.carouselables', [])))],
            'carouselable_id' => ['nullable', 'integer'],

            // images
            'image_ar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_en' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.after_or_equal' => __('carousel.validation.starts_at_future'),
            'ends_at.after_or_equal' => __('carousel.validation.ends_at_after_start'),
        ];
    }
}