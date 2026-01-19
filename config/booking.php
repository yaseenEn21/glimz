<?php

return [

    'reschedule_limit_minutes' => 100,
    'cancel_limit_minutes' => 120,
    'pending_auto_cancel_minutes' => 10,
    'slot_step_minutes' => 90,

    'default_status' => 'pending',

    'status_meta' => [
        'pending' => [
            'label_key' => 'bookings.status.pending',
            'badge_class' => 'badge-light-warning',
            'color' => '#fff6e6',
            'line_color' => '#b57600',
        ],
        'confirmed' => [
            'label_key' => 'bookings.status.confirmed',
            'badge_class' => 'badge-light-primary',
            'color' => '#3e98ff71',
            'line_color' => '#3E97FF',
        ],
        'moving' => [
            'label_key' => 'bookings.status.moving',
            'badge_class' => 'badge-light-info',
            'color' => '#7139ea5d',
            'line_color' => '#7239EA',
        ],
        'arrived' => [
            'label_key' => 'bookings.status.arrived',
            'badge_class' => 'badge-light-info',
            'color' => '#e6f4ff',
            'line_color' => '#0063b5',
        ],
        'completed' => [
            'label_key' => 'bookings.status.completed',
            'badge_class' => 'badge-light-success',
            'color' => '#e9ffe6',
            'line_color' => '#50CD89',
        ],
        'cancelled' => [
            'label_key' => 'bookings.status.cancelled',
            'badge_class' => 'badge-light-danger',
            'color' => '#ffe6e6',
            'line_color' => '#ff0000',
        ],
    ],
];