<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'birth_date' => $this->birth_date?->format('d-m-Y'),
            'gender' => $this->gender,
            'lang' => $this->lang,
            'profile_image_url' => $this->getFirstMediaUrl('profile_image') ?: defaultImage('user.jpg'),
            'is_active' => $this->is_active,
            'user_type' => $this->user_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
