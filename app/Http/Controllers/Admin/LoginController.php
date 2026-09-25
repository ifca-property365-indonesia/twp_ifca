<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LoginLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Login admin sekarang satu pintu di "/" (App\Http\Controllers\PortalLoginController).
 * Class ini tinggal menyediakan pengisian session admin, redirect /admin, dan logout.
 */
class LoginController extends Controller
{
    public function index()
    {
        if (Session::get('is_login')) {
            return redirect('/admin/dash');
        }
        return redirect('/');
    }

    /**
     * Isi session admin. Dipakai PortalLoginController (login satu pintu & pindah portal).
     */
    public function createSession($adminId, $email)
    {
        $dataAdmin = DB::connection('ifcaadm')
            ->table('mgr.administrator')
            ->where(array('id' => $adminId))
            ->get();

        Session::put('is_login', TRUE);
        Session::put('Tsuname', $dataAdmin[0]->name);
        Session::put('Tsemail', $email);
        Session::put('Tsuser_id', $dataAdmin[0]->id);

        // Nama & foto untuk header (all_login), supaya view tidak perlu query lagi.
        // Diperbarui oleh Admin\AccountController::updateprofile.
        $login = DB::connection('ifcaadm')->table('mgr.all_login')
            ->where('email', $email)
            ->where('tableforeign', 'administrator')
            ->first();
        Session::put('Tsdisplay_name', $login->name ?? $dataAdmin[0]->name);
        Session::put('Tspict', !empty($login->pict) ? $login->pict : '');

        LoginLog::record(LoginLog::ADMIN, $adminId, $email);
    }

    public function logout()
    {
        Session::flush();
        return redirect('/')->with('alert', __('admin/login.already_logout'));
    }
}
