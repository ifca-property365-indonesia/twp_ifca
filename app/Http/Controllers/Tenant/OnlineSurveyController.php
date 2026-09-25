<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class OnlineSurveyController extends Controller
{
	public function index()
    {    
        $business_no = Session::get('business_no');

        // Ambil semua publish aktif
        $dataPub = DB::table('mgr.pm_survey_publish')
            ->where('publishdate', '<=', date("Y-m-d"))
            ->where('expireddate', '>=', date("Y-m-d"))
            ->where('flag_publish', 1)
            ->get();

        $lsP = '';
        $cnR = 0;     

        // 🔥 Ambil semua publish_id yang SUDAH dijawab user (sekali query saja)
        $answered = DB::table('mgr.pm_survey_respon')
            ->whereIn('user_id', TenantScope::businessNos())
            ->pluck('publish_id')
            ->toArray();

        if (!empty($dataPub)) {
            foreach ($dataPub as $publish) 
            {
                $id = $publish->id;

                // ❌ Kalau sudah pernah isi → skip
                if (in_array($id, $answered)) {
                    continue;
                }

                // ✅ Kalau belum isi → tampilkan
                $lsP .= '<h5 class="fw-bold text-primary border-bottom pb-2 mb-3">'.e($publish->title).'</h5>';

                $dataSur = DB::table('mgr.pm_survey_hd')
                    ->where('publish_id', $publish->id)
                    ->get();

                if (!empty($dataSur))
                {
                    $lsP .= '<form role="form" method="post" name="f'.$publish->id.'" action="" id="frm'.$publish->id.'">';

                    foreach ($dataSur as $k => $survey) {

                        $lsP .= '<div class="mb-4"><div class="form-label fs-6">'.$survey->content.'</div>';
                        $lsP .= '<input type="hidden" name="s[]" value="'.$survey->id.'">';

                        $dataOpt = DB::table('mgr.pm_survey_dt')
                            ->where('survey_id', $survey->id)
                            ->get();

                        if (!empty($dataOpt))
                        {
                            foreach ($dataOpt as $option) {

                                $lsP .= '<div class="form-check mb-2"><label class="form-check-label">';
                                $lsP .= '<input type="radio" class="form-check-input" name="oR['.$k.']" data-ada="true" value="'.$option->line_no.'"/> ';
                                $lsP .= ' '.e($option->options).'</label>';

                                if ($option->flag_remark == 1) {
                                    $lsP .= '<textarea class="form-control form-control-sm mt-2" rows="2" name="remarks" placeholder="'.e(__('common.remarks')).'"></textarea>';
                                }

                                $lsP .= '</div>';
                            }

                            $lsP .= '</div>';
                        }
                    }

                    $lsP .= '<input name="id" type="hidden" value="'.$publish->id.'"/>';
                    $lsP .= '<input name="q" type="hidden" value="'.$k.'"/>';

                    $lsP .= '<div class="text-end mt-3">';
                    $lsP .= '<button type="button" id="btnSave'.$publish->id.'" data-p="'.$publish->id.'" data-q="'.$k.'" class="btn btn-primary">'.e(__('common.submit')).'</button>';
                    $lsP .= '</div></form>';
                }
            }
        }

        $content = array(
            'dP' => $lsP,
            'cS' => $cnR,
        );

        return view('tenant.online_survey.index', $content);
    }

    public function save(Request $request)
    {
        $msg = "";
        $business_no = Session::get('business_no');
        $email = Session::get('Tenemail');
        $idP = $request->id;
        $q = $request->q;

        for ($i=0; $i <= $q; $i++)
        {
            $data = array(
                'publish_id' => $idP,
                'survey_id' => $request->s[$i],
                'respon' => $request->oR[$i],
                'user_id' => $business_no,
                'email_addr' => $email,
                'remark' => $request->remarks ?? null,
                'date_created' => date('Y-m-d H:i:s'),
                'audit_user' => '',
                'audit_date' => date('Y-m-d H:i:s')
            );

            $query = DB::table('mgr.pm_survey_respon')
                ->insert($data);
            if ($query != "OK") {
                $msg = $query;
                $st = 'Fail';
            } else {
                $msg = __('common.saved');
                $st = 'OK';
            }
        }

        $callback = array(
            "pesan" => $msg,
            "status" => $st
        );
        echo json_encode($callback);
    }
}