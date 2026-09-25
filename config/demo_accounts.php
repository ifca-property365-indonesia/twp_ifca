<?php

// Kotak "Demo accounts" di halaman login (resources/views/login.blade.php).
// Nilai dari .env (tidak masuk git): LOGIN_DEMO_ACCOUNTS, LOGIN_DEMO_PASSWORD, LOGIN_DEMO_LIST.
// Server produksi: LOGIN_DEMO_ACCOUNTS=false (atau tidak diisi) -> kotak tidak dikirim ke browser.
return [
    'enabled'  => (bool) env('LOGIN_DEMO_ACCOUNTS', false),
    'password' => (string) env('LOGIN_DEMO_PASSWORD', ''),
    // LOGIN_DEMO_LIST="email|label;email|label"
    'accounts' => array_values(array_filter(array_map(function ($item) {
        [$email, $role] = array_pad(explode('|', trim($item), 2), 2, '');
        return trim($email) === '' ? null : ['email' => trim($email), 'role' => trim($role)];
    }, explode(';', (string) env('LOGIN_DEMO_LIST', ''))))),
];
