<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;
use PDF;

class SurveyResultController extends Controller
{
    public function getTable()
    {
        $query = DB::connection('ifcaadm')->select("SELECT ROW_NUMBER() OVER (ORDER BY t.publishdate DESC) AS [row_number], t.* FROM mgr.v_pm_survey_publish t where t.flag_publish=1 order by publishdate desc");
        return DataTables::of($query)->make(true);
    }
    public function viewresult($publish=''){
        if (!DB::connection('ifcaadm')->table('mgr.pm_survey_publish')->where('id', (int) $publish)->exists()) {
            abort(404);
        }
       
        $sql = "SELECT DISTINCT
                c.id AS publish_id,
                c.title AS title,
                a.content AS content,
                b.options AS options,
                b.flag_remark AS flag_remark,
                c.expireddate AS expireddate,
                c.publishdate AS publishdate,
                a.quest_no AS quest_no,
                b.line_no AS line_no,
                d.email_addr AS email_addr,
                e.name AS company_name,
                d.date_created AS date_created,
                (
                    SELECT COUNT(1)
                    FROM mgr.pm_survey_respon d2
                    WHERE d2.survey_id = b.survey_id
                    AND d2.respon = b.line_no
                ) AS jumlah
            FROM mgr.pm_survey_hd a
            JOIN mgr.pm_survey_dt b ON a.id = b.survey_id
            JOIN mgr.pm_survey_publish c ON a.publish_id = c.id
            LEFT JOIN mgr.pm_survey_respon d 
                   ON b.survey_id = d.survey_id
                  AND d.respon = b.line_no
            LEFT JOIN mgr.all_login e 
                   ON d.email_addr = e.email
            Where a.publish_id = ?
            ORDER BY c.id, a.quest_no, b.line_no, d.date_created";
        $result1 = DB::connection('ifcaadm')->select($sql, [$publish]);

        $sqlLine = "SELECT (SELECT COUNT(publish_id) FROM mgr.pm_survey_respon i WHERE i.publish_id = j.id) AS cnt FROM mgr.pm_survey_publish j where id = ?";
        $result3 = DB::connection('ifcaadm')->select($sqlLine, [$publish]);

        $content = array(
                         'dtsurvey'=>$result1,
                         'id'=>$publish,
                         'Responden'=>$result3
                     );
        return view('admin.survey.result.view',$content);
    }
    function generatepdf(Request $request)
    {
        $publish = $request->id;
        $sql = "SELECT * FROM mgr.v_pm_survey_result where publish_id = ? ORDER BY publish_id ASC";
        $result1 = DB::connection('ifcaadm')->select($sql, [$publish]);

        $sqlLine = "SELECT (SELECT COUNT(DISTINCT user_id) FROM mgr.pm_survey_respon i WHERE i.publish_id = j.id) AS cnt FROM mgr.pm_survey_publish j where id = ?";
        $result3 = DB::connection('ifcaadm')->select($sqlLine, [$publish]);
        if(!empty($result1)){
            $content = array(
                'dtsurvey'=>$result1,
                'Responden'=>$result3
            );
            $pdfname="survey_result";
            $pdf = PDF::loadView('admin.survey.result.pdfview', $content);
            return $pdf->stream($pdfname.'.pdf');
        }else{
            return abort(404);
        }
    }
}
