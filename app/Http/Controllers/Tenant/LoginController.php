<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\LoginLog;
use App\Support\Password;
use App\Support\UserLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Login tenant sekarang satu pintu di "/" (App\Http\Controllers\PortalLoginController).
 * login() di sini hanya dipakai halaman pilih business (tenant.login.step) untuk
 * tenant yang punya lebih dari satu business aktif.
 */
class LoginController extends Controller
{
    public function index()
    {
        if (Session::get('is_Tenant_logged')) {
            return redirect('/tenant/dash');
        }
        return redirect('/');
    }

    public function login(Request $request)
    {
        $request->validate([
            'bsn' => ['required'],
            'password' => ['required'],
        ]);

        $bsn = $request->bsn;
        $plain = $request->password;

        // bcrypt (dan md5 untuk akun lama) dicek per baris, lihat App\Support\Password.
        $datas = DB::table('mgr.all_login')
            ->where('tableforeign', 'tenant')
            ->where('idforeign', $bsn)
            ->get();

        foreach ($datas as $login) {
            if (!Password::check($plain, $login->password)) {
                continue;
            }

            Password::upgrade(
                DB::table('mgr.all_login')->where('id', $login->id),
                $login->password,
                $plain
            );

            $this->createSession($login->idforeign);
            UserLocale::load($login->email);   // bahasa pilihan user ini (default English)
            return redirect('/tenant/dash');
        }

        return redirect('/')->with('alert', __('tenant/login.user_not_found'));
    }

    /**
     * Isi session tenant + catat log_login. Dipakai oleh login() di sini dan oleh
     * PortalLoginController (login satu pintu & pindah portal).
     */
    public function createSession($tenantId)
    {
        $kriteriaTenant = ['tenant.id' => $tenantId];

        $dataTenant = DB::table('mgr.tenant')
            ->join('mgr.pm_tenancy', function ($join) {
                $join->on('tenant.business_no', '=', 'pm_tenancy.business_no')
                    ->on('tenant.tenant_no_df', '=', 'pm_tenancy.tenant_no');
            })
            ->where($kriteriaTenant)
            ->select(
                'tenant.*',
                'pm_tenancy.entity_cd',
                'pm_tenancy.project_no'
            )
            ->first();

        Session::put('is_Tenant_logged', true);
        Session::put('Tuname', $dataTenant->contact_name);
        Session::put('TCompany', $dataTenant->name);
        Session::put('Tuser_id', $dataTenant->id);
        Session::put('business_no', $dataTenant->business_no);
        Session::put('tenant_df', $dataTenant->tenant_no_df);
        Session::put('Tenemail', $dataTenant->email);
        Session::put('Tflag', $dataTenant->flag);
        Session::put('entity_cd', $dataTenant->entity_cd);
        Session::put('project_no', $dataTenant->project_no);

        // Header: nama (all_login.name), contact person (tenant.contact_name = Tuname) dan foto.
        // Diperbarui oleh Tenant\AccountController::updateprofile.
        $login = DB::table('mgr.all_login')
            ->where('email', $dataTenant->email)
            ->where('tableforeign', 'tenant')
            ->first();
        Session::put('Tdisplay_name', $login->name ?? $dataTenant->name);
        Session::put('Tpict', !empty($login->pict) ? $login->pict : '');

        // Akun yang juga administrator (login satu pintu mencatatnya di session 'portals')
        // masuk portal tenant dalam "mode semua tenant" -> lihat App\Support\TenantScope.
        $portals = Session::get('portals', array());
        Session::put('Tall_tenants', !empty($portals['admin']));

        // email yang diketik di halaman login (session login_email), kalau tidak ada: email akun
        LoginLog::record(LoginLog::TENANT, $tenantId, Session::get('login_email') ?: ($login->email ?? $dataTenant->email));
    }

    /**
     * Daftar record tenant milik sebuah email yang tenancy-nya masih aktif
     * (status 'A' dan belum expired). Dipakai get_combo() dan PortalLoginController.
     */
    public function activeTenants($email)
    {
        $crit = array('email' => $email);
        $query = DB::table('mgr.tenant')->where($crit)->get();
        if (count($query) === 0) {
            return array();
        }

        $wherein = ""; //ambil businessno by email
        foreach ($query as $result) {
            $wherein .= "'" . $result->business_no . "',";
        }
        $wherein = substr($wherein, 0, -1);

        $sql = "SELECT * FROM mgr.pm_tenancy WHERE business_no in (" . $wherein . ") and status = 'A' and expiry_date >= GETDATE()";
        $query = DB::select($sql);

        if (!empty($query)) {
            $wherein2 = "";
            foreach ($query as $result) {
                $wherein2 .= "'" . $result->business_no . "',";
            }
            $wherein2 = substr($wherein2, 0, -1);
        } else {
            $wherein2 = "''";
        }

        $query2 = "SELECT * FROM mgr.tenant WHERE email = ? AND business_no IN ($wherein2)";
        return DB::select($query2, [$email]);
    }

    function get_combo($selected_id = "", $crit = null)
    {
        $results2 = $this->activeTenants($crit['email']);
        // start making combo
        $combo[] = '<option value=0>' . e(__('tenant/login.choose')) . '</option>';
        $combo[] = "\n";
        if (count($results2) === 1) {
            $selected_id = $results2[0]->id;
        }

        foreach ($results2 as $result) {
            if ($result->id == $selected_id) {
                $selected = ' selected="1"';
            } else {
                $selected = '';
            }

            $combo[] = '<option value="' . $result->id . '"' . $selected . '>' . $result->name . '</option>';
            $combo[] = "\n";
        }
        return implode("", $combo);
    }

    public function logout()
    {
        Session::flush();
        return redirect('/');
    }
}
