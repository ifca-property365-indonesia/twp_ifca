<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Password;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AccountController extends Controller
{
    /**
     * Profil akun yang sedang login. Semua aksi di controller ini memakai email session
     * (Tenemail), bukan email dari URL / form, jadi akun lain tidak bisa dibaca atau diubah.
     */
    private function sessionEmail()
    {
        return (string) Session::get('Tenemail');
    }

    private function forbidden()
    {
        return response()->json(['status' => 'Failed', 'pesan' => __('common.error_occurred', ['message' => 'session'])], 403);
    }

    public function getbyemail($email = null)
    {
        $email = $this->sessionEmail();
        if ($email === '') {
            return $this->forbidden();
        }
        // tanpa kolom password
        $data = DB::select("SELECT id, name, email, handphone, pict, tableforeign, idforeign from mgr.all_login where email = ?", [$email]);
        // contact_name dari tabel tenant (business yang sedang dibuka, kalau emailnya sama)
        $tenant = DB::table('mgr.tenant')->where('email', $email)
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) Session::get('Tuser_id')])
            ->first(['contact_name']);
        foreach ($data as $row) {
            $row->contact_name = $tenant->contact_name ?? null;
        }
        echo json_encode($data);
    }
    /**
     * Unggah foto profil ke img/user/ (dipanggil dari modal profil setelah foto dipotong).
     * Balasan JSON: status OK|Failed, pesan, url (absolut), picname.
     */
    public function savepic(Request $request)
    {
        $file = $request->file('userfile');

        if (!$file) {
            // $_FILES kosong: tidak ada file, atau melebihi post_max_size / upload_max_filesize
            return response()->json(['status' => 'Failed', 'pesan' => __('tenant/account.no_file')]);
        }
        if (!$file->isValid()) {
            return response()->json(['status' => 'Failed', 'pesan' => __('tenant/account.upload_error', ['message' => $file->getErrorMessage()])]);
        }
        if ($file->getSize() > 5000000) {
            return response()->json(['status' => 'Failed', 'pesan' => __('tenant/account.max_size_server')]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_only_image')]);
        }

        // nama unik supaya tidak menimpa file lain dan tidak kena cache browser
        $base    = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base    = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base) ?: 'profile';
        $picname = $base . '_' . date('YmdHis') . '.' . $ext;
        $target  = base_path('img/user');

        try {
            if (!is_dir($target)) {
                mkdir($target, 0775, true);
            }
            $file->move($target, $picname);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'Failed', 'pesan' => __('tenant/account.upload_failed', ['message' => $e->getMessage()])]);
        }

        return response()->json([
            'status'  => 'OK',
            'pesan'   => __('common.upload_done', ['name' => $picname]),
            'url'     => url('img/user/' . $picname),
            'picname' => $picname,
        ]);
    }
    public function updateprofile(Request $request)
    {
        $name       = $request->name;
        $telp       = $request->handphone;
        $images      = $request->labelimage;
        $email      = $this->sessionEmail();
        if ($email === '') {
            return $this->forbidden();
        }

        $data = array(
            'name' => $name,
            'handphone' => $telp,
        );

        // Foto: labelimage berisi nama file hasil savepic atau URL lama.
        // Kosong -> foto yang tersimpan tidak diubah (dulu tersimpan '.../img/user/' tanpa nama file).
        $images = trim((string) $images);
        $image  = null;
        if ($images !== '') {
            $image = filter_var($images, FILTER_VALIDATE_URL) ? $images : url('img/user/' . basename($images));
            $data['pict'] = $image;
        }
        $criteria = array('email' => $email);

        
        try { 
            
                DB::table('mgr.all_login')
                    ->where($criteria)
                    ->update($data);

                // Contact name -> tabel tenant, semua baris dengan email yang sedang login
                $contact = trim((string) $request->contact_name);
                if ($request->has('contact_name')) {
                    DB::table('mgr.tenant')
                        ->where('email', $email)
                        ->update(['contact_name' => $contact === '' ? null : $contact]);
                    Session::put('Tuname', $contact);
                }

                // header memakai nilai dari session
                Session::put('Tdisplay_name', $name);
                if ($image !== null) {
                    Session::put('Tpict', $image);
                }
                
                $msg = __('common.updated');
                $st  = 'OK';
             
        } catch(\Illuminate\Database\QueryException $ex){ 
            $msg = __('common.save_failed', ['message' => $ex->getMessage()]);
            $st  = 'Failed';
        }
        return response()->json([
            'status' => $st,
            'pesan' => $msg
        ]);
    }
    public function changepass(Request $request)
    {
        $email = $this->sessionEmail();
        if ($email === '') {
            return $this->forbidden();
        }
        if (trim((string) $request->password) === '') {
            return response()->json(['status' => 'Failed', 'pesan' => __('shared/plugins.validate.required')]);
        }
        $password = Password::make($request->password);
        $data = array(
            'password' => $password
        );
        $criteria = array('email' => $email);

        try { 
            
                DB::table('mgr.all_login')
                    ->where($criteria)
                    ->update($data);
                
                $msg = __('common.updated');
                $st  = 'OK';
             
        } catch(\Illuminate\Database\QueryException $ex){ 
            $msg = __('common.save_failed', ['message' => $ex->getMessage()]);
            $st  = 'Failed';
        }
        return response()->json([
            'status' => $st,
            'pesan' => $msg
        ]);
    }
}
