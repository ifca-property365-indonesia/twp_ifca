<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SysSpecController extends Controller
{
    public function index()
    {
        $image1 = url('images/slides/pb-1.jpg');
        $image2 = url('images/slides/pb-2.jpg');
        $image3 = url('images/slides/pb-3.png');
        $image4 = url('images/slides/pb-1.jpg');
        $image5 = url('images/slides/pb-2.jpg');
        $image6 = url('images/slides/pb-3.png');
        
        $dtimg = DB::connection('ifcaadm')->select("SELECT * from mgr.image_login"); 
        if(!empty($dtimg)){
            foreach ($dtimg as $key) {
                if($key->webname=='admin'){
                    if($key->seq_no=='1'){
                        if(!empty($key->image_url)){
                            $image1 = $key->image_url;
                        }
                    }
                    if($key->seq_no=='2'){
                        if(!empty($key->image_url)){
                            $image2 = $key->image_url;
                        }
                    }
                    if($key->seq_no=='3'){
                        if(!empty($key->image_url)){
                            $image3 = $key->image_url;
                        }
                    }
                }
                if($key->webname=='tenant'){
                    if($key->seq_no=='4'){
                        if(!empty($key->image_url)){
                            $image4 = $key->image_url;
                        }
                    }
                    if($key->seq_no=='5'){
                        if(!empty($key->image_url)){
                            $image5 = $key->image_url;
                        }
                    }
                    if($key->seq_no=='6'){
                        if(!empty($key->image_url)){
                            $image6 = $key->image_url;
                        }
                    }
                }
            }
        }   
        $content = array(
            'image1'=>$image1,
            'image2'=>$image2,
            'image3'=>$image3,
            'image4'=>$image4,
            'image5'=>$image5,
            'image6'=>$image6
        );
        
        return view('admin.sysspec.index',$content);
    }
    public function imgLogin(Request $request)
    {
        // web & seq dipakai sebagai nama folder / file: hanya nilai yang dikenal
        $web = in_array($request->web, ['admin', 'tenant'], true) ? $request->web : null;
        $seq = (int) $request->seq;
        $url = '';
        $descs = '';

        $files = $_FILES;
        $picture = !empty($_FILES) ? $picture = $_FILES["imglogin"] : '';
        if ($web && $seq >= 1 && $seq <= 6 && !empty($picture["name"])) {
            $tipegambar = strtolower(pathinfo($_FILES["imglogin"]["name"], PATHINFO_EXTENSION));
            // nama tetap per slot (images/slides/{web}/login-{seq}.{ext}), tidak bergantung
            // pada nama gambar lama yang bisa berupa URL server lain atau kosong
            $picname = 'login-' . $seq . '.' . $tipegambar;

            $psn = '';
            $msg = '';
            $picture = array_filter($picture);

            // path absolut di folder proyek; dulu './images/slides/..' relatif terhadap folder
            // kerja PHP dan mkdir() tidak rekursif -> gagal kalau images/slides belum ada
            $relative_dir = 'images/slides/' . $web . '/';
            $target_dir = base_path($relative_dir);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0775, true);
            }
            $target_file = $target_dir . $picname;
            $uploadOk = 1;
            $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

            if ($_FILES["imglogin"]["size"] > 5000000) {
                $msg = __('common.upload_max_size', ['size' => '5MB']);
                $uploadOk = 0;
                $psn = 'failed';
                $res = array("pesan" => $msg, "status" => $psn);

                echo json_encode($res);
                exit();
            }

            $imageFileType = strtolower($imageFileType);
            // Allow certain file formats
            if (
                $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif" && $imageFileType != "JPG"
            ) {
                $msg = __('common.upload_only_image');
                $uploadOk = 0;
                $psn = 'failed';
                $res = array("pesan" => $msg, "status" => $psn);

                echo json_encode($res);
                exit();
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                $msg = __('common.upload_not_saved');
                $psn = "Failed";
                // if everything is ok, try to upload file
            } else {

                if (move_uploaded_file($_FILES["imglogin"]["tmp_name"], $target_file)) {
                    
                    $descs = $relative_dir . $picname;
                    // ?v= supaya browser tidak menampilkan gambar lama dari cache (nama file tetap)
                    $url = url($descs) . '?v=' . time();
                    try{
                        $dataup = array(
                            'seq_no' => $seq,
                            'image_url' => $url,
                            'webname'=> $web
                        );
                        $where = array('seq_no' => $seq, 'webname' => $web);
                        $cek = DB::connection('ifcaadm')
                            ->table('mgr.image_login')
                            ->where($where)
                            ->get();
                        if(count($cek)>0){
                            DB::connection('ifcaadm')
                            ->table('mgr.image_login')
                            ->where($where)
                            ->update($dataup);
                        }else{
                            DB::connection('ifcaadm')
                            ->table('mgr.image_login')
                            ->insert($dataup);
                        }
                        $msg = __('admin/sysspec.image_changed');
                        $psn = "OK";
                    } catch(\Illuminate\Database\QueryException $ex){ 
                        $msg = __('common.save_failed', ['message' => $ex->getMessage()]);
                        $psn  = 'Failed';
                    }
                    
                    
                } else {
                    $msg = __('common.upload_error');
                    $psn = "Failed";
                }
            }
        } else {
            $msg = __('common.upload_error');
            $psn = "Failed";
        }

        $res = array(
            'pesan' => $msg,
            'status' => $psn,
            'url' => $url,
            'picname' => $descs,
        );
        echo json_encode($res);
    }

    public function defaultpass(Request $request)
    {
        $data = DB::connection('ifcaadm')->table('mgr.defaultpassword')
            ->select('password')
            ->first();
        return view('admin.sysspec.defaultpass', compact('data'));
    }

    public function defaultpasssave(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);

        DB::connection('ifcaadm')->table('mgr.defaultpassword')->update([
            'password' => $request->password,
            'audit_date' => Carbon::today()
        ]);

        return response()->json([
            'success' => true,
            'message' => __('admin/sysspec.updated')
        ]);
    }
}
