<?php

namespace App\Support;

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Pilihan bahasa tampilan per user, disimpan di tabel mgr.user_locale (demo_twp_adm) (per email login).
 *
 * - User yang belum pernah memilih -> English (config app.locale), sampai dia
 *   mengganti bahasa sendiri lewat menu Language di header.
 * - Saat login, pilihannya dimuat ke session 'locale' (load()); middleware SetLocale
 *   membaca session itu di setiap request. Logout mengosongkan session -> kembali English.
 * - Kalau tabel belum dibuat / database bermasalah, login dan ganti bahasa tetap jalan
 *   (hanya tidak tersimpan permanen); error-nya dicatat di log.
 */
class UserLocale
{
    /** Bahasa tersimpan untuk email ini, atau default kalau belum pernah memilih. */
    public static function forEmail($email)
    {
        try {
            $locale = DB::table('mgr.user_locale')->where('email', self::key($email))->value('locale');
        } catch (\Throwable $e) {
            Log::warning('UserLocale::forEmail: ' . $e->getMessage());
            $locale = null;
        }

        return isset(SetLocale::LOCALES[$locale]) ? $locale : config('app.locale');
    }

    /** Muat bahasa user yang baru login ke session. */
    public static function load($email)
    {
        Session::put('login_email', self::key($email));
        Session::put('locale', self::forEmail($email));
    }

    /** Simpan pilihan bahasa user yang sedang login (tidak melakukan apa-apa kalau belum login). */
    public static function save($locale)
    {
        $email = self::currentEmail();
        if ($email === '' || !isset(SetLocale::LOCALES[$locale])) {
            return;
        }

        try {
            DB::table('mgr.user_locale')->updateOrInsert(
                ['email' => $email],
                ['locale' => $locale, 'updated_at' => now()]
            );
        } catch (\Throwable $e) {
            Log::warning('UserLocale::save: ' . $e->getMessage());
        }
    }

    /**
     * Email user yang sedang login. 'login_email' diisi saat login; Tsemail / Tenemail
     * untuk session yang sudah login sebelum fitur ini ada.
     */
    private static function currentEmail()
    {
        return self::key(Session::get('login_email') ?: (Session::get('Tsemail') ?: Session::get('Tenemail')));
    }

    private static function key($email)
    {
        return strtolower(trim((string) $email));
    }
}
