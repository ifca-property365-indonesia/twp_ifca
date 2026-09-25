<?php

// Ganti email login dari View Profile (admin & tenant), lihat App\Support\LoginEmail.
return [
    'email_note'          => 'This email is used to sign in. Changing it requires your current password.',
    'email_invalid'       => 'Please enter a valid email address (max. 100 characters).',
    'email_taken'         => 'This email is already used by another account.',
    'email_confirm_title' => 'Change Login Email',
    'email_confirm_text'  => 'From now on you will sign in with :email. Enter your current password to confirm.',
    'current_password'    => 'Current password',
    'password_required'   => 'Please enter your current password.',
    'password_wrong'      => 'The current password is incorrect.',
    'email_changed'       => 'Profile updated. Please sign in with :email from now on.',
];
