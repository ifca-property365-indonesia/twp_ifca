<?php

namespace App\Http\Middleware;

use App\Support\AdminUsers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Proteksi halaman portal Admin (dulu: webadmin AuthCheck).
 * Login admin menyimpan flag session 'is_login'.
 * Grup admin terbatas (config admin_users.restricted_menus, mis. executive_management -> Financials)
 * hanya boleh membuka menunya: Dashboard dialihkan ke menu pertamanya, URL lain 403.
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::get('is_login')) {
            return redirect('/')->with('alert', __('admin/login.login_first'));
        }

        if (!AdminUsers::allows($request->path())) {
            $path = trim($request->path(), '/');
            if ($request->isMethod('GET') && in_array($path, ['admin', 'admin/dash'], true)) {
                return redirect(AdminUsers::homePath());
            }
            abort(403);
        }

        return $next($request);
    }
}