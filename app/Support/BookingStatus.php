<?php

namespace App\Support;

class BookingStatus
{
    public static function meta(?string $status): array
    {
        $status = $status ?: config('booking.default_status', 'pending');

        $map = (array) config('booking.status_meta', []);
        $default = config('booking.default_status', 'pending');

        $m = $map[$status] ?? $map[$default] ?? [];

        $labelKey = $m['label_key'] ?? ('booking.status.' . $status);

        return [
            'value'      => (string) $status,
            'label'      => (string) __($labelKey),
            'badge_class'=> (string) ($m['badge_class'] ?? 'badge-light'),
            'color'      => (string) ($m['color'] ?? '#6c757d'),
            'line_color' => (string) ($m['line_color'] ?? ($m['color'] ?? '#6c757d')),
        ];
    }

    public static function badge(?string $status): string
    {
        $m = self::meta($status);
        return '<span class="badge ' . e($m['badge_class']) . '">' . e($m['label']) . '</span>';
    }
}