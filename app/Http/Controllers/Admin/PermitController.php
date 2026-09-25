<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BasePermitController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Permit Letter portal admin. Logika ada di BasePermitController; bedanya:
 * admin melihat & membuat permit untuk semua tenant, saat mengubah hanya boleh
 * mengganti jadwal/catatan/daftar, mencetak formulir untuk ditandatangani, dan
 * meng-approve permit dengan mengunggah dokumen bertanda tangan (uploadSigned).
 */
class PermitController extends BasePermitController
{
    protected function portal()
    {
        return 'admin';
    }

    protected function layout()
    {
        return 'admin.template.layout2.base';
    }

    /** Semua tenancy aktif, tanpa filter tenant. */
    protected function tenancies()
    {
        return DB::table('mgr.pm_tenancy')
            ->where('status', 'A')
            ->orderBy('tenant_no')
            ->get();
    }

    /** null = tanpa batas cakupan (semua tenant). */
    protected function tenantNos()
    {
        return null;
    }

    /** Pemohon = admin yang sedang login. */
    protected function applicant()
    {
        $login = DB::connection('ifcaadm')->table('mgr.all_login')
            ->where('email', Session::get('Tsemail'))
            ->where('tableforeign', 'administrator')
            ->first();

        return [
            'name'  => Session::get('Tsdisplay_name') ?: Session::get('Tsuname'),
            'email' => Session::get('Tsemail'),
            'hp'    => $login->handphone ?? null,
        ];
    }

    /** Daftar tenant untuk filter di History admin. */
    public function tenantFilter()
    {
        return $this->tenancies();
    }
}
