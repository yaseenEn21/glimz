<?php

return [
    'title' => 'الفواتير',
    'list' => 'قائمة الفواتير',
    'invoice' => 'فاتورة',
    'view' => 'عرض',
    'print' => 'طباعة',
    'back_to_list' => 'رجوع للقائمة',
    'actions_title' => 'إجراءات',

    'locked' => 'مقفلة',
    'copied' => 'تم النسخ',
    'copy_number' => 'نسخ رقم الفاتورة',

    'filters' => [
        'search_placeholder' => 'بحث بالرقم / رقم الفاتورة / العميل...',
        'status_placeholder' => 'الحالة',
        'type_placeholder' => 'النوع',
        'locked_placeholder' => 'القفل',
        'locked_yes' => 'مقفلة',
        'locked_no' => 'غير مقفلة',
        'from' => 'من',
        'to' => 'إلى',
        'reset' => 'إعادة ضبط',
    ],

    'type' => [
        'invoice' => 'فاتورة',
        'adjustment' => 'تعديل',
        'credit_note' => 'إشعار دائن',
    ],

    'status' => [
        'unpaid' => 'غير مدفوعة',
        'paid' => 'مدفوعة',
        'cancelled' => 'ملغاة',
        'refunded' => 'مسترجعة',
    ],

    'fields' => [
        'id' => 'المعرف',
        'number' => 'رقم الفاتورة',
        'user' => 'العميل',
        'invoiceable' => 'المرجع',
        'type' => 'نوع الفاتورة',
        'status' => 'الحالة',
        'locked' => 'القفل',
        'subtotal' => 'الإجمالي قبل الخصم',
        'discount' => 'خصم الفاتورة',
        'tax' => 'الضريبة',
        'gross_total' => 'الإجمالي قبل الخصم (مع الضريبة)',
        'total' => 'الإجمالي النهائي',
        'currency' => 'العملة',
        'issued_at' => 'تاريخ الإصدار',
        'paid_at' => 'تاريخ الدفع',
        'version' => 'الإصدار',
        'parent_invoice' => 'الفاتورة الأصل',
        'child_invoices' => 'فواتير مرتبطة',
    ],

    'items_title' => 'بنود الفاتورة',
    'items_count' => 'عدد البنود',

    'item' => [
        'fields' => [
            'type' => 'نوع البند',
            'title' => 'البند',
            'qty' => 'الكمية',
            'unit_price' => 'سعر الوحدة',
            'line_tax' => 'ضريبة السطر',
            'line_total' => 'إجمالي السطر',
        ],
        'type' => [
            'service' => 'خدمة',
            'product' => 'منتج',
            'fee' => 'رسوم',
            'custom' => 'مخصص',
        ],
    ],

    'payments_title' => 'المدفوعات',
    'payment' => [
        'fields' => [
            'amount' => 'المبلغ',
            'method' => 'الطريقة',
            'status' => 'الحالة',
            'paid_at' => 'تاريخ الدفع',
        ],
    ],

    'totals_title' => 'ملخص المبالغ',

    'notice_unpaid_title' => 'الفاتورة غير مدفوعة',
    'notice_unpaid_text' => 'يمكن تطبيق كوبون/تعديل قبل الدفع حسب السياسات.',

    'coupon_title' => 'تفاصيل الكوبون',
    'coupon' => [
        'code' => 'الكود',
        'discount' => 'قيمة الخصم',
        'eligible_base' => 'الأساس المؤهل',
        'applied_at' => 'تاريخ التطبيق',
    ],

    'relations_title' => 'العلاقات',
    'meta_title' => 'بيانات إضافية (Meta)',
    'Package'          => 'باقة',
    'Booking'          => 'حجز',

    'manual_payment' => [
        'pay_button' => 'تسديد الفاتورة',
        'modal_title' => 'تسديد الفاتورة يدوياً',
        'invoice_info' => 'معلومات الفاتورة',
        'payment_method' => 'وسيلة الدفع',
        'select_method' => 'اختر وسيلة الدفع',
        'bank_details_title' => 'تفاصيل الحساب البنكي',
        'reference_number' => 'رقم المرجع',
        'reference_placeholder' => 'أدخل رقم المرجع أو التحويل',
        'reference_hint' => 'رقم التحويل البنكي أو رقم الإيصال',
        'attachment' => 'المرفق',
        'attachment_hint' => 'صورة الإيصال أو إثبات الدفع (PDF, JPG, PNG - حتى 5MB)',
        'notes' => 'ملاحظات',
        'notes_placeholder' => 'أي ملاحظات إضافية عن عملية الدفع',
        'cancel' => 'إلغاء',
        'confirm' => 'تأكيد الدفع',
        'processing' => 'جاري المعالجة...',
        
        'already_paid' => 'هذه الفاتورة مدفوعة بالفعل',
        'success' => 'تم تسديد الفاتورة بنجاح',
        'error' => 'حدث خطأ أثناء معالجة الدفع',
        'fulfillment_failed' => 'تم الدفع ولكن فشل تنفيذ الطلب',
        
        'validation' => [
            'method_required' => 'يجب اختيار وسيلة دفع',
            'method_invalid' => 'وسيلة الدفع المختارة غير صالحة',
            'file_too_large' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)',
        ],
    ],

    // External Payment Methods
    'external_methods' => [
        'name' => 'اسم وسيلة الدفع',
        'description' => 'الوصف',
        'code' => 'الرمز',
        'active' => 'نشطة',
        'inactive' => 'غير نشطة',
        
        'types' => [
            'bank_transfer' => 'تحويل بنكي',
            'cash' => 'نقدي',
            'cheque' => 'شيك',
            'pos' => 'نقطة بيع (POS)',
            'mada' => 'مدى',
        ],
    ],
];