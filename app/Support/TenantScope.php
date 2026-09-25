<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Cakupan data tenant untuk portal tenant.
 *
 * Tenant biasa hanya melihat data miliknya sendiri (tenant_no / business_no / id dari session).
 * Akun yang juga administrator (mis. admin@ifca.co.id) masuk portal tenant dalam
 * "mode semua tenant" (session Tall_tenants = true): semua tenancy aktif yang terdaftar
 * ikut masuk cakupan, sehingga dropdown tenant/unit dan daftar ticket/tagihan/riwayat
 * menampilkan data seluruh tenant. Berapapun tenant yang ditambahkan nanti otomatis ikut.
 */
class TenantScope
{
    /** Mode semua tenant aktif? */
    public static function all()
    {
        return (bool) Session::get('Tall_tenants', false);
    }

    /** Baris mgr.pm_tenancy (demo_twp_adm) yang masuk cakupan. */
    public static function tenancies()
    {
        $q = DB::table('mgr.pm_tenancy');
        if (self::all()) {
            $q->where('status', 'A');
        } else {
            $q->where('business_no', Session::get('business_no'))
              ->where('tenant_no', Session::get('tenant_df'));
        }
        return $q->orderBy('tenant_no')->get();
    }

    /** Daftar tenant_no (= debtor_acct di SQL Server) yang masuk cakupan. */
    public static function tenantNos()
    {
        if (!self::all()) {
            return array((string) Session::get('tenant_df'));
        }
        return DB::table('mgr.pm_tenancy')->where('status', 'A')->distinct()->pluck('tenant_no')->map(fn ($v) => (string) $v)->all();
    }

    /** Daftar business_no yang masuk cakupan. */
    public static function businessNos()
    {
        if (!self::all()) {
            return array((string) Session::get('business_no'));
        }
        return DB::table('mgr.pm_tenancy')->where('status', 'A')->distinct()->pluck('business_no')->map(fn ($v) => (string) $v)->all();
    }

    /**
     * Daftar entity_cd yang masuk cakupan. Tenant biasa: entity tenancy-nya sendiri;
     * mode semua tenant: seluruh entity tenancy aktif, supaya data tenant di entity lain
     * ikut terlihat (dipakai bersama tenantNos() yang membatasi debtor_acct).
     */
    public static function entityCds()
    {
        if (!self::all()) {
            return array((string) Session::get('entity_cd'));
        }
        return DB::table('mgr.pm_tenancy')->where('status', 'A')->distinct()->pluck('entity_cd')->map(fn ($v) => (string) $v)->all();
    }

    /** Daftar project_no yang masuk cakupan (lihat entityCds()). */
    public static function projectNos()
    {
        if (!self::all()) {
            return array((string) Session::get('project_no'));
        }
        return DB::table('mgr.pm_tenancy')->where('status', 'A')->distinct()->pluck('project_no')->map(fn ($v) => (string) $v)->all();
    }

    /** Daftar tenant.id (demo_twp_adm, dipakai kolom id_tenant) yang masuk cakupan. */
    public static function tenantIds()
    {
        if (!self::all()) {
            return array((int) Session::get('Tuser_id'));
        }
        return DB::table('mgr.tenant')->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Potongan SQL "kolom IN ('a','b')" untuk query mentah. Nilai di-escape sederhana
     * (data berasal dari tabel sendiri, bukan input user).
     */
    public static function sqlIn($column, array $values)
    {
        if (count($values) === 0) {
            return '1=0';
        }
        $quoted = array_map(fn ($v) => "'" . str_replace("'", "''", $v) . "'", $values);
        return $column . ' IN (' . implode(',', $quoted) . ')';
    }

    /** "debtor_acct IN (...)" untuk query mentah ke demo_twp. */
    public static function sqlTenantNo($column = 'debtor_acct')
    {
        return self::sqlIn($column, self::tenantNos());
    }

    /** "entity_cd IN (...)" untuk query mentah ke demo_twp. */
    public static function sqlEntity($column = 'entity_cd')
    {
        return self::sqlIn($column, self::entityCds());
    }

    /** "id_tenant IN (...)" untuk query mentah ke demo_twp_adm. */
    public static function sqlTenantId($column = 'id_tenant')
    {
        return self::sqlIn($column, array_map('strval', self::tenantIds()));
    }
}
