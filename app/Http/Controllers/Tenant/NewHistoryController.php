<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class NewHistoryController extends Controller
{
	public function getbillingtable(Request $request)
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
}