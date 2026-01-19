<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BookingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_id' => ['sometimes', 'integer', 'exists:cars,id'],
            'address_id' => ['sometimes', 'integer', 'exists:addresses,id'],

            // لو بده يحوّل لباقة ما بلزم service_id
            'service_id' => ['sometimes', 'nullable', 'integer', 'required_without:package_subscription_id'],
            'package_subscription_id' => ['sometimes', 'nullable', 'integer', 'exists:package_subscriptions,id'],

            'date' => ['sometimes', 'date_format:d-m-Y'],
            'time' => ['sometimes', 'regex:/^\d{2}:\d{2}$/'],

            'employee_id' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],

            'products' => ['sometimes', 'nullable', 'array'],
            'products.*.product_id' => ['required_with:products', 'integer', 'exists:products,id'],
            'products.*.qty' => ['required_with:products', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date_format' => 'صيغة التاريخ يجب أن تكون d-m-Y.',
            'time.regex' => 'صيغة الوقت يجب أن تكون HH:MM.',
        ];
    }
}