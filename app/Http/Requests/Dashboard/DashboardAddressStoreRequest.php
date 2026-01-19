<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardAddressStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['home', 'work', 'other'])],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:150'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'building_name' => ['nullable', 'string', 'max:150'],
            'building_number' => ['nullable', 'string', 'max:50'],
            'landmark' => ['nullable', 'string', 'max:1000'],
            'address_link' => ['required', 'string', 'max:2000', 'regex:/^https?:\/\//i'],
            'is_default' => ['nullable', 'boolean'],
        ];

    }
}