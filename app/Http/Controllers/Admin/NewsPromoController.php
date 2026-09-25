<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NewsPicture;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;

class NewsPromoController extends Controller
{
    public function getTable()
    {
        $query = DB::connection('ifcaadm')->select("SELECT ROW_NUMBER() OVER (ORDER BY t.id) AS [row_number], t.* FROM mgr.newsfeed t");
        foreach ($query as $row) {
            $row->picture_url = $row->attach_type === 'P' ? NewsPicture::url($row->picture) : null;
        }
        return DataTables::of($query)->make(true);
    }
    public function addform($type='',$id=0)
    {
        if($type == 'A')
        {
            $jdl  = __('admin/news.add_title');
            $form ='add';
        } else if($type == 'E') {
            $jdl  = __('admin/news.edit_title');
            $form ='edit';
        }

        $content = array(
            'id' => $id,
            'jdl'=> $jdl,
            'form'=>$form
        );
        return view('admin.news.add', $content);
    }
    /**
     * Unggah gambar berita (dipanggil form saat Simpan, sebelum save()).
     * Mengembalikan path relatif (disimpan ke newsfeed.picture) dan URL untuk preview.
     */
    public function savePic(Request $request)
    {
        $file = $request->file('userfile');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_error')]);
        }
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif'], true)
            || !str_starts_with((string) $file->getMimeType(), 'image/')) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_only_image')]);
        }
        if ($file->getSize() > 5000000) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_max_size', ['size' => '5MB'])]);
        }

        try {
            $path = NewsPicture::store($file);
        } catch (\Exception $e) {
            return response()->json(['status' => 'Failed', 'pesan' => __('common.upload_error')]);
        }

        return response()->json([
            'status' => 'OK',
            'pesan' => __('common.upload_done', ['name' => $file->getClientOriginalName()]),
            'path' => $path,
            'url' => NewsPicture::url($path),
        ]);
    }
    public function getByID($id = '')
    {
        $where = array('id' => $id);
        $data = DB::connection('ifcaadm')
            ->table('mgr.newsfeed')
            ->where($where)
            ->get();
        foreach ($data as $row) {
            $row->picture_url = NewsPicture::url($row->picture);
        }
        echo json_encode($data);
    }
    public function save(Request $request)
    {
        $msg            = '';
        $id             = $request->id;
        $start_date = explode('/', $request->start_date);
        $start_date = $start_date[2].'-'.$start_date[1].'-'.$start_date[0].' 00:00:00';

        $end_date = explode('/', $request->end_date);
        $end_date = $end_date[2].'-'.$end_date[1].'-'.$end_date[0].' 23:59:59';
        $data = array(
            'content_type'         => $request->content_type,
            'subject'  => $request->news_title,
            'content'   => $request->news_descs,
            'status'   => 1,
            'attach_type' => $request->attach_type,
            'youtube_link' => $request->youtubelink,
            'picture'     => $request->picturepath,
            'date_created'    => date('Y-m-d H:i:s'),
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        $criteria = array('id' => $id);
        try { 
            if ($id > 0) { //update
                unset($data['date_created']);
                DB::connection('ifcaadm')
                    ->table('mgr.newsfeed')
                    ->where($criteria)
                    ->update($data);
                
                $msg = __('common.updated');
                $st  = 'OK';
                
            } else {//create
               
                DB::connection('ifcaadm')
                    ->table('mgr.newsfeed')
                    ->insert($data);
                
                $msg = __('common.saved');
                $st = 'OK';
                
                
            }
        } catch(\Illuminate\Database\QueryException $ex){ 
            $msg = __('common.save_failed', ['message' => $ex->getMessage()]);
            $st  = 'Failed';
        }
        return response()->json([
            'status' => $st,
            'pesan' => $msg
        ]);
    }
    public function delete(Request $request)
    {
       
        $criteria = array('id' => $request->id);
        try { 
            DB::connection('ifcaadm')
            ->table('mgr.newsfeed')
            ->where($criteria)
            ->delete();
            $msg = __('common.deleted');
            $st  = 'OK';
        } catch(\Illuminate\Database\QueryException $ex){ 
            $msg = __('common.delete_failed', ['message' => $ex->getMessage()]);
            $st  = 'Fail';
        }
        return response()->json([
            'status' => $st,
            'pesan' => $msg
        ]);
    }
}
