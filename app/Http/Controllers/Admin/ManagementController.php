<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementController extends Controller
{
    public function index($pdf = null)
    {
        return view('admin.management.dashboard');
    }

    public function getApAging()
    {
        $data = DB::connection('ifcapb')->select("
        SELECT top 10 
            l.creditor_acct,
            c.name,
            SUM(l.mbal_amt) as amount
        FROM mgr.ap_ledger l
        INNER JOIN mgr.ap_creditor c
            ON l.creditor_acct = c.creditor_acct
        WHERE l.entity_cd = '01'
            AND l.class_cd = 'I'
            AND l.mbal_amt > 0
        GROUP BY 
            l.creditor_acct, 
            c.name
        ORDER BY SUM(l.mbal_amt) DESC
        ");

        return response()->json($data);
    }

    public function getArAging()
    {
        $data = DB::connection('ifcapb')->select("
        SELECT TOP 10
            l.debtor_acct,
            d.name,
            SUM(l.mbal_amt) AS amount
        FROM mgr.ar_ledger l
        INNER JOIN mgr.ar_debtor d
            ON l.debtor_acct = d.debtor_acct
        WHERE l.entity_cd = '01'
            AND l.class = 'I'
            AND l.mbal_amt > 0
        GROUP BY 
            l.debtor_acct,
            d.name
        ORDER BY SUM(l.mbal_amt) DESC;
        ");

        return response()->json($data);
    }

    public function getRevenueData(Request $request)
    {
        // Tangkap parameter 'year' dari request AJAX.
        // Jika tidak ada, gunakan tahun saat ini sebagai default.
        $year = $request->input('year', date('Y'));

        // Gunakan '?' untuk parameter binding pada fyear
        $data = DB::connection('ifcapb')->select("
        SELECT
            p.aperiod,
            ISNULL(a.actual_amt,0)/1000000.0 actual,
            ISNULL(b.budget_amt,0)/1000000.0 budget
        FROM
        (
            SELECT 1 aperiod UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
            UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
            UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
        ) p

        LEFT JOIN
        (
            SELECT
                aperiod,
                SUM(ABS(trx_amt)) actual_amt
            FROM mgr.gl_postjnls
            WHERE entity_cd = '01'
            AND fyear = ? 
            AND acct_cd IN (
                SELECT acct_cd
                FROM mgr.gl_chart
                WHERE acct_type = 'I'
            )
            GROUP BY aperiod
        ) a ON p.aperiod = a.aperiod

        LEFT JOIN
        (
            SELECT
                aperiod,
                SUM(trx_amt) budget_amt
            FROM mgr.gl_budget_ver
            WHERE entity_cd = '01'
            AND fyear = ? 
            AND acct_cd IN (
                SELECT acct_cd
                FROM mgr.gl_chart
                WHERE acct_type = 'I'
            )
            GROUP BY aperiod
        ) b ON p.aperiod = b.aperiod

        ORDER BY p.aperiod
        ", [$year, $year]); // Masukkan $year dua kali karena ada dua tanda '?' di query

        return response()->json($data);
    }

    public function getExpenseData(Request $request)
    {
        // Tangkap parameter 'year' dari request AJAX.
        // Jika tidak ada, gunakan tahun saat ini sebagai default.
        $year = $request->input('year', date('Y'));

        // Gunakan '?' untuk parameter binding pada fyear
        $data = DB::connection('ifcapb')->select("
        SELECT
            p.aperiod,
            ISNULL(a.actual_amt,0)/1000000.0 actual,
            ISNULL(b.budget_amt,0)/1000000.0 budget
        FROM
        (
            SELECT 1 aperiod UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
            UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
            UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
        ) p

        LEFT JOIN
        (
            SELECT
                aperiod,
                SUM(ABS(trx_amt)) actual_amt
            FROM mgr.gl_postjnls
            WHERE entity_cd = '01'
            AND fyear = ? 
            AND acct_cd IN (
                SELECT acct_cd
                FROM mgr.gl_chart
                WHERE acct_type = 'E'
            )
            GROUP BY aperiod
        ) a ON p.aperiod = a.aperiod

        LEFT JOIN
        (
            SELECT
                aperiod,
                SUM(trx_amt) budget_amt
            FROM mgr.gl_budget_ver
            WHERE entity_cd = '01'
            AND fyear = ? 
            AND acct_cd IN (
                SELECT acct_cd
                FROM mgr.gl_chart
                WHERE acct_type = 'E'
            )
            GROUP BY aperiod
        ) b ON p.aperiod = b.aperiod

        ORDER BY p.aperiod
        ", [$year, $year]); // Masukkan $year dua kali karena ada dua tanda '?' di query

        return response()->json($data);
    }
}
