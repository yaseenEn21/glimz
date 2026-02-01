<?php

return [
    // Page Titles
    'partners' => 'Partners',
    'partner' => 'Partner',
    'list' => 'Partners List',
    'create' => 'Create Partner',
    'edit' => 'Edit Partner',
    'show' => 'Partner Details',
    'assign_services' => 'Assign Services & Employees',

    // Fields
    'fields' => [
        'name' => 'Name',
        'username' => 'Username',
        'email' => 'Email',
        'mobile' => 'Mobile',
        'webhook_url' => 'Webhook URL',
        'daily_booking_limit' => 'Daily Booking Limit',
        'api_token' => 'API Token',
        'is_active' => 'Active',
        'status' => 'Status',
        'created_at' => 'Created At',
        'services_count' => 'Services Count',
        'employees_count' => 'Employees Count',
    ],

    // Actions
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'assign_services' => 'Assign Services',
        'copy_token' => 'Copy Token',
        'regenerate_token' => 'Regenerate Token',
        'show_token' => 'Show Token',
        'hide_token' => 'Hide Token',
    ],

    // Status
    'active' => 'Active',
    'inactive' => 'Inactive',

    // Messages
    'created_successfully' => 'Partner created successfully',
    'updated_successfully' => 'Partner updated successfully',
    'deleted_successfully' => 'Partner deleted successfully',
    'token_regenerated' => 'Token regenerated successfully',
    'token_copied' => 'Token copied',
    'assignments_updated' => 'Assignments updated successfully',

    // Validations
    'username_english_only' => 'Username must contain only English letters, numbers, dashes, and underscores',
    'username_taken' => 'Username already taken',

    // Descriptions
    'username_help' => 'English only, no spaces (e.g., msmar-services)',
    'webhook_url_help' => 'Updates will be sent to this URL',
    'daily_booking_limit_help' => 'Number of bookings allowed per day for this partner',
    'api_token_help' => 'Use this token in API requests',

    // Tables
    'no_partners' => 'No partners found',
    'total' => 'Total',

    // Assignments
    'assigned_services' => 'Assigned Services',
    'no_services_assigned' => 'No services assigned yet',
    'service' => 'Service',
    'employees' => 'Employees',
    'select_service' => 'Select Service',
    'select_employees' => 'Select Employees',
    'add_service' => 'Add Service',
    'remove_service' => 'Remove',
    'at_least_one_employee' => 'At least one employee must be selected',

    // Confirm
    'delete_confirm' => 'Are you sure you want to delete this partner?',
    'regenerate_token_confirm' => 'Are you sure you want to regenerate the token? The old token will be invalidated.',
];