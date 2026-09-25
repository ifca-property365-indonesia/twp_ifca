<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use DataTables;

class SurveyPublishController extends Controller
{
    public function getByID($id = '')
    {
        $where = array('publish_id' => $id);
        $data = DB::connection('ifcaadm')
            ->table('mgr.v_pmpublish_pmsurveyhd')
            ->where($where)
            ->get();
        echo json_encode($data);
    }
    public function getTable()
    {
        $query = DB::connection('ifcaadm')->select("SELECT ROW_NUMBER() OVER (ORDER BY t.publish_id) AS [row_number], t.* FROM mgr.v_pm_survey_publish t where t.flag_publish=0");
        return DataTables::of($query)->make(true);
    }
    public function getTable_publish()
    {
        $query = DB::connection('ifcaadm')->select("SELECT ROW_NUMBER() OVER (ORDER BY t.publishdate DESC) AS [row_number], t.* FROM mgr.v_pm_survey_publish t where t.flag_publish=1 order by publishdate desc");
        return DataTables::of($query)->make(true);
    }
    public function form(){
        $table = "SELECT subject,id from mgr.pm_tmpsurvey order by subject asc";
        $proDescs = DB::connection('ifcaadm')->select($table);
        
        $comboProject[]='';
        if(!empty($proDescs)) {
            $comboProject[] = '<option></option>';
            foreach ($proDescs as $dtProject) {
                $comboProject[] = '<option value="'.$dtProject->id.'">'.$dtProject->subject.'</option>';
            }
            $comboProject = implode("", $comboProject);
        }
        $content = array('ddsubject'=>$comboProject);
        return view('admin.survey.publish.form',$content);
    }
    public function save(Request $request){

        $form = $request->form;
        $title = $request->txttitle;
        $subject = $request->txtsubject;
        $publish_id = $request->publish_id;
    
        $audit_date = date('Y-m-d H:i:s');
        $audit_user = Session::get('Tsuser_id');
        try { 
            if ($form == 'add') {
                $dataPub = array(
                        'flag_publish'=>0,
                        'date_created'=>$audit_date,
                        'title' =>$title,
                        'audit_user'=>$audit_user,
                        'audit_date'=>$audit_date
                    );
                $publish_id = DB::connection('ifcaadm')
                    ->table('mgr.pm_survey_publish')
                    ->insertGetId($dataPub);
                $i=1;
                foreach ($subject as $surveyid) {
                    $wheretmp = array('id'=>$surveyid);
                    $datatmp = DB::connection('ifcaadm')
                        ->table('mgr.pm_tmpsurvey')
                        ->where($wheretmp)
                        ->get();
                    $dataHD = array(    
                        'publish_id' => $publish_id,
                        'quest_no'=>$i,
                        'subject'=>$datatmp[0]->subject,
                        'content'=>$datatmp[0]->content,
                        'tmpsurvey_id' => $surveyid,
                        'audit_user'=>$audit_user,
                        'audit_date'=>$audit_date
                    );
                    $survey_id = DB::connection('ifcaadm')
                    ->table('mgr.pm_survey_hd')
                    ->insertGetId($dataHD);//ambil survey id yg baru di insert di 
                    $wheretmp=array('tmpsurvey_id'=>$surveyid);//surveyid ambil dari foreach subject
                    $datatmpdtl = DB::connection('ifcaadm')
                        ->table('mgr.pm_tmpsurvey_dtl')
                        ->where($wheretmp)
                        ->get(); 
                    
                    foreach ($datatmpdtl as $key) {
                        $datadtl[] = array(
                            'publish_id' => $publish_id,
                            'survey_id'=>$survey_id,
                            'line_no'=>$key->line_no,
                            'options'=>$key->options,
                            'flag_remark'=>$key->flag_remark,
                            'audit_user'=>$audit_user,
                            'audit_date'=>$audit_date
                        );
                    }//end looping detail
                    DB::connection('ifcaadm')
                    ->table('mgr.pm_survey_dt')
                    ->insert($datadtl);
                    $datadtl = [];
                
                    $i++;
                }//END OF FOREACH SUBJECT
                $msg = __('common.saved');
                $st = 'OK';
           } else { //--proses update
                $where=array('id'=>$publish_id);
                $dataPub = array(
                        'title' =>$title,
                        'audit_user'=>$audit_user,
                        'audit_date'=>$audit_date
                    );
                DB::connection('ifcaadm')
                    ->table('mgr.pm_survey_publish')
                    ->where($where)
                    ->update($dataPub);
                $wheredlt=array('publish_id'=>$publish_id);
                DB::connection('ifcaadm')->table('mgr.pm_survey_hd')->where($wheredlt)->delete();
                DB::connection('ifcaadm')->table('mgr.pm_survey_dt')->where($wheredlt)->delete();
     
                    $i=1;
                    foreach ($subject as $surveyid) {
                        $wheretmp=array('id'=>$surveyid);
                        $datatmp = DB::connection('ifcaadm')
                        ->table('mgr.pm_tmpsurvey')
                        ->where($wheretmp)
                        ->get(); 
                        $dataHD = array(
                            'publish_id' => $publish_id,
                            'quest_no'=>$i,
                            'subject'=>$datatmp[0]->subject,
                            'content'=>$datatmp[0]->content,
                            'tmpsurvey_id' => $surveyid,
                            'audit_user'=>$audit_user,
                            'audit_date'=>$audit_date
                        );
                        $survey_id = DB::connection('ifcaadm')
                            ->table('mgr.pm_survey_hd')
                            ->insertGetId($dataHD);//ambil survey id yg baru di insert di 
                           
                        $wheretmp=array('tmpsurvey_id'=>$surveyid);//surveyid ambil dari foreach subject
                        $datatmpdtl = DB::connection('ifcaadm')
                        ->table('mgr.pm_tmpsurvey_dtl')
                        ->where($wheretmp)
                        ->get(); 
                            
                        foreach ($datatmpdtl as $key) {
                            $datadtl[] = array(
                                'publish_id' => $publish_id,
                                'survey_id'=>$survey_id,
                                'line_no'=>$key->line_no,
                                'options'=>$key->options,
                                'flag_remark'=>$key->flag_remark,
                                'audit_user'=>$audit_user,
                                'audit_date'=>$audit_date
                            );
                        }//end looping detail
                        DB::connection('ifcaadm')
                            ->table('mgr.pm_survey_dt')
                            ->insert($datadtl);
                        
                        $datadtl = [];
                    
                        $i++;
                }//END OF FOREACH SUBJECT
                $msg = __('common.updated');
                $st = 'OK';
            
            }//end else update

        } catch(\Illuminate\Database\QueryException $ex){ 
            $msg = __('common.save_failed', ['message' => $ex->getMessage()]);
            $st  = 'Failed';
        }
        return response()->json([
            'status' => $st,
            'pesan' => $msg
        ]);   

    }
    public function savepublish(Request $request)
    {

        $form = $request->form;
        $publish_id = $request->publish_id;
        $publish = $request->publishDate;
        $expired = $request->ExpiredDate;
        $audit_date = date('Y-m-d H:i:s');
        $audit_user = Session::get('Tsuser_id');

        $publish = date('Y-m-d', strtotime($publish));
        $expired = date('Y-m-d', strtotime($expired));
        $where=array('id'=>$publish_id);
        $dataPub = array(
                'flag_publish' => 1,
                'publishdate' => $publish,
                'expireddate' => $expired,
                'audit_user'=>$audit_user,
                'audit_date'=>$audit_date
            );
        try { 
            DB::connection('ifcaadm')
                    ->table('mgr.pm_survey_publish')
                    ->where($where)
                    ->update($dataPub);
            $msg = __('common.saved');
            $st  = 'OK';
        } catch(\Illuminate\Database\QueryException $ex){ 
            $msg = __('admin/survey.publish_failed', ['message' => $ex->getMessage()]);
            $st  = 'Fail';
        }
        return response()->json([
            'status' => $st,
            'pesan' => $msg
        ]);

    }
    public function delete(Request $request)
    {
       
        $criteriapub = array('id' => $request->publish_id);
        $criteriasur = array('publish_id' => $request->publish_id);
        try { 
            DB::connection('ifcaadm')
            ->table('mgr.pm_survey_publish')
            ->where($criteriapub)
            ->delete();
            DB::connection('ifcaadm')
            ->table('mgr.pm_survey_hd')
            ->where($criteriasur)
            ->delete();
            DB::connection('ifcaadm')
            ->table('mgr.pm_survey_dt')
            ->where($criteriasur)
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
