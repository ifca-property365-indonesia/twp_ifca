<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * User administrator (login portal admin) dan grupnya.
 *   mgr.administrator : grup admin (name = nama grup di session Tsuname, admincall = kode grup,
 *                       id_foreign = id, mengikuti data yang sudah ada)
 *   mgr.all_login     : akun login; admin = tableforeign 'administrator', idforeign = administrator.id
 * Email admin harus unik di antara baris administrator (login admin mengambil baris pertama per email);
 * email yang sama boleh juga dipakai akun tenant.
 */
class AdminUsers
{
    public const MAX_NAME = 60;          // all_login.name, administrator.name
    public const MAX_EMAIL = 140;        // all_login.email
    public const MAX_PHONE = 60;         // all_login.handphone
    public const MAX_CODE = 60;          // administrator.admincall (kolom 255, dibatasi agar rapi)
    public const MIN_PASSWORD = 8;

    private static function db()
    {
        return DB::connection('ifcaadm');
    }

    /**
     * Admin yang login boleh mengelola user administrator? Hanya grup dengan admincall =
     * config admin_users.manager_admincall ('admin'). Grup admin di session: Tsuser_id = administrator.id.
     */
    public static function canManage(): bool
    {
        static $cache = [];
        $groupId = (int) Session::get('Tsuser_id');
        if (!Session::get('is_login') || $groupId <= 0) {
            return false;
        }
        if (!array_key_exists($groupId, $cache)) {
            $code = self::db()->table('mgr.administrator')->where('id', $groupId)->value('admincall');
            $cache[$groupId] = $code !== null && mb_strtolower(trim($code)) === mb_strtolower((string) config('admin_users.manager_admincall', 'admin'));
        }
        return $cache[$groupId];
    }

    /** admincall grup admin yang login (Tsuser_id = administrator.id), huruf kecil; null bila tidak login. */
    public static function currentCode(): ?string
    {
        static $cache = [];
        $groupId = (int) Session::get('Tsuser_id');
        if (!Session::get('is_login') || $groupId <= 0) {
            return null;
        }
        if (!array_key_exists($groupId, $cache)) {
            $code = self::db()->table('mgr.administrator')->where('id', $groupId)->value('admincall');
            $cache[$groupId] = $code !== null ? mb_strtolower(trim($code)) : null;
        }
        return $cache[$groupId];
    }

    /** Bagian menu yang boleh dibuka grup ini (config admin_users.restricted_menus); null = semua menu. */
    public static function allowedSections(): ?array
    {
        $code = self::currentCode();
        $map = array_change_key_case((array) config('admin_users.restricted_menus', []), CASE_LOWER);
        return $code !== null && isset($map[$code]) ? array_values((array) $map[$code]) : null;
    }

    /**
     * Boleh membuka URL/menu ini? $path boleh dengan atau tanpa awalan 'admin/' (mis. 'admin/financials/cash-flow',
     * 'news'). Grup tanpa pembatasan selalu boleh.
     */
    public static function allows(string $path): bool
    {
        $sections = self::allowedSections();
        if ($sections === null) {
            return true;
        }
        $path = trim((string) preg_replace('#^/?admin(/|$)#', '', trim($path, '/')), '/');
        foreach (array_merge($sections, (array) config('admin_users.always_allowed', [])) as $allowed) {
            $allowed = trim((string) $allowed, '/');
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return true;
            }
        }
        return false;
    }

    /** Halaman awal admin: Dashboard, atau menu pertama yang diizinkan untuk grup terbatas. */
    public static function homePath(): string
    {
        $sections = self::allowedSections();
        return $sections ? 'admin/' . trim($sections[0], '/') : 'admin/dash';
    }

    /** Password default user administrator baru (config admin_users.default_password). */
    public static function defaultPassword(): string
    {
        return (string) config('admin_users.default_password', '');
    }

    /** Daftar grup admin (id => objek). */
    public static function groups(): Collection
    {
        return self::db()->table('mgr.administrator')->orderBy('id')->get()->keyBy('id');
    }

    /** Daftar user administrator beserta nama grupnya. */
    public static function users(): Collection
    {
        return self::db()->table('mgr.all_login as l')
            ->leftJoin('mgr.administrator as a', 'a.id', '=', 'l.idforeign')
            ->where('l.tableforeign', 'administrator')
            ->orderBy('l.id')
            ->select('l.id', 'l.name', 'l.email', 'l.handphone', 'l.idforeign', 'a.name as group_name', 'a.admincall')
            ->get();
    }

    public static function emailExists(string $email): bool
    {
        return self::db()->table('mgr.all_login')
            ->where('tableforeign', 'administrator')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->exists();
    }

    public static function groupNameExists(string $name): bool
    {
        return self::db()->table('mgr.administrator')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();
    }

    public static function groupCodeExists(string $code): bool
    {
        return self::db()->table('mgr.administrator')->whereRaw('LOWER(admincall) = ?', [mb_strtolower($code)])->exists();
    }

    /** Kode grup dari nama: huruf kecil, angka, garis bawah (mis. "Finance Team" -> "finance_team"). */
    public static function codeFrom(string $name): string
    {
        return mb_substr(Str::slug($name, '_'), 0, self::MAX_CODE);
    }

    /**
     * Simpan user administrator baru; $group = ['id' => grup lama] atau ['name' => .., 'code' => ..] untuk grup baru
     * (dibuat dalam transaksi yang sama). Kembali: ['user_id' => .., 'group_id' => .., 'group_created' => bool].
     */
    public static function create(array $user, array $group, string $plainPassword): array
    {
        return self::db()->transaction(function () use ($user, $group, $plainPassword) {
            $created = false;
            if (isset($group['id'])) {
                $groupId = (int) $group['id'];
            } else {
                $groupId = (int) self::db()->table('mgr.administrator')->insertGetId([
                    'id_foreign' => 0,
                    'admincall'  => $group['code'],
                    'name'       => $group['name'],
                ]);
                // id_foreign = id, sama dengan baris administrator yang sudah ada
                self::db()->table('mgr.administrator')->where('id', $groupId)->update(['id_foreign' => $groupId]);
                $created = true;
            }

            $userId = (int) self::db()->table('mgr.all_login')->insertGetId([
                'name'         => $user['name'],
                'email'        => $user['email'],
                'password'     => Password::make($plainPassword),
                'pict'         => null,
                'handphone'    => $user['handphone'] !== '' ? $user['handphone'] : null,
                'tableforeign' => 'administrator',
                'idforeign'    => $groupId,
            ]);

            return ['user_id' => $userId, 'group_id' => $groupId, 'group_created' => $created];
        });
    }
}
