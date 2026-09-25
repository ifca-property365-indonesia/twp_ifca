<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PDF;

class BillingOutstandingController extends Controller
{
    public function index()
    {
        $tenant_no = Session::get('tenant_df');
        $id_tenant = Session::get('Tuser_id');
        $crit = array('tenant_no' => $tenant_no,'id_tenant' =>$id_tenant);

        // Combo Lot No
        $cbLot = $this->getLotNo($tenant_no);

        // Billing Outstanding
        $business_no = Session::get('business_no');
        $criteria = array(
            'business_no'=>$business_no, 
            'tenant_no'=>$tenant_no
        );
        $dataTenancy = DB::table('mgr.pm_tenancy')
            ->where($criteria)
            ->get();
        $list_bill = '';
        $footer_bill = '';
        $totalOutstanding = '';
        $i = 1;
        $statusPembayaran = true;

        if (!empty($dataTenancy)) {
            $entity = $dataTenancy[0]->entity_cd;
            $project = $dataTenancy[0]->project_no;
            // total per mata uang (currency_cd dari ar_ledger, mis. IDR / USD)
            $sumBilling = array();

            $dataBilling = $this->get_soa_by_tenant($entity,$project,$tenant_no);
            if (!empty($dataBilling)) {
                foreach ($dataBilling as $billing) {
                    $list_bill .= '<tr class="odd">';
                    $list_bill .= '<td>'. $i.'</td>';
                    $list_bill .= '<td>'. $billing->doc_no.'</td>';
                    $list_bill .= '<td>'. date("d M Y", strtotime($billing->doc_date)).'</td>';
                    $list_bill .= '<td>'. date("d M Y", strtotime($billing->due_date)).'</td>';
                    $list_bill .= '<td>'. $billing->ar_ldg_desc.'</td>';

                    if(empty($billing->start_date) || empty($billing->end_date)){
                        $dtPer = ' - ';
                    } else {
                        $dtPer = date("d M Y", strtotime($billing->start_date)).' - '.date("d M Y", strtotime($billing->end_date));
                    }

                    $list_bill .= '<td>'. $dtPer .'</td>';
                    $list_bill .= '<td>'. $billing->currency_cd.'</td>';
                    $list_bill .= '<td align="right">'. number_format($billing->fbal_amt,2,",",".").'</td>';
                    $list_bill .= '</tr>';

                    $currency = strtoupper(trim((string) $billing->currency_cd));
                    $sumBilling[$currency] = ($sumBilling[$currency] ?? 0) + $billing->fbal_amt;
                    $i++;
                }

                $first = true;
                foreach ($sumBilling as $currency => $amount) {
                    if ($amount == 0) {
                        continue;
                    }
                    $amountText = number_format($amount, 2, ",", ".");
                    $totalOutstanding .= ($totalOutstanding !== '' ? ' | ' : '') . e($currency) . ' ' . $amountText;
                    $label = $first ? '<b>' . e(__('tenant/billing.total')) . '</b>' : '';
                    $footer_bill .= '<tr><td colspan="6" align="center">' . $label . '</td><td><b><span>' . e($currency) . '</span></b></td><td align="right"><b><span>' . $amountText . '</span></b></td></tr>';
                    $first = false;
                }
                $statusPembayaran = false;
            }
        } else {
            $statusPembayaran = true;
        }

        // Our Latest Ticket
        $flagsurvey = 1;
        $list_hticket = "";
        $i = 1;

        $htenants = DB::table('mgr.sv_entry_multi')
            ->whereIn('tenant_no', TenantScope::tenantNos())
            ->whereIn('id_tenant', TenantScope::tenantIds())
            ->where('status','<>','C')->get();
        if (!empty($htenants)) {
            foreach ($htenants as $tenant)
            {
                $list_hticket .= '<tr class="odd">';
                $list_hticket .= '<td>'.$i.'</td>';
                $list_hticket .= '<td>'.$tenant->complain_no.'</td>';
                $crit = array('category_cd' => $tenant->category_cd);
                $data_category = DB::connection('dblive')
                    ->table('mgr.sv_category')
                    ->where($crit)
                    ->get();

                if(empty($data_category)) {
                    $list_hticket .= '<td>'.$tenant->category_cd.'</td>';
                } else {
                    foreach ($data_category as $datacate) {
                        if ($datacate->descs == null) {
                            $list_hticket .= '<td></td>';
                        } else {
                            $list_hticket .= '<td>'.$datacate->descs.'</td>';
                        }
                    }
                }

                $list_hticket .= '<td>'.$tenant->work_requested.'</td>';
                $list_hticket .= '<td>'.date("d M Y", strtotime($tenant->reported_date)).'</td>';
                $list_hticket .= '<td>'.$tenant->serv_req_by.'</td>';
                $list_hticket .= '<td>'.$tenant->lot_no.'</td>';

                $data_status = $this->get_statusIFCA($tenant->status);
                $list_hticket .= '<td><span class="badge '.$data_status["color"].'">'.$data_status["status"]. '</span></td>';

                if($tenant->status=='R') {
                    $list_hticket .= '<td><button class="btn btn-warning btn-sm w-100" onclick="location.href=\''. url('tenant/ticket').'/'.$tenant->id.'/'.'edit'.'\'"> '.e(__('common.edit')).'</button></td>';
                } else {
                    $list_hticket .= '<td></td>'."\n";
                }
                $list_hticket .= '</tr>';
                $i++;
            }
        }

        // Our Latest Overtime
        $today = date('Y-m-d h:i:s');
        $i = 1;
        $list_hovertime = "";

        $hovertime = DB::select("SELECT * from mgr.ot_trx where " . TenantScope::sqlTenantId('id_tenant') . " AND status NOT IN ('X','Y','Z')");
        if (!empty($hovertime)) {
            foreach ($hovertime as $overtime)
            {
                $list_hovertime .= '<tr class="odd">';
                $list_hovertime .= '<td>'.$i.'</td>';
                $list_hovertime .= '<td>'.$overtime->id.'</td>';
                $list_hovertime .= '<td>'.date("d M Y", strtotime($overtime->date_created)) . '</td>';
                $list_hovertime .= '<td>'.$overtime->lot_no.'</td>';
                $list_hovertime .= '<td>'.$overtime->start_overtime.'</td>';
                $list_hovertime .= '<td>'.$overtime->end_overtime . '</td>';

                $data_status = $this->get_statusOT($overtime->status);
                $list_hovertime .= '<td><span class="badge '.$data_status["color"].'">'.$data_status["status"]. '</span></td>';
                if($overtime->start_overtime > $today && $overtime->status=='N') {
                    $list_hovertime .= '<td><button class="btn btn-danger btn-sm w-100" onclick="changeStatus('.$overtime->id.')" data-ot="'.$overtime->id.'">'.e(__('common.cancel')).'</button></td>'."\n";
                } else {
                    $list_hovertime .= '<td><button class="btn btn-danger btn-sm w-100 disabled">'.e(__('common.cancel')).'</button></td>'."\n";
                }
                $list_hovertime .= '</tr>' . "\n";
                $i++;
            }
        }
        $dtnews = DB::select("SELECT TOP 5 * from mgr.newsfeed where active='1' and attach_type='P' and status = '1'");
        $content = array(
            'combolot' => $cbLot,
            'totalOutstanding' => $totalOutstanding,
            'statusPembayaran' => $statusPembayaran,
            'list_bill' => $list_bill,
            'footer_bill' => $footer_bill,
            'list_hticket' => $list_hticket,
            'list_hovertime' => $list_hovertime,
            'dtnews'=>$dtnews
        );
        return view('tenant.billingoutstanding.index', $content);
    }

    public function getGraph(Request $request)
    {
        if($_POST)
        {
            $tenant_no = Session::get('tenant_df');
            $lot_no = $request->lot_no;
            $m = __('tenant/dashboard.months');
            $idm = array();
            $lm = array();
            $lu = array();
            $lh = array();

            $business_no = Session::get('business_no');
            $criteria = array(
                'business_no' => $business_no, 
                'tenant_no'   => $tenant_no
            );

            $dataTenancy = DB::table('mgr.pm_tenancy')
                ->where($criteria)
                ->get();
            if (!empty($dataTenancy))
            {
                $entity = $dataTenancy[0]->entity_cd;
                $project = $dataTenancy[0]->project_no;

                $dataEUsage = $this->getEusage_by_lotno($entity,$project,$tenant_no,$lot_no);
                if (!empty($dataEUsage))
                {
                    foreach ($dataEUsage as $graph) {
                        $idm[] = $graph->meter_id;
                        $lm[] = $m[$graph->Monthly].' '.$graph->Yearly ;
                        $lu[] = $graph->usages;
                        $lh[] = $graph->usage_highs;
                    }
                    
                }
                $aDs = array(
                    array(
                        'label'=>"LWBP(Lewat Waktu Beban Puncak) 22:00 - 18:00",
                        'backgroundColor'=>"#48A497",
                        'strokeColor'=>"#48A4D1",
                        'pointColor'=>"#3b8bba",
                        'pointStrokeColor'=>"rgba(60,141,188,1)",
                        'pointHighlightFill'=>"#fff",
                        'pointHighlightStroke'=>"rgba(60,141,188,1)",
                        'data'=>$lu
                    ),
                    array(
                        'label'=>"WBP(Waktu Beban Puncak) 18:00 - 22:00",
                        'backgroundColor'=>"rgba(73,188,170,0.4)",
                        'strokeColor'=>"rgba(72,174,209,0.4)",
                        'pointColor'=>"rgba(210, 214, 222, 1)",
                        'pointStrokeColor'=>"#c1c7d1",
                        'pointHighlightFill'=>"#fff",
                        'pointHighlightStroke'=>"rgba(220,220,220,1)",
                        'data'=>$lh
                    )
                );
                $aRet = array('meterid'=>$idm, 'chartdt'=>array('labels'=>$lm,'datasets'=>$aDs));
            } else {
                $aRet = array();
            }
            echo json_encode($aRet);
        }
    }

    /**
     * Combo unit (lot). Tenant biasa: unit miliknya; mode semua tenant (admin): unit dari
     * seluruh tenancy aktif, diberi label tenant_no-nya.
     */
    public function getLotNo($tenant_no=null)
    {
        $list_lot = '';
        foreach (TenantScope::tenancies() as $tenancy) {
            $crit1 = array(
                'entity_cd'=>$tenancy->entity_cd,
                'project_no'=>$tenancy->project_no,
                'business_id'=>$tenancy->business_no,
                'tenant_no'=>$tenancy->tenant_no
            );

            $tenant_lot = DB::connection('dblive')
                ->table('mgr.v_tenant_lot')
                ->where($crit1)
                ->get();

            foreach ($tenant_lot as $datalot) {
                $label = TenantScope::all() ? $tenancy->tenant_no.' - '.$datalot->descs : $datalot->descs;
                $list_lot.='<option value="'.$datalot->lot_no.'" >'.$label.'</option>';
            }
        }
        return $list_lot;
    }

    public function getEusage_by_lotno($entity="", $project="", $tenant_no="", $lotno="")
    {
        $sql = "SELECT
            a.meter_id,
            DAY(a.read_date) AS Daily,
            MONTH(a.read_date) AS Monthly,
            YEAR(a.read_date) AS Yearly,
            a.usage AS usages,
            a.usage_high AS usage_highs,
            b.lot_no 
            FROM
            mgr.pm_meter_dtl a 
            INNER JOIN
                -- mgr.pm_lot_meter_new b 
                mgr.pm_lot_meter b 
                ON a.entity_cd = b.entity_cd
                AND a.project_no = b.project_no 
                AND a.meter_id = b.meter_id 
            INNER JOIN
                mgr.pl_project d 
                ON a.entity_cd = d.entity_cd
                AND a.project_no = d.project_no 
            WHERE a.meter_type='E' AND " . TenantScope::sqlEntity('a.entity_cd') . " and " . TenantScope::sqlTenantNo('b.debtor_acct') . " AND b.lot_no = ? ORDER BY a.read_date";

        
        $query = DB::connection('dblive')->select($sql, [(string) $lotno]);
        return $query;
    }

    public function get_soa_by_tenant($entity="", $project="", $tenant_no="", $date_until=null)
    {
        if(is_null($date_until)) {
            $today = date('d M Y H:i:s');
        } else {
            $today = date('d M Y H:i:s', strtotime($date_until));
        }

        $sql = "SELECT DISTINCT pp.descs AS prj_desc, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs AS ar_ldg_desc, al.fdoc_amt, sum(ac.trx_amt) AS alloc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, ce.base_currency AS mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, NULL AS start_date, NULL AS end_date, al.fbal_amt, al.old_doc_no AS old_ref_no, al.doc_date 
            FROM mgr.ar_ledger al
            INNER JOIN mgr.ar_debtor ad ON  al.entity_cd = ad.entity_cd AND al.project_no = ad.project_no AND al.debtor_acct = ad.debtor_acct
            INNER JOIN mgr.cf_entity ce 
            ON  al.entity_cd = ce.entity_cd
            INNER JOIN mgr.pl_project pp
            ON  al.project_no = pp.project_no AND al.entity_cd = pp.entity_cd
            LEFT OUTER JOIN mgr.ar_alloc ac 
            ON  al.entity_cd = ac.entity_cd AND al.project_no = ac.project_no AND al.debtor_acct = ac.debtor_acct AND al.doc_no = ac.debit_doc AND al.doc_date = ac.debit_date AND al.trx_type = ac.debit_trx AND al.currency_cd = ac.currency_cd AND ac.trx_date <= getdate(), mgr.ar_spec ars
            WHERE al.class='I' AND " . TenantScope::sqlTenantNo('al.debtor_acct') . " AND al.doc_date <= getdate() AND fbal_amt > 0
            GROUP BY pp.descs, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs, al.fdoc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, ce.base_currency, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.fbal_amt, al.old_doc_no, al.doc_date  
            HAVING al.fdoc_amt - isnull(sum(ac.trx_amt),0) > 0";
        $query = DB::connection('dblive')->select($sql);
        return $query;
    }

    function get_statusIFCA($statusid="")
    {
        $color = '';
        $status = '';
        switch ($statusid) {
            case 'R':
                $status = __('tenant/ticket.statuses.O');
                $color = "badge-soft-info";
                break;
            case 'A':
            case 'S':
            case 'P':
            case 'F':
            case 'M':
            case 'Z':
                $status = __('tenant/ticket.statuses.P');
                $color = "badge-soft-warning";
                break;
            case 'Y':
                $status = __('tenant/ticket.statuses.Y');
                $color = "badge-soft-success";         
                break;      
            case 'C':
                $status = __('tenant/ticket.statuses.C');
                $color = "badge-soft-success";
                break;
            case 'X':
                $status = __('tenant/ticket.statuses.X');
                $color = "badge-soft-secondary";         
                break;
        }

        if(!is_null($color)) {
            $rst = array(
                'color'=>$color,
                'status'=>$status
            );
            return $rst;
        } else {
            return '';
        }
    }

    function get_statusOT($statusid="")
    {
        $color = null;
        $status = null;
        switch ($statusid) {
            case 'N':
                $color = "badge-soft-info";
                $status = __('tenant/dashboard.ot_statuses.waiting');
                break;
            case 'A':
                $color = "badge-soft-success";
                $status = __('tenant/dashboard.ot_statuses.activated');
                break;
            case 'X':
                $color = "badge-soft-warning";
                $status = __('tenant/dashboard.ot_statuses.canceled');
                break;
            case 'Z':
                $color = "badge-soft-danger";
                $status = __('tenant/dashboard.ot_statuses.closed');
                break;
        }
        
        if(!is_null($color)) {
            $rst = array(
                'color'=>$color,
                'status'=>$status
            );
            return $rst;
        } else {
            return null;
        }
    }

    function gen(Request $request)
    {
        $file = $request->chart;
        $lot_no = $request->lot_no;
        $up = 'data://'.substr($file, 5);
        $bin = file_get_contents($up);
        $target_dir = './storage/file_generate/chart/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir);
        }
        $target_file = $target_dir . 'eu_'. Session::get('Tuser_id').'.png';

        $na = 'eu_'.Session::get('Tuser_id').'.png';
        file_put_contents($target_file, $bin);
        echo url('tenant/dash/export/'.$na.'/'.$lot_no);
    }

    function export($nm = null, $lot_no=null)
    {
        if(!empty($nm) && !empty($lot_no))
        {
            $tenant_no = Session::get('tenant_df');
            $business_no = Session::get('business_no');
            $criteria = array(
                'business_no' => $business_no, 
                'tenant_no' => $tenant_no
            );
            $dtaTenancy = DB::table('mgr.pm_tenancy')->where($criteria)->get();
            if(!empty($dtaTenancy))
            {
                $entity = $dtaTenancy[0]->entity_cd;
                $project = $dtaTenancy[0]->project_no;
                $dtaGra = $this->getEusage_by_lotno($entity,$project,$tenant_no,$lot_no);
                if(!empty($dtaGra))
                {
                    $le = '';
                    foreach ($dtaGra as $Eusage) {
                        $mn = __('tenant/dashboard.months.'.(int) $Eusage->Monthly). ' '. $Eusage->Yearly;
                        $le.='<tr class="odd">';
                        $le.='<td align="center">'.$mn.'</td>';
                        $le.='<td align="center">'.number_format($Eusage->usages,2).'</td>';
                        $le.='<td align="center">'.number_format($Eusage->usage_highs,2).'</td>';
                        $le.='</tr>';
                    }
                    $name_file = 'Electric_Usage_'.$lot_no;
                    $nama_gbr = $nm;

                    $content = array(
                        'image' => $nama_gbr,
                        'cl' => $le
                    );

                    $pdf = PDF::loadView('tenant.export.elchart', $content)
                        ->setOptions([
                            'defaultFont' => 'sans-serif',
                            'isPhpEnabled' => false,
                            'isJavascriptEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultPaperSize' => 'A4',
                        ]);
                    return $pdf->download($name_file.'.pdf');
                }
            }
        }
    }

    public function cancelOT(Request $request)
    {
        $id = $request->id;
        $msg = "";

        $data_overtime = DB::table('mgr.ot_trx')
            ->where('id', $id)
            ->get();
        if ($data_overtime) {
            $crit = array('id' => $id);
            $data = array('status' => 'X');

            $query = DB::table('mgr.ot_trx')
                ->where($crit)
                ->update($data);
            if ($query != "1") {
                $msg = $query;
                $st  = 'Fail';
            } else {
                $msg = __('common.updated');
                $st  = 'OK';
            }
        }

        $callback = array(
            "pesan" => $msg,
            "status" => $st
        );
        echo json_encode($callback);
    }
}
