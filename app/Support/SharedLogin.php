<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Akun gabungan admin + tenant: satu baris mgr.all_login (tableforeign 'administrator') untuk email yang
 * juga dipakai tenant (mgr.tenant.email). Hak masuk portal tenant tetap dari mgr.tenant + pm_tenancy aktif;
 * yang dipakai dari all_login hanya password (dan foto). Jadi tenant TANPA baris all_login sendiri
 * memakai akun administrator dengan email yang sama.
 *
 * Tenant yang punya baris all_login sendiri (tableforeign 'tenant') tetap memakai barisnya seperti dulu.
 */
class SharedLogin
{
    /** Akun administrator (all_login) dengan email ini, atau null. */
    public static function adminFor(?string $email): ?object
    {
        $email = LoginEmail::normalize($email);
        if ($email === '') {
            return null;
        }
        return DB::table('mgr.all_login')
            ->where('tableforeign', 'administrator')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    /** Baris all_login tenant milik tenant ini (tableforeign 'tenant', idforeign = tenant.id), atau null. */
    public static function tenantRow(int $tenantId): ?object
    {
        return DB::table('mgr.all_login')
            ->where('tableforeign', 'tenant')
            ->where('idforeign', $tenantId)
            ->first();
    }

    /**
     * Akun login untuk tenant ini: baris tenant sendiri, atau (akun gabungan) akun administrator
     * dengan email tenant. $email = mgr.tenant.email (diambil dari tabel bila tidak diberikan).
     */
    public static function forTenant(int $tenantId, ?string $email = null): ?object
    {
        $row = self::tenantRow($tenantId);
        if ($row) {
            return $row;
        }
        $email ??= DB::table('mgr.tenant')->where('id', $tenantId)->value('email');
        return self::adminFor($email);
    }

    /** Tenant ini memakai akun gabungan (tidak punya baris all_login sendiri, akun admin dengan emailnya ada)? */
    public static function isShared(int $tenantId, ?string $email = null): bool
    {
        if (self::tenantRow($tenantId)) {
            return false;
        }
        $email ??= DB::table('mgr.tenant')->where('id', $tenantId)->value('email');
        return self::adminFor($email) !== null;
    }

    /** Password cocok untuk tenant ini (baris tenant sendiri atau akun gabungan)? */
    public static function tenantPasswordMatches(int $tenantId, string $plain, ?string $email = null): bool
    {
        $row = self::forTenant($tenantId, $email);
        return $row !== null && $plain !== '' && Password::check($plain, $row->password);
    }

    /** Tenant (mgr.tenant) dengan email ini yang tidak punya baris all_login sendiri -> ikut akun admin. */
    public static function sharedTenantCount(?string $email): int
    {
        $email = LoginEmail::normalize($email);
        if ($email === '') {
            return 0;
        }
        return DB::table('mgr.tenant as t')
            ->whereRaw('LOWER(t.email) = ?', [$email])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('mgr.all_login as l')
                    ->whereColumn('l.idforeign', 't.id')
                    ->where('l.tableforeign', 'tenant');
            })
            ->count();
    }
}
