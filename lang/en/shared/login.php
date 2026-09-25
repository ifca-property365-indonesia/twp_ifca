<?php

// Halaman login satu pintu (admin & tenant) dan pesan otentikasi.
return [
    'page_title'        => 'Log in',
    'log_in'            => 'Log In',
    'portal_name'       => 'IFCA Tenant Portal',
    'business_name'     => 'Business Name',
    'ph_email'          => 'Enter your email address',
    'ph_password'       => 'Enter your password',
    'toggle_password'   => 'Show / hide password',
    'checking_email'    => 'Checking email...',
    'enter_email_first' => 'Enter your email first',
    'choose'            => '-- Choose --',

    'incorrect'         => 'Incorrect email or password.',
    'account_expired'   => 'Your account expired on :date. Please contact Building Management.',
    'account_inactive'  => 'Your account is not active. Please contact Building Management.',
    'no_admin_access'   => 'This account does not have Admin access.',
    'no_business_access'=> 'This account does not have access to the selected business.',
    'login_first'       => 'Please login first!',

    'attributes' => [
        'email'    => 'email',
        'password' => 'password',
    ],
    // kotak Demo accounts (config/demo_accounts.php)
    'demo_title'        => 'Demo accounts',
    'demo_password'     => 'Password for every account: :password',
    'demo_hint'         => 'Click an account to fill in the form.',
];
