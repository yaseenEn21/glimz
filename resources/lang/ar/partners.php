<?php

return [
    // Page Titles
    'title' => 'الشركاء',
    'partners' => 'الشركاء',
    'partner' => 'الشريك',
    'list' => 'قائمة الشركاء',
    'create' => 'إضافة شريك جديد',
    'edit' => 'تعديل الشريك',
    'show' => 'تفاصيل الشريك',
    'assign_services' => 'تخصيص الخدمات والموظفين',

    'api_documentation' => 'توثيق API',
    'api_documentation_desc' => 'اطّلع على التوثيق الكامل لـ API مع أمثلة وشرح مفصل',
    'view_documentation' => 'عرض التوثيق',

    // Fields
    'fields' => [
        'name' => 'الاسم',
        'username' => 'اسم المستخدم',
        'email' => 'البريد الإلكتروني',
        'mobile' => 'رقم الجوال',
        'webhook_url' => 'عنوان Webhook',
        'webhook_type' => 'نوع Webhook',
        'daily_booking_limit' => 'الحد اليومي للحجوزات',
        'api_token' => 'API Token',
        'is_active' => 'نشط',
        'status' => 'الحالة',
        'created_at' => 'تاريخ الإنشاء',
        'services_count' => 'عدد الخدمات',
        'employees_count' => 'عدد الموظفين',
    ],

    // Actions
    'actions' => [
        'view' => 'عرض',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'assign_services' => 'تخصيص الخدمات',
        'copy_token' => 'نسخ Token',
        'regenerate_token' => 'إعادة توليد Token',
        'show_token' => 'عرض Token',
        'hide_token' => 'إخفاء Token',
    ],

    // Status
    'active' => 'نشط',
    'inactive' => 'غير نشط',

    // Messages
    'created_successfully' => 'تم إنشاء الشريك بنجاح',
    'updated_successfully' => 'تم تحديث الشريك بنجاح',
    'deleted_successfully' => 'تم حذف الشريك بنجاح',
    'token_regenerated' => 'تم إعادة توليد Token بنجاح',
    'token_copied' => 'تم نسخ Token',
    'assignments_updated' => 'تم تحديث التخصيصات بنجاح',

    // Validations
    'username_english_only' => 'اسم المستخدم يجب أن يكون بالإنجليزية فقط (حروف، أرقام، شرطة، شرطة سفلية)',
    'username_taken' => 'اسم المستخدم مستخدم مسبقاً',

    // Descriptions
    'username_help' => 'إنجليزي فقط، بدون مسافات (مثال: msmar-services)',
    'webhook_url_help' => 'سيتم إرسال التحديثات إلى هذا العنوان',
    'daily_booking_limit_help' => 'عدد الحجوزات المسموح بها يومياً لهذا الشريك',
    'api_token_help' => 'استخدم هذا Token في طلبات API',

    // Tables
    'no_partners' => 'لا يوجد شركاء',
    'total' => 'المجموع',

    // Assignments
    'assigned_services' => 'الخدمات المخصصة',
    'edit_services' => 'تعديل التخصيصات',
    'no_services_assigned' => 'لم يتم تخصيص خدمات بعد',
    'service' => 'الخدمة',
    'employees' => 'الموظفين',
    'select_service' => 'اختر الخدمة',
    'select_employees' => 'اختر الموظفين',
    'add_service' => 'إضافة خدمة',
    'remove_service' => 'إزالة',
    'at_least_one_employee' => 'يجب اختيار موظف واحد على الأقل',

    // Confirm
    'delete_confirm' => 'هل أنت متأكد من حذف هذا الشريك؟',
    'regenerate_token_confirm' => 'هل أنت متأكد من إعادة توليد Token؟ سيتم إبطال Token القديم.',

    'search_by_name_placeholder' => 'ابحث بالاسم أو اسم المستخدم...',
    'all_statuses' => 'جميع الحالات',
    'save' => 'حفظ',
    'cancel' => 'الغاء',
    'details' => 'تفاصيل',

    'api' => [
        'unauthorized' => 'غير مصرح',
        'invalid_token' => 'رمز غير صالح',
        'partner_inactive' => 'حساب الشريك غير نشط',
        'daily_limit_exceeded' => 'تم تجاوز الحد اليومي للحجوزات',
        'service_not_authorized' => 'الشريك غير مصرح له بهذه الخدمة',
        'employee_not_authorized' => 'الموظف غير مصرح له بهذه الخدمة',
    ],

    'webhook_type_help' => 'حدد نوع الـ webhook حسب متطلبات الشريك',

    'bookings_title' => 'حجوزات الشريك',
    'external_id' => 'رقم الحجز الخارجي',
    'stats' => [
        'total_bookings' => 'إجمالي الحجوزات',
    ],
];