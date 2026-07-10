<?php

return [
    'title' => 'المستخدمون',
    'add_user' => 'إضافة مستخدم',
    'edit_user' => 'تعديل المستخدم',
    'back_to_users' => 'العودة إلى المستخدمين',

    'name' => 'الاسم',
    'email' => 'البريد الإلكتروني',
    'phone' => 'الهاتف',
    'password' => 'كلمة المرور',
    'confirm_password' => 'تأكيد كلمة المرور',
    'role' => 'الدور',
    'status' => 'الحالة',
    'active' => 'مفعّل',
    'inactive' => 'غير مفعّل',
    'created_at' => 'تاريخ الإنشاء',

    'role_super_admin' => 'مدير عام',
    'role_admin' => 'مدير',
    'role_employee' => 'موظف',
    'role_customer' => 'عميل',
    'all_roles' => 'جميع الأدوار',

    'search_placeholder' => 'ابحثي بالاسم أو البريد الإلكتروني…',

    'email_verified' => 'وسم البريد الإلكتروني كمؤكد',
    'send_welcome_email' => 'إرسال بريد ترحيبي يحتوي على بيانات الدخول',

    'permissions_title' => 'الصلاحيات',
    'permissions_hint' => 'تُستخدم فقط لدور الموظف — المدير والمدير العام لديهما دائمًا صلاحية كاملة.',
    'select_all' => 'تحديد الكل',
    'clear_all' => 'إلغاء التحديد',
    'search_permissions' => 'ابحثي في الصلاحيات…',
    'presets_title' => 'قوالب جاهزة',
    'apply_preset' => 'تطبيق',

    'presets' => [
        'product_manager' => 'مسؤول المنتجات',
        'order_manager' => 'مسؤول الطلبات',
        'inventory_manager' => 'مسؤول المخزون',
        'content_manager' => 'مسؤول المحتوى',
        'customer_support' => 'دعم العملاء',
        'marketing_manager' => 'مسؤول التسويق',
    ],

    'save_user' => 'حفظ المستخدم',
    'reset_password' => 'إعادة تعيين كلمة المرور',
    'force_logout' => 'تسجيل الخروج الإجباري',
    'confirm_delete' => 'حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.',
    'confirm_reset_password' => 'إرسال رابط إعادة تعيين كلمة المرور لهذا المستخدم؟',
    'confirm_force_logout' => 'تسجيل خروج هذا المستخدم من جميع الأجهزة؟',
    'confirm_toggle_active' => 'تغيير حالة تفعيل هذا المستخدم؟',

    'no_users' => 'لا يوجد مستخدمون.',

    'created' => 'تم إنشاء المستخدم بنجاح.',
    'updated' => 'تم تحديث المستخدم بنجاح.',
    'deleted' => 'تم حذف المستخدم بنجاح.',
    'user_disabled' => 'تم تعطيل المستخدم.',
    'user_enabled' => 'تم تفعيل المستخدم.',
    'reset_link_sent' => 'تم إرسال رابط إعادة تعيين كلمة المرور لهذا المستخدم.',
    'force_logout_done' => 'تم تسجيل خروج هذا المستخدم من جميع الأجهزة.',

    'cannot_change_own_role' => 'لا يمكنكِ تغيير دورك الخاص.',
    'cannot_delete_last_super_admin' => 'لا يمكنكِ حذف آخر مدير عام متبقٍ.',

    'primary_super_admin_badge' => 'المدير العام الرئيسي',
    'primary_super_admin_hint' => 'هذا هو حساب المدير العام الرئيسي للنظام. دوره وبريده الإلكتروني وصلاحياته وحالة تفعيله مقفلة، ولا يمكن حذفه.',
    'cannot_change_primary_super_admin_role' => 'لا يمكن تغيير دور المدير العام الرئيسي.',
    'cannot_change_primary_super_admin_email' => 'لا يمكن تغيير البريد الإلكتروني للمدير العام الرئيسي.',
];
