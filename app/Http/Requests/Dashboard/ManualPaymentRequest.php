<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو تحقق من الصلاحيات
    }

    public function rules(): array
    {
        return [
            'external_payment_method_id' => [
                'required',
                'integer',
                Rule::exists('external_payment_methods', 'id')->where('is_active', true),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // 5MB
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'external_payment_method_id.required' => __('invoices.manual_payment.validation.method_required'),
            'external_payment_method_id.exists' => __('invoices.manual_payment.validation.method_invalid'),
            'payment_attachment.max' => __('invoices.manual_payment.validation.file_too_large'),
        ];
    }
}