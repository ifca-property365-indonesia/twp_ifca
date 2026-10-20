<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Ganti email login dari View Profile (admin & tenant).
 *
 * Email adalah identitas login: admin dicari lewat all_login.email, tenant lewat tenant.email,
 * dan satu email bisa memiliki beberapa akun (mis. admin sekaligus tenant). Karena itu semua
 * baris milik email lama dipindah bersama ke email baru: all_login, tenant dan user_locale,
 * lalu session diperbarui supaya user tetap login.
 */
class LoginEmail
{
    /** Panjang maksimum (kolom email terpendek yang ikut diubah: user_locale / log_login). */
    public const MAX = 100;

    public static function normalize($email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Pesan error kalau email baru tidak bisa dipakai, null kalau boleh.
     * $old = email yang sedang login.
     */
    public static function validate($old, $new): ?string
    {
        $new = self::normalize($new);
        if ($new === '' || !filter_var($new, FILTER_VALIDATE_EMAIL) || mb_strlen($new) > self::MAX) {
            return __('shared/account.email_invalid');
        }
        if ($new === self::normalize($old)) {
            return null;
        }

        $db = DB::connection('ifcaadm');
        $used = $db->table('mgr.all_login')->whereRaw('LOWER(email) = ?', [$new])->exists()
            || $db->table('mgr.tenant')->whereRaw('LOWER(email) = ?', [$new])->exists();

        return $used ? __('shared/account.email_taken') : null;
    }

    /**
     * Password saat ini cocok untuk akun yang sedang login?
     * $type = 'administrator' | 'tenant', $id = administrator.id / tenant.id, $email = email login.
     * Akun dicari lewat idforeign + email: satu grup administrator (atau tenant) bisa punya beberapa
     * akun login, jadi tanpa email yang terambil akun pertama, bukan akun yang sedang login.
     */
    public static function passwordMatches($type, $id, $plain, $email): bool
    {
        if (self::normalize($email) === '' || (string) $plain === '') {
            return false;
        }
        $stored = DB::connection('ifcaadm')->table('mgr.all_login')
            ->where('tableforeign', $type)
            ->where('idforeign', (int) $id)
            ->whereRaw('LOWER(email) = ?', [self::normalize($email)])
            ->value('password');
        // tenant akun gabungan (tanpa baris all_login sendiri): password akun administrator dengan email sama
        if ($stored === null && $type === 'tenant' && SharedLogin::isShared((int) $id, $email)) {
            $stored = SharedLogin::adminFor($email)->password ?? null;
        }

        return $stored !== null && Password::check($plain, $stored);
    }

    /** Pindahkan semua akun email lama ke email baru (satu transaksi) dan perbarui session. */
    public static function change($old, $new): void
    {
        $old = trim((string) $old);
        $new = self::normalize($new);
        if (self::normalize($old) === $new) {
            return;
        }

        $db = DB::connection('ifcaadm');
        $db->transaction(function () use ($db, $old, $new) {
            $db->table('mgr.all_login')->whereRaw('LOWER(email) = ?', [self::normalize($old)])->update(['email' => $new]);
            $db->table('mgr.tenant')->whereRaw('LOWER(email) = ?', [self::normalize($old)])->update(['email' => $new]);

            // pilihan bahasa ikut pindah (kunci user_locale = email)
            $db->table('mgr.user_locale')->where('email', $new)->delete();
            $db->table('mgr.user_locale')->where('email', self::normalize($old))->update(['email' => $new]);
        });

        // session: portal yang sedang dibuka dan menu pindah portal di header
        foreach (['Tenemail', 'Tsemail', 'login_email'] as $key) {
            if (self::normalize(Session::get($key)) === self::normalize($old)) {
                Session::put($key, $new);
            }
        }
        $portals = Session::get('portals');
        if (!empty($portals['admin']['email']) && self::normalize($portals['admin']['email']) === self::normalize($old)) {
            $portals['admin']['email'] = $new;
            Session::put('portals', $portals);
        }
    }
}
