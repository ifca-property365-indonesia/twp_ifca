<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use App\Support\TicketHd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use DataTables;

class HistoryController extends Controller
{

    public function ticketTable(Request $request)
    {
        if ($request->ajax()) {

            $id_tenant = Session::get('Tuser_id');
            $tenant_df = Session::get('tenant_df');

            // =========================
            // DATE START
            // =========================
            $date_start = $request->date_start;

            if (empty($date_start)) {
                // Jika kosong, ambil dari tanggal paling awal
                $date_start = '19000101';
            } else {
                // dd/mm/yyyy (datepicker) atau yyyy-mm-dd, lihat TicketHd::toYmd
                $date_start = TicketHd::toYmd($date_start) ?: '19000101';
            }

            // =========================
            // DATE END
            // =========================
            $date_end = $request->date_end;

            if (empty($date_end)) {
                // Jika kosong, tanpa batas akhir (semua work order)
                $date_end = '29991231';
            } else {
                $date_end = TicketHd::toYmd($date_end) ?: '29991231';
            }

            // =========================
            // QUERY: work order di mgr.sv_entry_hd (lihat App\Support\TicketHd)
            // =========================
            $response = TicketHd::between(
                TicketHd::query()->whereIn('t.debtor_acct', TenantScope::tenantNos()),
                $date_start,
                $date_end
            )
                ->orderBy('t.reported_date', 'desc')
                ->orderBy('t.report_no', 'desc')
                ->get();

            return Datatables::of($response)->make(true);
        }
    }

	public function ticketSearch(Request $request)
    {
    	$callback = array(
            'Data'   => null,
            'Error'  => false,
            'Pesan'  => '',
            'Status' => 200
        );
        $id_tenant = Session::get('Tuser_id');

        // tanggal kosong = tanpa batas (sama dengan ticketTable)
        $start = $request->start;
        if (empty($start)) {
            $start = '19000101';
        } else {
            // dd/mm/yyyy (datepicker) atau yyyy-mm-dd, lihat TicketHd::toYmd
            $start = TicketHd::toYmd($start) ?: '19000101';
        }

        $end = $request->end;
        if (empty($end)) {
            $end = '29991231';
        } else {
            $end = TicketHd::toYmd($end) ?: '29991231';
        }

        // sama dengan isi tabel (ticketTable): work order di mgr.sv_entry_hd
        $found = TicketHd::between(
            TicketHd::query()->whereIn('t.debtor_acct', TenantScope::tenantNos()),
            $start,
            $end
        )->exists();
        if ($found)
        {
            $callback['Pesan'] = __('common.data_found');   
            $callback['Error'] = false;
        }
        else
        {
            $callback['Error'] = true;
            $callback['Pesan'] = __('common.data_not_found');
        }
        echo json_encode($callback);
    }

    public function overtimeTable(Request $request)
    {
    	if ($request->ajax()) {
            $id_tenant = Session::get('Tuser_id');
    		$date_start = $request->date_start;
            if(empty($date_start)){
                $date_start='';
            } else {
                $tglstart = explode('/',$date_start);
                $date_start = date('Ymd',strtotime($tglstart[2].'-'.$tglstart[1].'-'.$tglstart[0]));
            }

            $date_end = $request->date_end;
            if(empty($date_end)){
                $date_end='';
            } else {
                $tglend = explode('/',$date_end);
                $date_end = date('Ymd',strtotime($tglend[2].'-'.$tglend[1].'-'.$tglend[0]));
            }

	        if (empty($date_start) || empty($date_end))
            {
	            $sql = "SELECT * FROM mgr.ot_trx WHERE " . TenantScope::sqlTenantId('id_tenant') . " and year(start_overtime)*10000+month(start_overtime)*100+day(start_overtime) >= '$date_start' AND year(start_overtime)*10000+month(start_overtime)*100+day(start_overtime) <= '$date_end' AND status <> 'E' ORDER BY start_overtime DESC";
	            // 14 Sep 2021
	        	$response = DB::connection('mysql')->select($sql);
	            return Datatables::of($response)
	                ->make(true);
            }
            else {
            	$sql = "SELECT * FROM mgr.ot_trx WHERE " . TenantScope::sqlTenantId('id_tenant') . " and year(start_overtime)*10000+month(start_overtime)*100+day(start_overtime) >= '$date_start' AND year(start_overtime)*10000+month(start_overtime)*100+day(start_overtime) <= '$date_end' AND status <> 'E' ORDER BY start_overtime DESC";
	            // 14 Sep 2021
	        	$response = DB::connection('mysql')->select($sql);
	            return Datatables::of($response)
	                ->make(true);
            }
        }
    }

    public function overtimeSearch(Request $request)
    {
    	$callback = array(
            'Data'   => null,
            'Error'  => false,
            'Pesan'  => '',
            'Status' => 200
        );
        $id_tenant = Session::get('Tuser_id');
        $start = $request->start;
        $tglstart = explode('/',$start);          
        $start = date('Ymd',strtotime($tglstart[2].'-'.$tglstart[1].'-'.$tglstart[0]));      

        $end = $request->end;
        $tglend = explode('/',$end);
        $end = date('Ymd',strtotime($tglend[2].'-'.$tglend[1].'-'.$tglend[0]));

        $sql = "SELECT * FROM mgr.ot_trx WHERE " . TenantScope::sqlTenantId('id_tenant') . " and year(start_overtime)*10000+month(start_overtime)*100+day(start_overtime) >= '$start' AND year(start_overtime)*10000+month(start_overtime)*100+day(start_overtime) <= '$end' AND status <> 'E' ORDER BY start_overtime DESC";
        $query = DB::select($sql);

        if (count($query) > 0)
        {
            $callback['Pesan'] = __('common.data_found');   
            $callback['Error'] = false;
        }
        else
        {
            $callback['Error'] = true;
            $callback['Pesan'] = __('common.data_not_found');
        }
        echo json_encode($callback);
    }

    public function billingTable(Request $request)
    {
    	if ($request->ajax()) {
    		$date_start = $request->date_start;
            if(empty($date_start)){
                $date_start='';
            } else {
                $tglstart = explode('/',$date_start);
                $date_start = date('Ymd',strtotime($tglstart[2].'-'.$tglstart[1].'-'.$tglstart[0]));
            }

            $date_end = $request->date_end;
            if(empty($date_end)){
                $date_end='';
            } else {
                $tglend = explode('/',$date_end);
                $date_end = date('Ymd',strtotime($tglend[2].'-'.$tglend[1].'-'.$tglend[0]));
            }

            $business_no = Session::get('business_no');
            $tenant_no = Session::get('tenant_df');

            $crit = array(
	            'business_no'=>$business_no,
	            'tenant_no'=>$tenant_no
	        );
	        $dataTenancy = DB::table('mgr.pm_tenancy')
	            ->where($crit)
	            ->get();

	        if (!empty($dataTenancy)) {
	            $entity = $dataTenancy[0]->entity_cd;
	            $project = $dataTenancy[0]->project_no;

	            if (empty($date_start) || empty($date_end))
	            {
		            $sql = "SELECT DISTINCT pp.descs AS prj_desc, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs AS ar_ldg_desc, al.fdoc_amt, sum(ac.trx_amt) AS alloc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, al.mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.start_date, al.end_date, al.fbal_amt, al.old_ref_no, al.doc_date, ac.credit_date
			            FROM mgr.ar_ledger al
			            INNER JOIN mgr.ar_debtor ad ON  al.entity_cd = ad.entity_cd AND al.project_no = ad.project_no AND al.debtor_acct = ad.debtor_acct
			            INNER JOIN mgr.cf_entity ce 
			            ON  al.entity_cd = ce.entity_cd AND al.mcurr_cd = ce.base_currency
			            INNER JOIN mgr.pl_project pp
			            ON  al.project_no = pp.project_no AND al.entity_cd = pp.entity_cd
			            LEFT OUTER JOIN mgr.ar_alloc ac 
			            ON  al.entity_cd = ac.entity_cd AND al.project_no = ac.project_no AND al.debtor_acct = ac.debtor_acct AND al.doc_no = ac.debit_doc AND al.doc_date = ac.debit_date AND al.trx_type = ac.debit_trx AND al.currency_cd = ac.mcurr_cd AND ac.trx_date <= getdate(), mgr.ar_spec ars
			            WHERE al.class='I' AND " . TenantScope::sqlTenantNo('al.debtor_acct') . "
						AND al.doc_date >= '$date_start' AND al.doc_date <= '$date_end'
						GROUP BY pp.descs, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs, al.fdoc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, al.mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.fbal_amt, al.old_ref_no, al.start_date,  al.end_date, al.doc_date, ac.credit_date
						ORDER BY al.doc_date DESC";
		            // 14 Sep 2021
		        	$response = DB::connection('dblive')->select($sql);
		            return Datatables::of($response)
		                ->make(true);
	            }
	            else {
	            	$sql = "SELECT DISTINCT pp.descs AS prj_desc, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs AS ar_ldg_desc, al.fdoc_amt, sum(ac.trx_amt) AS alloc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, al.mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.start_date, al.end_date, al.fbal_amt, al.old_ref_no, al.doc_date, ac.credit_date
			            FROM mgr.ar_ledger al
			            INNER JOIN mgr.ar_debtor ad ON  al.entity_cd = ad.entity_cd AND al.project_no = ad.project_no AND al.debtor_acct = ad.debtor_acct
			            INNER JOIN mgr.cf_entity ce 
			            ON  al.entity_cd = ce.entity_cd AND al.mcurr_cd = ce.base_currency
			            INNER JOIN mgr.pl_project pp
			            ON  al.project_no = pp.project_no AND al.entity_cd = pp.entity_cd
			            LEFT OUTER JOIN mgr.ar_alloc ac 
			            ON  al.entity_cd = ac.entity_cd AND al.project_no = ac.project_no AND al.debtor_acct = ac.debtor_acct AND al.doc_no = ac.debit_doc AND al.doc_date = ac.debit_date AND al.trx_type = ac.debit_trx AND al.currency_cd = ac.mcurr_cd AND ac.trx_date <= getdate(), mgr.ar_spec ars
			            WHERE al.class='I' AND " . TenantScope::sqlTenantNo('al.debtor_acct') . "
						AND al.doc_date >= '$date_start' AND al.doc_date <= '$date_end'
						GROUP BY pp.descs, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs, al.fdoc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, al.mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.fbal_amt, al.old_ref_no, al.start_date,  al.end_date, al.doc_date, ac.credit_date
						ORDER BY al.doc_date DESC";
		            // 14 Sep 2021
		        	$response = DB::connection('dblive')->select($sql);
		            return Datatables::of($response)
		                ->make(true);
	            }
	        }
        }
    }

    public function billingSearch(Request $request)
    {
        $callback = array(
            'Data'   => null,
            'Error'  => false,
            'Pesan'  => '',
            'Status' => 200
        );

        $start = $request->start;
        $tglstart = explode('/',$start);          
        $start = date('Ymd',strtotime($tglstart[2].'-'.$tglstart[1].'-'.$tglstart[0]));

        $end = $request->end;
        $tglend = explode('/',$end);
        $end = date('Ymd',strtotime($tglend[2].'-'.$tglend[1].'-'.$tglend[0]));

        $business_no = Session::get('business_no');
        $tenant_no = Session::get('tenant_df');

        $crit = array(
            'business_no'=>$business_no,
            'tenant_no'=>$tenant_no
        );
        $dataTenancy = DB::table('mgr.pm_tenancy')
            ->where($crit)
            ->get();

        if (!empty($dataTenancy)) {
            $entity = $dataTenancy[0]->entity_cd;
            $project = $dataTenancy[0]->project_no;

	        $sql = "SELECT DISTINCT pp.descs AS prj_desc, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs AS ar_ldg_desc, al.fdoc_amt, sum(ac.trx_amt) AS alloc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, al.mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.start_date, al.end_date, al.fbal_amt, al.old_ref_no, al.doc_date, ac.credit_date
	            FROM mgr.ar_ledger al
	            INNER JOIN mgr.ar_debtor ad ON  al.entity_cd = ad.entity_cd AND al.project_no = ad.project_no AND al.debtor_acct = ad.debtor_acct
	            INNER JOIN mgr.cf_entity ce 
	            ON  al.entity_cd = ce.entity_cd AND al.mcurr_cd = ce.base_currency
	            INNER JOIN mgr.pl_project pp
	            ON  al.project_no = pp.project_no AND al.entity_cd = pp.entity_cd
	            LEFT OUTER JOIN mgr.ar_alloc ac 
	            ON  al.entity_cd = ac.entity_cd AND al.project_no = ac.project_no AND al.debtor_acct = ac.debtor_acct AND al.doc_no = ac.debit_doc AND al.doc_date = ac.debit_date AND al.trx_type = ac.debit_trx AND al.currency_cd = ac.mcurr_cd AND ac.trx_date <= getdate(), mgr.ar_spec ars
	            WHERE al.class='I' AND " . TenantScope::sqlTenantNo('al.debtor_acct') . "
				AND al.doc_date >= '$start' AND al.doc_date <= '$end'
				GROUP BY pp.descs, ad.name, ad.address1, ad.address2, ad.address3, ad.post_cd, al.doc_no, al.due_date, al.descs, al.fdoc_amt, al.trx_mode, al.trx_type, al.entity_cd, al.project_no, al.debtor_acct, al.mcurr_cd, al.currency_cd, al.currency_rate, ars.age1, ars.age2, ars.age3, ars.age4, ars.age5, ars.age6, al.fbal_amt, al.old_ref_no, al.start_date,  al.end_date, al.doc_date, ac.credit_date
				ORDER BY al.doc_date DESC";
	        $query = DB::connection('dblive')->select($sql);
	    }

        if (count($query) > 0)
        {
            $callback['Pesan'] = __('common.data_found');   
            $callback['Error'] = false;
        }
        else
        {
            $callback['Error'] = true;
            $callback['Pesan'] = __('common.data_not_found');
        }
        echo json_encode($callback);
    }

    public function invoiceTable(Request $request)
    {
        $business_no = Session::get('business_no');
        $tenant_no = Session::get('tenant_df');

        $crit = array(
            'business_no' => $business_no,
            'tenant_no' => $tenant_no
        );

        $dataTenancy = DB::table('mgr.pm_tenancy')
            ->where($crit)
            ->get();

        if ($dataTenancy->isNotEmpty()) {

            $entity = $dataTenancy[0]->entity_cd;
            $project = $dataTenancy[0]->project_no;

            $start_date = $request->start_date;
            $end_date   = $request->end_date;

            $query = DB::connection('dblive')
                ->table('mgr.ar_ledger')
                ->whereIn('entity_cd', TenantScope::entityCds())
                ->whereIn('project_no', TenantScope::projectNos())
                ->whereIn('class', ['I', 'N'])
                ->where('mbal_amt', 0)
                ->whereIn('debtor_acct', TenantScope::tenantNos());

            if (!empty($start_date) && !empty($end_date)) {

                $start_date = \Carbon\Carbon::createFromFormat(
                    'd/m/Y',
                    $start_date
                )->format('Ymd');

                $end_date = \Carbon\Carbon::createFromFormat(
                    'd/m/Y',
                    $end_date
                )->addDay()->format('Ymd');

                $query->where('doc_date', '>=', $start_date)
                    ->where('doc_date', '<', $end_date);
            }

            \Log::info('Invoice Query', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            return DataTables::of($query)->make(true);
        }

        return DataTables::of(collect([]))
            ->make(true);
    }
}