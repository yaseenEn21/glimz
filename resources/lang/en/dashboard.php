<?php

return [

    'title' => 'Dashboard',

    'kpi' => [
        'title' => 'KPI Dashboard',
        'subtitle' => 'Default is the current month — you can change the date range',

        'actions' => [
            'refresh' => 'Refresh',
        ],

        'cards' => [
            'total_bookings' => 'Total bookings',
            'active_bookings' => 'Active bookings',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'cancel_rate' => 'Cancel rate %',
            'package_bookings' => 'Package bookings',
            'gross' => 'Total sales',
            'paid' => 'Paid',
            'unpaid' => 'Unpaid',
            'avg_ticket' => 'Avg. ticket',
        ],

        'sections' => [
            'status_distribution' => 'Status distribution',
            'trend_daily' => 'Bookings & payments (daily)',
            'top_bikers' => 'Top employees',
            'top_services' => 'Top services',
        ],

        'charts' => [
            'series' => [
                'bookings' => 'Bookings',
                'paid' => 'Paid',
            ],
            'bars' => [
                'bookings_count' => 'Bookings count',
            ],
        ],
    ],

];