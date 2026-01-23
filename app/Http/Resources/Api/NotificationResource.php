<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $icon_path = $this->icon_path !== null
            ? asset('assets/media/icons/duotune/notifications/' . $this->icon_path)
            : defaultImage('notification.png');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $icon_path,
            'data' => $this->data ?: (object) [],
            'is_read' => (bool) $this->is_read,
            'is_new' => (bool) $this->is_new,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}