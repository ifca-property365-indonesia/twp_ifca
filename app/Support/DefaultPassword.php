<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Password default untuk akun baru / reset password, diambil dari tabel `defaultpassword`
 * (diatur admin lewat menu System Spec -> Default Password). Kalau tabel kosong,
 * dipakai nilai lama 'cartenz123'.
 */
class DefaultPassword
{
    const FALLBACK = 'cartenz123';

    /** Password default dalam bentuk plain text. */
    public static function get()
    {
        $value = DB::connection('ifcaadm')->table('mgr.defaultpassword')->value('password');
        $value = is_string($value) ? trim($value) : '';
        return $value !== '' ? $value : self::FALLBACK;
    }

    /** Password default dalam bentuk hash siap simpan ke all_login (bcrypt). */
    public static function hash()
    {
        return Password::make(self::get());
    }
}
