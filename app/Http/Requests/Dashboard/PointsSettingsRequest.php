<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class PointsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'redeem_points' => ['required', 'integer', 'min:1'],
            'redeem_amount' => ['required', 'numeric', 'min:0.01'],
            'min_redeem_points' => ['required', 'integer', 'min:1', 'lte:redeem_points'],
        ];
    }

    public function attributes(): array
    {
        return [
            'redeem_points' => __('points_settings.redeem_points.label'),
            'redeem_amount' => __('points_settings.redeem_amount.label'),
            'min_redeem_points' => __('points_settings.min_redeem_points.label'),
        ];
    }
}