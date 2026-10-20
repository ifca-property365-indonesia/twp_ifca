<?php

// Menu admin Admin Users (App\Http\Controllers\Admin\AdminUserController, App\Support\AdminUsers).
return [
    // Hanya grup admin dengan admincall ini yang boleh melihat menu & membuat user administrator.
    'manager_admincall' => env('ADMIN_USERS_MANAGER', 'admin'),

    // Menu yang boleh dibuka per grup admin (admincall => bagian URL setelah /admin/).
    // Grup yang tidak terdaftar = semua menu. executive_management: Financials & Reports & Documents.
    'restricted_menus' => [
        'executive_management' => ['financials', 'reports'],
    ],

    // Selalu boleh untuk grup terbatas: profil sendiri (View Profile) di header.
    'always_allowed' => ['account/profile', 'account/getbyemail', 'account/updateprofile', 'account/savepic', 'account/changepass'],

    // Password default user administrator baru (pilihan "default" di form).
    'default_password' => env('ADMIN_USER_DEFAULT_PASSWORD', '@Admintwp'),

    // Email pemberitahuan ke user baru (App\Mail\AdminUserCreated) lewat SMTP Gmail
    // (MAIL_HOST=smtp.gmail.com, MAIL_USERNAME/MAIL_PASSWORD = akun & App Password Gmail, MAIL_FROM_*).
    // Nonaktif sampai ADMIN_USER_MAIL=true.
    'mail' => [
        'enabled' => (bool) env('ADMIN_USER_MAIL', false),
        'mailer'  => env('ADMIN_USER_MAILER', 'smtp'),
    ],
];
