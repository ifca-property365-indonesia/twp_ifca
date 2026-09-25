<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\BasePermitController;
use App\Support\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Permit Letter portal tenant. Logika ada di BasePermitController; di sini hanya
 * cakupan data (tenant yang sedang login) dan identitas pemohon.
 */
class PermitController extends BasePermitController
{
    protected function portal()
    {
        return 'tenant';
    }

    protected function layout()
    {
        return 'tenant.template.base';
    }

    /** Tenancy milik tenant yang login (akun admin di portal tenant: seluruh tenancy aktif). */
    protected function tenancies()
    {
        return TenantScope::tenancies();
    }

    protected function tenantNos()
    {
        return TenantScope::tenantNos();
    }

    /** Pemohon = kontak tenant yang sedang login. */
    protected function applicant()
    {
        $tenant = DB::table('mgr.tenant as t')
            ->leftJoin('mgr.all_login as al', 't.email', '=', 'al.email')
            ->where('al.email', Session::get('Tenemail'))
            // satu email bisa punya baris admin dan tenant di all_login; utamakan baris tenant
            ->orderByRaw("CASE WHEN al.tableforeign = 'tenant' THEN 0 ELSE 1 END")
            ->select('t.contact_name as contact_name', 'al.handphone as handphone')
            ->first();

        if (!$tenant) {
            return null;
        }

        return [
            'name'  => Session::get('Tuname') ?: $tenant->contact_name,
            'email' => Session::get('Tenemail'),
            'hp'    => $tenant->handphone,
        ];
    }
}
