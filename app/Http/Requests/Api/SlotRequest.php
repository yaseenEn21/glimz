<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public
    }

    public function rules(): array
    {
        $tz = config('app.timezone', 'UTC');
        $today = now($tz)->format('d-m-Y');

        return [
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date_format:d-m-Y', "after_or_equal:$today"],

            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],

            'mode' => ['nullable', Rule::in(['rolling', 'blocks'])],
            'step_minutes' => ['nullable', 'integer', 'min:5', 'max:90'],
        ];
    }

}