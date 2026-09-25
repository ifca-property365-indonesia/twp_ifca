<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use App\Support\TicketHd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PDF;

class DashController extends Controller
{
    public function index()
    {
        $tenant_no = Session::get('tenant_df');
        $entity = Session::get('entity_cd');
        $project = Session::get('project_no');
        $id_tenant = Session::get('Tuser_id');
        $tenant_flag = Session::get('Tflag');
        $crit = array('tenant_no' => $tenant_no,'id_tenant' =>$id_tenant);

        // Combo Lot No
        $cbLot = $this->getLotNo($tenant_no);

        // Meter ID akan diambil setelah Utility dipilih
        $cbMeterId = '';

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
        
        $i = 1;

        // Proforma Payment
        $sumProforma = [];
        $totalProforma = '';
        $dataproforma = $this->get_proforma_by_tenant($entity,$project,$tenant_no);
        if ($dataproforma->isNotEmpty()) {

            foreach ($dataproforma as $proforma) {

                $currency = strtoupper(trim($proforma->currency_cd));
                $outstanding = ($proforma->base_amt ?? 0) + ($proforma->tax_amt ?? 0);

                if (!isset($sumProforma[$currency])) {
                    $sumProforma[$currency] = 0;
                }

                $sumProforma[$currency] += $outstanding;
                $i++;
            }

            $first = true;

            foreach ($sumProforma as $currency => $amount) {

                // Untuk ditampilkan di card
                if (!$first) {
                    $totalProforma .= '<br>';
                }

                $totalProforma .= $currency.' '.number_format($amount,2,",",".");
            }

            $statusProforma = false;

        } else {

            $statusProforma = true;

        }

        // INVOICE STATUS
        $sumInvoice = [];
        $totalInvoice = '';
        $dataInvoice = $this->get_invoice_by_tenant($entity,$project,$tenant_no);
        if ($dataInvoice->isNotEmpty()) {

            foreach ($dataInvoice as $invoice) {

                $currency = strtoupper(trim($invoice->currency_cd));
                $outstanding = ($invoice->fdoc_amt ?? 0);

                if (!isset($sumInvoice[$currency])) {
                    $sumInvoice[$currency] = 0;
                }

                $sumInvoice[$currency] += $outstanding;
                $i++;
            }

            $first = true;

            foreach ($sumInvoice as $currency => $amount) {

                // Untuk ditampilkan di card
                if (!$first) {
                    $totalInvoice .= '<br>';
                }

                $totalInvoice .= $currency.' '.number_format($amount,2,",",".");
            }

            $statusInvoice = false;

        } else {

            $statusInvoice = true;

        }
        // Our Latest Ticket
        $flagsurvey = 1;
        $list_hticket = "";
        $i = 1;

        // work order di mgr.sv_entry_hd (lihat App\Support\TicketHd)
        $htenants = TicketHd::query()
    ->whereIn('t.debtor_acct', TenantScope::tenantNos())
    ->orderBy('t.reported_date', 'desc')
    ->orderBy('t.report_no', 'desc')
    ->get();

if (!empty($htenants)) {
    foreach ($htenants as $tenant)
    {
        // =========================
        // CEK RECHARGEABLE / NON
        // =========================
        $billingType = __('tenant/dashboard.non_rechargeable');

        $checkRecharge = DB::connection('dblive')
            ->table('mgr.sv_entry_dt')
            ->where('entity_cd', $tenant->entity_cd)
            ->where('project_no', $tenant->project_no)
            ->where('report_no', $tenant->report_no)
            ->first();

        if ($checkRecharge) {
            $billingType = __('tenant/dashboard.rechargeable');
        }

        // =========================
        // HTML TABLE
        // =========================
        $list_hticket .= '<tr class="odd">';
        $list_hticket .= '<td>'.$i.'</td>';
        $list_hticket .= '<td>'.e($tenant->report_no).'</td>';
        $list_hticket .= '<td>'.e($tenant->category_desc ?? $tenant->category_cd).'</td>';

        $list_hticket .= '<td>'.$tenant->work_requested.'</td>';
        $list_hticket .= '<td>'.date("d M Y", strtotime($tenant->reported_date)).'</td>';
        $list_hticket .= '<td>'.$tenant->serv_req_by.'</td>';
        $list_hticket .= '<td>'.$tenant->lot_no.'</td>';

        $data_status = $this->get_statusIFCA($tenant->status);

        $list_hticket .= '<td>'.$billingType.'</td>';

        $list_hticket .= '<td><span class="badge '.$data_status["color"].'">'
            .$data_status["status"].
            '</span></td>';

        // Edit hanya untuk WO berstatus R yang berasal dari ticket TWP (id-nya di demo_twp_adm mgr.sv_entry_multi)
        $editId = null;
        if (trim((string) $tenant->status) === 'R' && $tenant->complain_no) {
            $editId = DB::table('mgr.sv_entry_multi')
                ->where('entity_cd', $tenant->entity_cd)
                ->where('project_no', $tenant->project_no)
                ->where('complain_no', $tenant->complain_no)
                ->value('id');
        }

        if ($editId) {
            $list_hticket .= '<td><button class="btn btn-warning btn-sm w-100" onclick="location.href=\''.url('tenant/ticket').'/'.$editId.'/edit\'"> '.e(__('common.edit')).'</button></td>';
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

                $data_status = $this->get_statusOT(
                    $overtime->status,
                    $overtime->start_overtime,
                    $overtime->end_overtime
                );
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
        $dtnews = DB::select("SELECT TOP 5 * FROM mgr.newsfeed WHERE start_date <= CAST(GETDATE() AS date) AND end_date >= CAST(GETDATE() AS date) ORDER BY start_date DESC");
        $content = array(
            'combolot' => $cbLot,
            'combometerid' => $cbMeterId,
            'totalProforma' => $totalProforma,
            'statusPembayaran' => $statusProforma,
            'totalInvoice' => $totalInvoice,
            'statusInvoice' => $statusInvoice,
            'list_hticket' => $list_hticket,
            'list_hovertime' => $list_hovertime,
            'tenant_flag'=>$tenant_flag,
            'dtnews'=>$dtnews
        );
        return view('tenant.dash.index', $content);
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
                        'label'=>"Lewat Waktu Beban Puncak 22:00 - 18:00",
                        'backgroundColor'=>"#48A497",
                        'strokeColor'=>"#48A4D1",
                        'pointColor'=>"#3b8bba",
                        'pointStrokeColor'=>"rgba(60,141,188,1)",
                        'pointHighlightFill'=>"#fff",
                        'pointHighlightStroke'=>"rgba(60,141,188,1)",
                        'data'=>$lu
                    ),
                    array(
                        'label'=>"Waktu Beban Puncak 18:00 - 22:00",
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

    public function getGraphMeterId(Request $request)
    {
        $tenant_no = Session::get('tenant_df');
        $meteridcombo = $request->meteridcombo;
        $yearcombo = $request->yearcombo;
        $utility = $request->utility;
        
        $m = __('tenant/dashboard.months');
        $idm = array();
        $lm = array();
        $lu = array();
        

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

            $dataEUsage = $this->getEusagehis_by_lotmeter($entity,$tenant_no,$meteridcombo, $yearcombo, $utility);
            
            if (!empty($dataEUsage))
            {
                foreach ($dataEUsage as $graph) {
                    $idm[] = $graph->meter_id;
                    $lm[] = $m[$graph->Monthly].' '.$graph->Yearly ;
                    $lu[] = $graph->usages;
                }
                
            }
            $aDs = array(
                array(
                    'label' => __('tenant/dashboard.monthly_usage'),
            
                    // warna utama area & bar
                    'backgroundColor' => '#D1BF8F',
            
                    // garis line chart
                    'borderColor' => '#BDA870',
            
                    // titik line chart
                    'pointBackgroundColor' => '#BDA870',
            
                    // border titik
                    'pointBorderColor' => '#FFFFFF',
            
                    // hover
                    'pointHoverBackgroundColor' => '#E5D6AB',
                    'pointHoverBorderColor' => '#C7B582',
            
                    // border bar chart
                    'borderWidth' => 2,
            
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
            
                    'fill' => true,
            
                    'data' => $lu
                )
            );
            $aRet = array('meterid'=>$idm, 'chartdt'=>array('labels'=>$lm,'datasets'=>$aDs));
        } else {
            $aRet = array();
        }
        echo json_encode($aRet);
    }

    /**
     * Combo unit (lot) untuk dashboard. Tenant biasa: unit miliknya; mode semua tenant:
     * unit dari seluruh tenancy aktif, diberi label tenant_no-nya.
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
            WHERE a.meter_type='E' AND " . TenantScope::sqlEntity('a.entity_cd') . " and " . TenantScope::sqlTenantNo('b.debtor_acct') . " AND b.lot_no='$lotno' ORDER BY a.read_date";

        $query = DB::connection('dblive')->select($sql);
        return $query;
    }

public function getEusagehis_by_lotmeter($entity="", $tenant_no="", $meterId="", $year="", $utility = "")
    {
        $sql = "SELECT
                a.meter_id,
                DAY(a.read_date) AS Daily,
                MONTH(a.read_date) AS Monthly,
                YEAR(a.read_date) AS Yearly,
                a.usage AS usages
            FROM mgr.pm_meter_dtl_his a
            WHERE " . TenantScope::sqlEntity('a.entity_cd') . "
            AND " . TenantScope::sqlTenantNo('a.debtor_acct') . "
            AND a.meter_id = '$meterId'
            AND a.meter_cd LIKE '{$utility}%'
            AND YEAR(a.read_date) = $year
            ORDER BY a.read_date";
        $query = DB::connection('dblive')->select($sql);

        return $query;
    }

    public function get_proforma_by_tenant($entity="", $project="", $tenant_no="")
    {
        $query = DB::connection('dblive')
                ->table('mgr.ar_bill')
                ->whereIn('entity_cd', TenantScope::entityCds())
                ->whereIn('project_no', TenantScope::projectNos())
                ->whereIn('debtor_acct', TenantScope::tenantNos())
                ->orderBy('doc_date', 'desc')
                ->get();
        return $query;
    }

    public function get_invoice_by_tenant($entity="", $project="", $tenant_no="")
    {
        $query = DB::connection('dblive')
                ->table('mgr.ar_ledger')
                ->whereIn('entity_cd', TenantScope::entityCds())
                ->whereIn('project_no', TenantScope::projectNos())
                ->whereIn('class', ['I', 'N'])
                ->where('mbal_amt', '>', 0)
                ->whereIn('debtor_acct', TenantScope::tenantNos())
                ->get();
        return $query;
    }

    function get_statusIFCA($statusid="")
    {
        $color = '';
        $status = '';
        switch ($statusid) {
            case 'R':
                $status = __('tenant/ticket.statuses.R');
                $color = "badge-soft-info";
                break;
            case 'O':
                $status = __('tenant/ticket.statuses.O');
                $color = "badge-soft-info";
                break;
            case 'A':
                $status = __('tenant/ticket.statuses.A');
                $color = "badge-soft-info";
                break;
            case 'S':
                $status = __('tenant/ticket.statuses.S');
                $color = "badge-soft-info";
                break;
            case 'P':
                $status = __('tenant/ticket.statuses.P');
                $color = "badge-soft-info";
                break;
            case 'F':
                $status = __('tenant/ticket.statuses.F');
                $color = "badge-soft-info";
                break;
            case 'M':
                $status = __('tenant/ticket.statuses.M');
                $color = "badge-soft-info";
                break;
            case 'Z':
                $status = __('tenant/ticket.statuses.Z');
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

    function get_statusOT($statusid = "", $startOvertime = null, $endOvertime = null)
    {
        $now = date('Y-m-d H:i:s');

        switch ($statusid) {

            case 'N':
                return [
                    'color'  => 'badge-soft-info',
                    'status' => __('tenant/dashboard.ot_statuses.process')
                ];

            case 'A':

                if ($now >= $startOvertime && $now <= $endOvertime) {
                    return [
                        'color'  => 'badge-soft-primary',
                        'status' => __('tenant/dashboard.ot_statuses.activated')
                    ];
                }

                if ($now > $endOvertime) {
                    return [
                        'color'  => 'badge-soft-dark',
                        'status' => __('tenant/dashboard.ot_statuses.ended')
                    ];
                }

                return [
                    'color'  => 'badge-soft-success',
                    'status' => __('tenant/dashboard.ot_statuses.scheduled')
                ];

            case 'X':
                return [
                    'color'  => 'badge-soft-warning',
                    'status' => __('tenant/dashboard.ot_statuses.canceled')
                ];

            case 'Z':
                return [
                    'color'  => 'badge-soft-danger',
                    'status' => __('tenant/dashboard.ot_statuses.closed')
                ];
        }

        return null;
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

    public function getMeterIdByUtility(Request $request)
    {
        $tenant_no = Session::get('tenant_df');
        $utility = $request->utility;

        $business_no = Session::get('business_no');

        $criteria = array(
            'business_no' => $business_no,
            'tenant_no'   => $tenant_no
        );

        $dataTenancy = DB::table('mgr.pm_tenancy')
            ->where($criteria)
            ->get();

        if ($dataTenancy->isEmpty()) {
            return response()->json([
                'status' => false,
                'html'   => ''
            ]);
        }

        $entity = $dataTenancy[0]->entity_cd;

        $meters = DB::connection('dblive')
            ->table('mgr.pm_meter_dtl_his')
            ->select('meter_id', 'lot_no', 'debtor_acct')
            ->whereIn('entity_cd', TenantScope::entityCds())
            ->whereIn('debtor_acct', TenantScope::tenantNos())
            ->where('meter_cd', 'LIKE', $utility . '%')
            ->distinct()
            ->orderBy('debtor_acct')
            ->orderBy('lot_no')
            ->orderBy('meter_id')
            ->get();

        $html = '';

        foreach ($meters as $meter) {
            $prefix = TenantScope::all() ? $meter->debtor_acct . ' - ' : '';
            $html .= '<option value="' . $meter->meter_id . '">'
                . $prefix . $meter->lot_no . ' - ' . $meter->meter_id
                . '</option>';
        }

        return response()->json([
            'status' => true,
            'html'   => $html
        ]);
    }
}
