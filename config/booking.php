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
            'color' => '#FFF8DD',      
            'line_color' => '#FFA800',  
        ],
        'confirmed' => [
            'label_key' => 'bookings.status.confirmed',
            'badge_class' => 'badge-light-primary',
            'color' => '#E1F0FF',      
            'line_color' => '#009EF7',  
        ],
        'moving' => [
            'label_key' => 'bookings.status.moving',
            'badge_class' => 'badge-light-info',
            'color' => '#F1E6FF',      
            'line_color' => '#7239EA',  
        ],
        'arrived' => [
            'label_key' => 'bookings.status.arrived',
            'badge_class' => 'badge-light-info',
            'color' => '#D6F5F5',      
            'line_color' => '#00A3A1',  
        ],
        'completed' => [
            'label_key' => 'bookings.status.completed',
            'badge_class' => 'badge-light-success',
            'color' => '#E8FFF3',      
            'line_color' => '#50CD89',  
        ],
        'cancelled' => [
            'label_key' => 'bookings.status.cancelled',
            'badge_class' => 'badge-light-danger',
            'color' => '#FFE2E5',      
            'line_color' => '#F1416C',  
        ],
    ],
];