<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Sumber data semua tabel ticket (dasbor & history, tenant & admin): mgr.sv_entry_hd.
 *
 * Setiap baris = satu work order (report_no WOyymmnnnn), termasuk WO yang dibuat
 * langsung di sistem IFCA desktop. Ticket yang belum punya baris HD tidak ikut tampil.
 * Insert/update ke sv_entry_multi (demo_twp_adm & demo_twp) tetap berjalan seperti biasa;
 * lihat TicketController::update (blok "insert ke HD").
 *
 * Kolom hasil (alias t): report_no, entity_cd, project_no, debtor_acct, reported_date,
 * work_requested, serv_req_by, lot_no, status, complain_no, category_cd, category_desc, name.
 */
class TicketHd
{
    /**
     * Query builder (koneksi dblive) yang siap diberi where / orderBy memakai alias "t.".
     *
     * category_cd: dari HD; kalau kosong (WO lama) diambil dari ticket penghubungnya,
     * yaitu sv_entry_multi dengan complain_no = hd.note1 (ticket TWP baru) atau
     * sv_entry_multi_dt.report_no = hd.report_no (cara sistem desktop).
     */
    public static function query()
    {
        $db = DB::connection('dblive');

        $linkedComplainNo = "COALESCE(hd.note1, (
                SELECT TOP 1 d.complain_no FROM mgr.sv_entry_multi_dt d
                WHERE d.entity_cd = hd.entity_cd AND d.project_no = hd.project_no AND d.report_no = hd.report_no
            ))";

        $hd = $db->table('mgr.sv_entry_hd as hd')->select(
            'hd.report_no',
            'hd.entity_cd',
            'hd.project_no',
            'hd.debtor_acct',
            'hd.reported_date',
            'hd.work_requested',
            'hd.serv_req_by',
            'hd.lot_no',
            'hd.status',
            DB::raw($linkedComplainNo . ' AS complain_no'),
            DB::raw("COALESCE(NULLIF(hd.category_cd, ''), (
                SELECT TOP 1 m.category_cd FROM mgr.sv_entry_multi m
                WHERE m.entity_cd = hd.entity_cd AND m.project_no = hd.project_no
                  AND m.complain_no = " . $linkedComplainNo . "
            )) AS category_cd")
        );

        return $db->query()
            ->fromSub($hd, 't')
            ->leftJoin('mgr.sv_category as cat', 'cat.category_cd', '=', 't.category_cd')
            ->leftJoin('mgr.ar_debtor as deb', function ($join) {
                $join->on('deb.entity_cd', '=', 't.entity_cd')
                    ->on('deb.project_no', '=', 't.project_no')
                    ->on('deb.debtor_acct', '=', 't.debtor_acct');
            })
            ->select('t.*', 'cat.descs as category_desc', 'deb.name');
    }

    /** Tanggal filter dari form (dd/mm/yyyy atau yyyy-mm-dd) -> 'Ymd'; null kalau kosong / tidak valid. */
    public static function toYmd($value)
    {
        return DateInput::format($value, 'Ymd');
    }

    /**
     * Batasi ke rentang tanggal lapor (termasuk tanggal akhir). $start / $end: 'Ymd'
     * (format yang selalu dibaca benar oleh SQL Server, tidak terpengaruh DATEFORMAT).
     */
    public static function between($query, $start, $end)
    {
        return $query
            ->where('t.reported_date', '>=', $start)
            ->where('t.reported_date', '<', date('Ymd', strtotime($end . ' +1 day')));
    }
}
