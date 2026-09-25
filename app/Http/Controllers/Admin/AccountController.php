<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DefaultPassword;
use App\Support\LoginEmail;
use App\Support\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use DataTables;

class AccountController extends Controller
{
    public function getTable()
    {
        $query = DB::connection('ifcaadm')->select("SELECT ROW_NUMBER() OVER (ORDER BY t.id) AS [row_number], t.id, t.name, t.email, t.handphone, t.pict, t.tableforeign, t.idforeign FROM mgr.all_login t");
        return DataTables::of($query)->make(true);
    }
    public function getbyemail($email)
    {
        $data = DB::connection('ifcaadm')
            ->select("SELECT id, name, email, handphone, pict, tableforeign, idforeign from mgr.all_login where email = ?", [$email]);
        echo json_encode($data);
    }
    /**
     * Unggah foto profil ke images/user/ (dipanggil dari modal profil setelah foto dipotong).
     * Balasan JSON: status OK|Failed, pesan, url (absolut), picname.
     */
    public function savepic(Request $request)
    {
        $file = $request->file('userfile');

        if (!$file) {
            // $_FILES kosong: tidak ada file, atau melebihi post_max_size / upload_max_filesize
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/account.no_file_received')]);
        }
        if (!$file->isValid()) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/account.upload_error_detail', ['message' => $file->getErrorMessage()])]);
        }
        if ($file->getSize() > 5000000) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_max_size', ['size' => '5MB'])]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_only_image')]);
        }

        // nama unik supaya tidak menimpa file lain dan tidak kena cache browser
        $base    = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base    = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base) ?: 'profile';
        $picname = $base . '_' . date('YmdHis') . '.' . $ext;
        $target  = base_path('images/user');

        try {
            if (!is_dir($target)) {
                mkdir($target, 0775, true);
            }
            $file->move($target, $picname);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/account.upload_failed_detail', ['message' => $e->getMessage()])]);
        }

        return response()->json([
            'status'  => 'OK',
            'pesan'   => __('common.upload_done', ['name' => $picname]),
            'url'     => url('images/user/' . $picname),
            'picname' => $picname,
        ]);
    }
    public function updateprofile(Request $request)
    {
        $name       = $request->name;
        $telp       = $request->handphone;
        $images      = $request->labelimage;
        // akun yang diubah = akun yang sedang login (field email di form = email baru)
        $email      = (string) Session::get('Tsemail');
        if ($email === '') {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.error_occurred', ['message' => 'session'])], 403);
        }

        // Ganti email login: email baru harus valid & belum dipakai, dan password saat ini benar
        $newEmail = $request->has('email') ? LoginEmail::normalize($request->email) : LoginEmail::normalize($email);
        $emailChanged = $newEmail !== LoginEmail::normalize($email);
        if ($emailChanged) {
            $error = LoginEmail::validate($email, $newEmail);
            if ($error === null && trim((string) $request->current_password) === '') {
                $error = __('shared/account.password_required');
            }
            if ($error === null && !LoginEmail::passwordMatches('administrator', Session::get('Tsuser_id'), $request->current_password)) {
                $error = __('shared/account.password_wrong');
            }
            if ($error !== null) {
                return response()->json(['status' => 'Failed', 'pesan' => $error]);
            }
        }

        $data = array(
            'name' => $name,
            'handphone' => $telp,
        );

        // Foto: labelimage berisi nama file hasil savepic atau URL lama.
        // Kosong -> foto yang tersimpan tidak diubah (dulu tersimpan '.../images/user/' tanpa nama file).
        $images = trim((string) $images);
        $image  = null;
        if ($images !== '') {
            $image = filter_var($images, FILTER_VALIDATE_URL) ? $images : url('images/user/' . basename($images));
            $data['pict'] = $image;
        }
        $criteria = array('email' => $email);

        
        try { 
            
                DB::connection('ifcaadm')
                    ->table('mgr.all_login')
                    ->where($criteria)
                    ->update($data);

                // header memakai nilai dari session
                Session::put('Tsdisplay_name', $name);
                if ($image !== null) {
                    Session::put('Tspict', $image);
                }

                $msg = __('common.updated');
                if ($emailChanged) {
                    // semua akun email lama (all_login, tenant, bahasa) + session -> email baru
                    LoginEmail::change($email, $newEmail);
                    $msg = __('shared/account.email_changed', ['email' => $newEmail]);
                }
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
        $password = Password::make($request->password);
        $data = array(
            'password' => $password
        );
        // password akun yang sedang login (bukan email dari form, yang sekarang bisa diedit)
        $criteria = array('email' => (string) Session::get('Tsemail'));
        try { 
            
                DB::connection('ifcaadm')
                    ->table('mgr.all_login')
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
    public function resetpass(Request $request)
    {
        // dari tabel defaultpassword (menu System Spec -> Default Password)
        $password_default = DefaultPassword::get();
        $password = Password::make($password_default);
        try { 
            $criteria = array('email' => $request->email);
            $data = array(
                'password' => $password
            );
                DB::connection('ifcaadm')
                    ->table('mgr.all_login')
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
