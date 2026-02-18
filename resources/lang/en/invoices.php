<?php

return [
    'title' => 'Invoices',
    'list' => 'Invoices List',
    'invoice' => 'Invoice',
    'view' => 'View',
    'print' => 'Print',
    'back_to_list' => 'Back to list',
    'actions_title' => 'Actions',
    'export_excel' => 'Export Excel',

    'locked' => 'Locked',
    'copied' => 'Copied',
    'copy_number' => 'Copy invoice number',

    'filters' => [
        'search_placeholder' => 'Search by ID / number / customer...',
        'status_placeholder' => 'Status',
        'type_placeholder' => 'Type',
        'locked_placeholder' => 'Lock',
        'locked_yes' => 'Locked',
        'locked_no' => 'Not locked',
        'from' => 'From',
        'to' => 'To',
        'reset' => 'Reset',
    ],

    'type' => [
        'invoice' => 'Invoice',
        'adjustment' => 'Adjustment',
        'credit_note' => 'Credit Note',
    ],

    'status' => [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],

    'fields' => [
        'id' => 'ID',
        'number' => 'Number',
        'user' => 'Customer',
        'invoiceable' => 'Reference',
        'type' => 'Type',
        'status' => 'Status',
        'locked' => 'Lock',
        'subtotal' => 'Subtotal',
        'discount' => 'Invoice Discount',
        'tax' => 'Tax',
        'gross_total' => 'Gross Total',
        'total' => 'Total',
        'currency' => 'Currency',
        'issued_at' => 'Issued at',
        'paid_at' => 'Paid at',
        'version' => 'Version',
        'parent_invoice' => 'Parent invoice',
        'child_invoices' => 'Related invoices',
    ],

    'items_title' => 'Invoice Items',
    'items_count' => 'Items count',

    'item' => [
        'fields' => [
            'type' => 'Item type',
            'title' => 'Item',
            'qty' => 'Qty',
            'unit_price' => 'Unit price',
            'line_tax' => 'Line tax',
            'line_total' => 'Line total',
        ],
        'type' => [
            'service' => 'Service',
            'product' => 'Product',
            'fee' => 'Fee',
            'custom' => 'Custom',
        ],
    ],

    'payments_title' => 'Payments',
    'payment' => [
        'fields' => [
            'amount' => 'Amount',
            'method' => 'Method',
            'status' => 'Status',
            'paid_at' => 'Paid at',
        ],
    ],

    'totals_title' => 'Totals summary',

    'notice_unpaid_title' => 'Invoice is unpaid',
    'notice_unpaid_text' => 'You may apply coupon/adjustments before payment based on policies.',

    'coupon_title' => 'Coupon details',
    'coupon' => [
        'code' => 'Code',
        'discount' => 'Discount amount',
        'eligible_base' => 'Eligible base',
        'applied_at' => 'Applied at',
    ],

    'relations_title' => 'Relations',
    'meta_title' => 'Meta',
    'Booking' => 'Booking',

    'manual_payment' => [
        'pay_button' => 'Pay Invoice',
        'modal_title' => 'Manual Invoice Payment',
        'invoice_info' => 'Invoice Information',
        'payment_method' => 'Payment Method',
        'select_method' => 'Select payment method',
        'bank_details_title' => 'Bank Account Details',
        'reference_number' => 'Reference Number',
        'reference_placeholder' => 'Enter reference or transaction number',
        'reference_hint' => 'Bank transfer number or receipt number',
        'attachment' => 'Attachment',
        'attachment_hint' => 'Receipt image or payment proof (PDF, JPG, PNG - up to 5MB)',
        'notes' => 'Notes',
        'notes_placeholder' => 'Any additional notes about the payment',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm Payment',
        'processing' => 'Processing...',

        'already_paid' => 'This invoice is already paid',
        'success' => 'Invoice paid successfully',
        'error' => 'An error occurred while processing payment',
        'fulfillment_failed' => 'Payment received but order fulfillment failed',

        'validation' => [
            'method_required' => 'Payment method is required',
            'method_invalid' => 'Selected payment method is invalid',
            'file_too_large' => 'File size is too large (maximum 5MB)',
        ],
    ],

    // External Payment Methods
    'external_methods' => [
        'name' => 'Payment Method Name',
        'description' => 'Description',
        'code' => 'Code',
        'active' => 'Active',
        'inactive' => 'Inactive',

        'types' => [
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'cheque' => 'Cheque',
            'pos' => 'Point of Sale (POS)',
            'mada' => 'Mada',
        ],
    ],
];