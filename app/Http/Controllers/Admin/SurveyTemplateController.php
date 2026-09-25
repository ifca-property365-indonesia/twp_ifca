<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use DataTables;

class SurveyTemplateController extends Controller
{
    public function getTable()
    {
        $query = DB::connection('ifcaadm')->select("SELECT ROW_NUMBER() OVER (ORDER BY t.tmpsurvey_id) AS [row_number], t.* FROM mgr.v_tmpsurvey t");
        return DataTables::of($query)->make(true);
    }
    public function getByID($id = '')
    {
        $where = array('tmpsurvey_id' => $id);
        $data = DB::connection('ifcaadm')
            ->table('mgr.v_pm_tmpsurvey_all')
            ->where($where)
            ->get();
        echo json_encode($data);
    }
    public function save(Request $request){

        $msg="";
    
            $tmpsurvey_id = $request->survey_id;
            $form = $request->form;
            $subject = $request->txtsubject;
            $content = $request->txtquestion;
            $options =$request->txtopt_value;
            $remark = $request->remark;
            $remark_val = $request->remark_val;
      
            $audit_date = date('Y-m-d H:i:s');
            $audit_user = Session::get('Tsuser_id');
            $batasLoop = count((array) $options);
            $datadtl = [];

        $datahdr = array(
            'subject'=>$subject,
            'content'=>$content,
            'date_created'=>$audit_date,
            'audit_user'=>$audit_user,
            'audit_date'=>$audit_date
        );
        try { 
            if ($form!='add') { //update
                $criteriahd = array('id' => $tmpsurvey_id);
                
                DB::connection('ifcaadm')
                    ->table('mgr.pm_tmpsurvey')
                    ->where($criteriahd)
                    ->update($datahdr);
                $criteriadt = array('tmpsurvey_id' => $tmpsurvey_id);
                DB::connection('ifcaadm')
                ->table('mgr.pm_tmpsurvey_dtl')
                ->where($criteriadt)
                ->delete();
                for($i=0; $i < $batasLoop; $i++){
                    $datadtl[]=array(
                        'tmpsurvey_id'=>$tmpsurvey_id,
                        'options'=>$options[$i],
                        'line_no'=>$i+1,
                        'flag_remark'=>$remark_val[$i],
                        'audit_user'=>$audit_user,
                        'audit_date'=>$audit_date
                    );
                }
                DB::connection('ifcaadm')
                    ->table('mgr.pm_tmpsurvey_dtl')
                    ->insert($datadtl);
                $msg = __('common.updated');
                $st = 'OK';
            } else {//create
                
                $survey_id = DB::connection('ifcaadm')
                    ->table('mgr.pm_tmpsurvey')
                    ->insertGetId($datahdr);
                    for ($i=0; $i < $batasLoop; $i++) {
                        $datadtl[] = array(
                            'tmpsurvey_id'=>$survey_id,
                            'line_no'=>$i+1,
                            'options'=>$options[$i],
                            'flag_remark'=>$remark_val[$i],
                            'audit_user'=>$audit_user,
                            'audit_date'=>$audit_date
                        );
                    }//end looping detail
                DB::connection('ifcaadm')
                    ->table('mgr.pm_tmpsurvey_dtl')
                    ->insert($datadtl);
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
       
        $criteriahd = array('id' => $request->id);
        $criteriadt = array('tmpsurvey_id' => $request->id);
        try { 
            DB::connection('ifcaadm')
            ->table('mgr.pm_tmpsurvey')
            ->where($criteriahd)
            ->delete();
            DB::connection('ifcaadm')
            ->table('mgr.pm_tmpsurvey_dtl')
            ->where($criteriadt)
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
