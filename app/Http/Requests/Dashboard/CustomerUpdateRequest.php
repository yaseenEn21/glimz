<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.edit') ?? false;
    }

    public function rules(): array
    {
        $id = (int)($this->route('customer')?->id);

        return [
            'name' => ['required','string','max:255'],
            'mobile' => ['required','string','max:30', Rule::unique('users','mobile')->ignore($id)],
            'email' => ['nullable','email','max:255', Rule::unique('users','email')->ignore($id)],

            'password' => ['nullable','string','min:6','max:255'],

            'birth_date' => ['nullable','date'],
            'gender' => ['required', Rule::in(['male','female'])],

            'customer_group_id' => ['nullable','integer','exists:customer_groups,id'],

            'is_active' => ['nullable','boolean'],
            'notification' => ['nullable','boolean'],

            'profile_image' => ['nullable','image','max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'notification' => $this->boolean('notification'),
        ]);
    }
}