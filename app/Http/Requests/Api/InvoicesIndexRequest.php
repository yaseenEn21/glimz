<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoicesIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['completed', 'not_completed'])],
            'type'   => ['nullable', Rule::in(['invoice','adjustment','credit_note'])],
            'q'      => ['nullable', 'string', 'max:50'], // search by number
        ];
    }
}
