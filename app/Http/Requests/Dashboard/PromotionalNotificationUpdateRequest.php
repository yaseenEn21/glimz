<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionalNotificationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $notification = $this->route('promotional_notification');
        
        return $this->user()->can('promotional_notifications.send') 
            && $notification->canBeEdited();
    }

    public function rules(): array
    {
        return [
            // المحتوى
            'title.ar' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'body.ar' => ['required', 'string', 'max:1000'],
            'body.en' => ['required', 'string', 'max:1000'],

            // الجمهور المستهدف
            'target_type' => ['required', Rule::in(['specific_users', 'all_users'])],
            'target_user_ids' => ['required_if:target_type,specific_users', 'array', 'min:1'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],

            // الربط
            'linkable_type' => ['nullable', 'string'],
            'linkable_id' => ['nullable', 'integer'],

            // الجدولة
            'send_type' => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_at' => ['required_if:send_type,scheduled', 'nullable', 'date', 'after:now'],

            // ملاحظات
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => __('promotional_notifications.validation.title_ar_required'),
            'title.en.required' => __('promotional_notifications.validation.title_en_required'),
            'body.ar.required' => __('promotional_notifications.validation.body_ar_required'),
            'body.en.required' => __('promotional_notifications.validation.body_en_required'),
            'target_user_ids.required_if' => __('promotional_notifications.validation.users_required'),
            'target_user_ids.min' => __('promotional_notifications.validation.users_min'),
            'scheduled_at.after' => __('promotional_notifications.validation.scheduled_future'),
        ];
    }

    /**
     * تحديد البيانات المخصصة للتحقق
     */
    protected function prepareForValidation()
    {
        // تحويل linkable_type و linkable_id من combined select
        if ($this->has('linkable_combined') && $this->linkable_combined) {
            $parts = explode(':', $this->linkable_combined);
            if (count($parts) === 2) {
                $this->merge([
                    'linkable_type' => $parts[0],
                    'linkable_id' => (int) $parts[1],
                ]);
            }
        }
    }
}