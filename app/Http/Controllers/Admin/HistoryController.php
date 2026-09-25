<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PdfTable;
use App\Support\TicketHd;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use DataTables;

class HistoryController extends Controller
{

    public function ticket(){

        // tenant yang punya work order (mgr.sv_entry_hd) untuk pilihan filter
        $dtDebtor = TicketHd::query()
            ->select('t.debtor_acct', 'deb.name')
            ->distinct()
            ->orderBy('deb.name')
            ->orderBy('t.debtor_acct')
            ->get();
        $content = array(
            'datadebtor'=>$dtDebtor);

        return view('admin.history.ticket',$content);
    }

    /**
     * Work order (mgr.sv_entry_hd) untuk tabel & PDF Ticket History: semua status,
     * tanggal lapor $start..$end (termasuk; null = tanpa batas), opsional satu tenant. Hasil diberi
     * row_number & categoryname seperti view lama v_ticket_history.
     */
    private function ticketRows($start, $end, $debtor)
    {
        // Semua status; tanggal hanya dibatasi kalau diisi (kosong = semua data)
        $query = TicketHd::query();

        // dd/mm/yyyy (datepicker) atau yyyy-mm-dd, lihat TicketHd::toYmd
        $start = TicketHd::toYmd($start);
        $end = TicketHd::toYmd($end);
        if ($start) {
            $query->where('t.reported_date', '>=', $start);
        }
        if ($end) {
            $query->where('t.reported_date', '<', date('Ymd', strtotime($end . ' +1 day')));
        }

        if ($debtor !== '') {
            $query->where('t.debtor_acct', $debtor);
        }

        $rows = $query->orderBy('t.reported_date', 'desc')->orderBy('t.report_no', 'desc')->get();

        foreach ($rows as $i => $row) {
            $row->row_number = $i + 1;
            $row->categoryname = $row->category_desc;
        }

        return $rows;
    }
    public function getTableTicket(Request $request)
    {

        $debtor = $request->debtor_acct;
        if(empty($debtor)){
            $debtor='';
        }

        // tanggal kosong = tanpa batas (semua work order)
        $date_end = $request->date_end ?: null;
        $date_start = $request->date_start ?: null;
        $query = $this->ticketRows($date_start, $date_end, (string) $debtor);
        return DataTables::of($query)->make(true);
    }
    public function overtime(){
       
        $sqlad = "SELECT distinct debtor_acct from mgr.v_overtime_history ORDER BY debtor_acct asc";        
        $dtDebtor = DB::connection('ifcapb')->select($sqlad);    
        $content = array(
            'datadebtor'=>$dtDebtor);
        
        return view('admin.history.overtime',$content);
    } 
    public function getTableOT(Request $request)
    {

        $debtor = $request->debtor_acct;
        if(empty($debtor)){
            $debtor='';
        }

        $date_end = $request->date_end;
        if(empty($date_end)){
            $date_end=date('Y-m-d 23:59:59', time() + 86400);  
        }else{
            $date_end=$date_end." 23:59:59";
        }

        $date_start = $request->date_start;
        if(empty($date_start)){
            $date_start=date('Y-m-01 00:00:00', strtotime("-12 months"));
        }else{
            $date_start=$date_start." 00:00:00";
        }
        $where = '';

        if($debtor!='' || !empty($debtor))
        {
            $where=" AND debtor_acct='".$debtor."' ".$where;
        }

        $sql ="SELECT ROW_NUMBER() OVER (ORDER BY begin_date desc) AS [row_number], * from mgr.v_overtime_history where  begin_date between CONVERT(DATETIME,'".$date_start."',110) and CONVERT(DATETIME,'".$date_end."',110)".$where ;
        $query = DB::connection('ifcapb')->select($sql);
        return DataTables::of($query)->make(true);
    }
    public function getTableLog(Request $request)
    {

        $date_end = $request->date_end;
        if(empty($date_end)){
            $date_end=date('Y-m-d 23:59:59', time() + 86400);  
        }else{
            $date_end=$date_end." 23:59:59";
        }

        $date_start = $request->date_start;
        if(empty($date_start)){
            $date_start=date('Y-m-01 00:00:00', strtotime("-12 months"));
        }else{
            $date_start=$date_start." 00:00:00";
        }
        $sql ="SELECT * FROM (
            SELECT 
                ROW_NUMBER() OVER (ORDER BY log_login.id) AS [row_number],idforeign,logintime,ipaddress,name,email 
            FROM mgr.log_login join mgr.tenant on tenant.id = log_login.idforeign
            ) sub
        where sub.logintime between ? and ?";
        $query = DB::connection('ifcaadm')->select($sql, [$date_start, $date_end]);
        return DataTables::of($query)->make(true);
        
    }
    public function dlpdf(Request $request)
    {
        $type = $request->type;
        $date_end = $request->date_end;
        $date_start = $request->date_start;
        $debtor = $request->debtor_acct;
        Session::put('gentype', $type);
        Session::put('date_end', $date_end);
        Session::put('date_start', $date_start);
        Session::put('debtor', $debtor);
        echo url('admin/history/export/'.$type);
    }
    /** Keterangan filter di bawah judul PDF: periode (dd/mm/yyyy) & tenant */
    private function pdfFilters($start, $end, $tenant = null)
    {
        $filters = [
            __('common.period') => ($start || $end)
                ? ($start ?: '...') . ' - ' . ($end ?: '...')
                : __('common.all'),
        ];
        if ($tenant !== null) {
            $filters[__('common.tenant')] = $tenant !== '' ? $tenant : __('common.all');
        }
        return $filters;
    }

    /** Tanggal dari database ke format tabel (FormatDateNew / FormatDateTimeNew di app.js) */
    private function pdfDate($value, $withTime = false)
    {
        if (empty($value)) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format($withTime ? 'd-m-Y H:i' : 'd-m-Y');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /** PDF dibuka di tab baru (inline), isi & kolom sama dengan DataTable halaman History */
    public function export(Request $request)
    {
        $type = $request->type;
        $rawEnd = Session::get('date_end');
        $rawStart = Session::get('date_start');
        $date_end = $rawEnd;
        if(empty($date_end)){
            $date_end=date('Y-m-d 23:59:59', time() + 86400);
        }else{
            $aa = explode("/",$date_end);
            $date_end = $aa[2]."-".$aa[1]."-".$aa[0]." 23:59:59";
        }
        $date_start = $rawStart;
        if(empty($date_start)){
            $date_start=date('Y-m-01 00:00:00', strtotime("-12 months"));
        }else{
            $aa = explode("/",$date_start);
            $date_start = $aa[2]."-".$aa[1]."-".$aa[0]." 00:00:00";
        }
        $disclaimer = __('admin/history.pdf_disclaimer');

        switch ($type) {
            case 'ticket':
                $debtor = (string) (Session::get('debtor') ?: '');
                // sama dengan tabel: tanggal filter apa adanya (dd/mm/yyyy), kosong = tanpa batas
                $dt_ticket = $this->ticketRows($rawStart ?: null, $rawEnd ?: null, $debtor);

                // warna badge sama dengan tabel Ticket History
                $tones = ['Z' => 'warning', 'Y' => 'success', 'C' => 'success', 'F' => 'success', 'X' => 'secondary'];
                $rows = [];
                foreach ($dt_ticket as $ticket) {
                    $status = trim((string) $ticket->status);
                    $known = $status !== '' && \Illuminate\Support\Facades\Lang::has('admin/history.ticket_statuses.' . $status);
                    $rows[] = [
                        $ticket->row_number,
                        $ticket->report_no,
                        $ticket->categoryname,
                        $ticket->name,
                        $ticket->work_requested,
                        $this->pdfDate($ticket->reported_date),
                        $ticket->serv_req_by,
                        $ticket->lot_no,
                        [
                            'text' => $known ? __('admin/history.ticket_statuses.' . $status) : ($status !== '' ? $status : '-'),
                            'badge' => $known ? ($tones[$status] ?? 'info') : 'secondary',
                        ],
                    ];
                }
                $tenant = $debtor === '' ? '' : (count($dt_ticket) ? $dt_ticket[0]->name : $debtor);

                return PdfTable::stream('history_tiket', [
                    'title' => __('admin/history.ticket_history'),
                    'filters' => $this->pdfFilters($rawStart, $rawEnd, $tenant),
                    'columns' => [
                        ['label' => __('admin/history.col_no'), 'align' => 'center', 'width' => '4%'],
                        ['label' => __('admin/history.wo_number'), 'align' => 'center', 'width' => '10%'],
                        ['label' => __('common.category'), 'align' => 'left', 'width' => '11%'],
                        ['label' => __('admin/history.tenant_name'), 'align' => 'left', 'width' => '14%'],
                        ['label' => __('common.description'), 'align' => 'left'],
                        ['label' => __('admin/history.reported_date'), 'align' => 'center', 'width' => '9%'],
                        ['label' => __('admin/history.request_by'), 'align' => 'left', 'width' => '10%'],
                        ['label' => __('admin/history.lot_number'), 'align' => 'center', 'width' => '8%'],
                        ['label' => __('admin/history.ticket_status'), 'align' => 'center', 'width' => '10%'],
                    ],
                    'rows' => $rows,
                    'disclaimer' => $disclaimer,
                ]);

            case 'log':
                $sql ="SELECT * FROM (
                    SELECT
                        ROW_NUMBER() OVER (ORDER BY log_login.id) AS [row_number],idforeign,logintime,ipaddress,name,email
                    FROM mgr.log_login join mgr.tenant on tenant.id = log_login.idforeign
                    ) sub
                where sub.logintime between ? and ?";
                $dtUsers = DB::connection('ifcaadm')->select($sql, [$date_start, $date_end]);
                $rows = [];
                foreach ($dtUsers as $i => $logUsers) {
                    $rows[] = [
                        $i + 1,
                        $this->pdfDate($logUsers->logintime, true),
                        $logUsers->name,
                        $logUsers->ipaddress,
                    ];
                }

                return PdfTable::stream('log_users', [
                    'title' => __('admin/history.log_user_history'),
                    'filters' => $this->pdfFilters($rawStart, $rawEnd),
                    'columns' => [
                        ['label' => __('admin/history.col_no'), 'align' => 'center', 'width' => '7%'],
                        ['label' => __('admin/history.login_date'), 'align' => 'center', 'width' => '22%'],
                        ['label' => __('admin/history.user_name'), 'align' => 'left'],
                        ['label' => __('admin/history.login_from'), 'align' => 'left', 'width' => '22%'],
                    ],
                    'rows' => $rows,
                    'disclaimer' => $disclaimer,
                ]);

            case 'overtime':
                $debtor = (string) (Session::get('debtor') ?: '');
                $sql = "SELECT ROW_NUMBER() OVER (ORDER BY begin_date desc) AS [row_number], * from mgr.v_overtime_history where begin_date between CONVERT(DATETIME,?,110) and CONVERT(DATETIME,?,110)";
                $bindings = [$date_start, $date_end];
                if ($debtor !== '') {
                    $sql .= " AND debtor_acct = ?";
                    $bindings[] = $debtor;
                }
                $dt_overtime = DB::connection('ifcapb')->select($sql, $bindings);

                // badge sama dengan tabel Overtime History
                $statuses = [
                    'N' => ['text' => __('admin/history.activated'), 'badge' => 'success'],
                    'P' => ['text' => __('admin/history.closed'), 'badge' => 'danger'],
                ];
                $rows = [];
                foreach ($dt_overtime as $overtime) {
                    $rows[] = [
                        $overtime->row_number,
                        $overtime->lot_no,
                        $overtime->debtor_acct,
                        $this->pdfDate($overtime->begin_date, true),
                        $this->pdfDate($overtime->end_date, true),
                        $statuses[$overtime->status] ?? '',
                        $overtime->remarks,
                    ];
                }

                return PdfTable::stream('history_overtime', [
                    'title' => __('admin/history.overtime_history'),
                    'filters' => $this->pdfFilters($rawStart, $rawEnd, $debtor),
                    'columns' => [
                        ['label' => __('admin/history.col_no'), 'align' => 'center', 'width' => '5%'],
                        ['label' => __('admin/history.lot_number'), 'align' => 'center', 'width' => '10%'],
                        ['label' => __('common.tenant'), 'align' => 'left', 'width' => '14%'],
                        ['label' => __('admin/history.start_overtime'), 'align' => 'center', 'width' => '14%'],
                        ['label' => __('admin/history.end_overtime'), 'align' => 'center', 'width' => '14%'],
                        ['label' => __('common.status'), 'align' => 'center', 'width' => '10%'],
                        ['label' => __('common.description'), 'align' => 'left'],
                    ],
                    'rows' => $rows,
                    'disclaimer' => $disclaimer,
                ]);

            default:
                abort(404);
        }
    }
}
