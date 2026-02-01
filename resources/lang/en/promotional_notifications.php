<?php

return [
    'title' => 'Promotional Notifications',
    'list' => 'Promotional Notifications List',
    'create' => 'Create Promotional Notification',
    'edit' => 'Edit Promotional Notification',
    'show' => 'Notification Details',

    'fields' => [
        'title' => 'Title',
        'title_ar' => 'Title (Arabic)',
        'title_en' => 'Title (English)',
        'body' => 'Body',
        'body_ar' => 'Body (Arabic)',
        'body_en' => 'Body (English)',
        'target_type' => 'Target Audience',
        'target_users' => 'Target Users',
        'linkable' => 'Link To',
        'status' => 'Status',
        'scheduled_at' => 'Scheduled At',
        'sent_at' => 'Sent At',
        'total_recipients' => 'Total Recipients',
        'successful_sends' => 'Successful Sends',
        'failed_sends' => 'Failed Sends',
        'success_rate' => 'Success Rate',
        'internal_notes' => 'Internal Notes',
        'created_by' => 'Created By',
        'created_at' => 'Created At',
    ],

    'target_types' => [
        'all_users' => 'All Users',
        'specific_users' => 'Specific Users',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sending' => 'Sending',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],

    'link_types' => [
        'none' => 'No Link',
        'service' => 'Service',
        'package' => 'Package',
        'product' => 'Product',
    ],

    'send_types' => [
        'now' => 'Send Now',
        'scheduled' => 'Schedule Send',
    ],

    'actions' => [
        'send_now' => 'Send Now',
        'schedule' => 'Schedule',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'view' => 'View',
        'title' => 'Actions'
    ],

    'messages' => [
        'sent_successfully' => 'Notification sent successfully',
        'scheduled_successfully' => 'Notification scheduled successfully',
        'updated_successfully' => 'Notification updated successfully',
        'deleted_successfully' => 'Notification deleted successfully',
        'cancelled_successfully' => 'Notification cancelled successfully',
        'send_failed' => 'Failed to send notification',
        'cannot_edit' => 'Cannot edit this notification',
        'cannot_delete' => 'Cannot delete this notification',
        'cannot_send' => 'Cannot send this notification',
        'cannot_cancel' => 'Cannot cancel this notification',
        'confirm_delete' => 'Are you sure you want to delete this notification?',
        'confirm_send' => 'Are you sure you want to send this notification?',
        'confirm_cancel' => 'Are you sure you want to cancel this notification?',
    ],

    'validation' => [
        'title_ar_required' => 'Title (Arabic) is required',
        'title_en_required' => 'Title (English) is required',
        'body_ar_required' => 'Body (Arabic) is required',
        'body_en_required' => 'Body (English) is required',
        'users_required' => 'At least one user must be selected',
        'users_min' => 'At least one user must be selected',
        'scheduled_future' => 'Scheduled time must be in the future',
    ],

    'hints' => [
        'title' => 'Notification title that will appear to users',
        'body' => 'Notification body that will appear to users',
        'target_type' => 'Choose who will receive this notification',
        'target_users' => 'Search and select specific users',
        'linkable' => 'Choose item that will open when notification is tapped (optional)',
        'scheduled_at' => 'Set date and time for automatic sending',
        'internal_notes' => 'Internal notes for team (won\'t be visible to users)',
    ],

    'placeholders' => [
        'title_ar' => 'Enter notification title in Arabic',
        'title_en' => 'Enter notification title in English',
        'body_ar' => 'Enter notification body in Arabic',
        'body_en' => 'Enter notification body in English',
        'search_users' => 'Search for users...',
        'select_linkable' => 'Select item to link',
        'scheduled_at' => 'YYYY-MM-DD HH:MM',
        'internal_notes' => 'Enter internal notes...',
    ],

    'stats' => [
        'total_notifications' => 'Total Notifications',
        'sent_notifications' => 'Sent Notifications',
        'scheduled_notifications' => 'Scheduled Notifications',
        'draft_notifications' => 'Drafts',
        'recipients_preview' => 'Expected Recipients Count',
    ],

    'empty_state' => [
        'title' => 'No promotional notifications',
        'description' => 'Create your first promotional notification to send to users',
        'create_button' => 'Create New Notification',
    ],

    'back' => 'Back',
    'content' => 'Content',
    'schedule' => 'Schedule',
    'send_time' => 'Send Time',
    'all_statuses' => 'All Statuses',
    'all_targets' => 'All Targets',
    'users' => 'Users',
    'search' => 'Search',
    'showing' => 'Showing',
    'to' => 'To',
    'of' => 'Of',
    'processing' => 'Processing',

    'lang' => ['ar' => 'Arabic', 'en' => 'English'],
    'scheduled_for' => 'Scheduled For',
    'sent_at_date' => 'Sent At',
    'link_type' => 'Link Type',
    'statistics' => 'Statistics',
    'target_audience' => 'Target Audience',
    'selected_users_count' => 'Selected Users Count',
    'timeline' => 'Timeline',
    'created' => 'Created',
    'by' => 'By',
    'scheduled' => 'Scheduled',
    'sent' => 'Sent',
    'metadata' => 'Metadata',
    'id' => 'ID',
    'updated_at' => 'Last Updated',
    'scheduled_successfully' => 'Scheduled successfully',
    'cancelled_successfully' => 'Cancelled successfully',

    'search_placeholder' => 'Search by title (AR/EN)...',
    'delete_warning' => 'You won\'t be able to revert this action',
    'cancel' => 'Cancel',
    'deleting' => 'Deleting',

];