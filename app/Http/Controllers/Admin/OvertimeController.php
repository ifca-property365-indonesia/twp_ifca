<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;

/**
 * Lembur (overtime) di portal admin.
 *
 * Approval : request tenant (ot_trx status N) disetujui (A) atau dibatalkan (X).
 * Posting  : lembur yang disetujui dan belum ditagih (status A, approved N) dalam satu periode
 *            dibuatkan tagihan di IFCA (mgr.ot_trx_fji + ot_trxdt_fji + ot_trxdt_zone_fji),
 *            lalu ot_trx ditandai approved Y / status Z (closed).
 *            Catatan: mgr.v_ot_debtor_tenancy, ot_trx_fji, ot_trxdt_fji, ot_trxdt_zone_fji belum ada
 *            di jbc_live dan mgr.ot_spec belum berisi trx_type/tax_cd -> posting menampilkan pesan
 *            error dari database sampai objek-objek itu disiapkan di IFCA.
 */
class OvertimeController extends Controller
{
    const STATUSES = ['new' => 'N', 'approved' => 'A', 'cancelled' => 'X'];

    // ------------------------------------------------------------------
    // Approval
    // ------------------------------------------------------------------

    public function approval()
    {
        return view('admin.overtime.approval');
    }

    /** Data tabel per tab: new | approved | cancelled (view v_ot_tenancy = ot_trx + pm_tenancy). */
    public function table($tab)
    {
        $status = self::STATUSES[$tab] ?? 'N';
        $rows = DB::connection('ifcaadm')->table('v_ot_tenancy')
            ->where('status', $status)
            ->orderBy('start_overtime', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        foreach ($rows as $i => $row) {
            $row->row_number = $i + 1;
        }

        return DataTables::of($rows)->make(true);
    }

    public function approve(Request $request)
    {
        return $this->changeStatus($request->id, 'A', __('admin/overtime.approved_msg'));
    }

    public function cancel(Request $request)
    {
        return $this->changeStatus($request->id, 'X', __('admin/overtime.cancelled_msg'));
    }

    /** Hanya request yang masih menunggu (N) yang bisa disetujui / dibatalkan. */
    private function changeStatus($id, $status, $message)
    {
        try {
            $updated = DB::connection('ifcaadm')->table('ot_trx')
                ->where('id', (int) $id)
                ->where('status', 'N')
                ->update(['status' => $status]);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json(['status' => 'Fail', 'pesan' => __('common.update_failed', ['message' => $ex->getMessage()])]);
        }

        return $updated
            ? response()->json(['status' => 'OK', 'pesan' => $message])
            : response()->json(['status' => 'Fail', 'pesan' => __('admin/overtime.not_waiting')]);
    }

    // ------------------------------------------------------------------
    // Posting ke billing IFCA
    // ------------------------------------------------------------------

    public function posting()
    {
        $entities = $projects = collect();
        try {
            $entities = DB::connection('dblive')->table('mgr.cf_entity')->orderBy('entity_cd')->get(['entity_cd', 'entity_name']);
            $projects = DB::connection('dblive')->table('mgr.pl_project')->orderBy('project_no')->get(['entity_cd', 'project_no', 'descs']);
        } catch (\Throwable $e) {
        }

        return view('admin.overtime.posting', [
            'entities' => $entities,
            'projects' => $projects,
        ]);
    }

    /**
     * Debtor yang punya lembur siap ditagih (status A, approved N) di periode yang dipilih
     * (tanggal yyyy-mm-dd, kosong = semua), dari mgr.v_ot_debtor_tenancy.
     */
    public function postingTable(Request $request)
    {
        $entity = trim((string) $request->entity);
        $project = trim((string) $request->project);

        try {
            if ($entity === '' || $project === '') {
                return DataTables::of(collect())->make(true);
            }

            $q = DB::connection('ifcaadm')->table('v_ot_tenancy')
                ->where('entity_cd', $entity)->where('project_no', $project)
                ->where('approved', 'N')->where('status', 'A');
            if ($request->date_start && $request->date_end) {
                $q->where('start_overtime', '>=', $request->date_start . ' 00:00:00')
                  ->where('start_overtime', '<=', $request->date_end . ' 23:59:59');
            }
            $tenantNos = $q->distinct()->pluck('tenant_no')->all();
            if (!$tenantNos) {
                return DataTables::of(collect())->make(true);
            }

            $rows = DB::connection('ifcapb')->table('mgr.v_ot_debtor_tenancy')
                ->where('entity_cd', $entity)->where('project_no', $project)
                ->whereIn('bill_debtor_acct', $tenantNos)
                ->whereColumn('debtor_acct', 'bill_debtor_acct')
                ->orderBy('bill_debtor_acct', 'desc')
                ->get();
            foreach ($rows as $i => $row) {
                $row->row_number = $i + 1;
            }
            return DataTables::of($rows)->make(true);
        } catch (\Throwable $e) {
            return response()->json([
                'draw' => (int) $request->draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                'error' => __('admin/overtime.posting_unavailable', ['message' => $e->getMessage()]),
            ]);
        }
    }

    /** Buat tagihan lembur satu debtor untuk periode yang dipilih (dd/mm/yyyy). */
    public function postingSave(Request $request)
    {
        $entity = trim((string) $request->entity);
        $project = trim((string) $request->project);
        $debtor = trim((string) $request->bill_debtor_acct);
        $remarks = trim((string) $request->remarks);
        $postDate = $this->ymd($request->post_date);
        $startDate = $this->ymd($request->start);
        $endDate = $this->ymd($request->end);

        if ($entity === '' || $project === '' || $debtor === '' || $remarks === '' || !$postDate || !$startDate || !$endDate) {
            return $this->fail(__('admin/overtime.posting_incomplete'));
        }
        if ($endDate < $startDate) {
            return $this->fail(__('admin/overtime.invalid_period'));
        }

        // kolom datetime SQL Server: format yyyymmdd hh:mm:ss (tidak tergantung DATEFORMAT sesi)
        $sqlDate = function ($ymd, $time = '00:00:00') { return str_replace('-', '', $ymd) . ' ' . $time; };
        $now = date('Ymd H:i:s');
        $ifca = DB::connection('dblive');

        try {
            $spec = $ifca->table('mgr.ot_spec')->first();
            if (!$spec || !isset($spec->trx_type, $spec->tax_cd)) {
                return $this->fail(__('admin/overtime.no_ot_spec'));
            }
            $taxRate = (float) ($ifca->table('mgr.cf_tax_sch_dt')
                ->where('scheme_cd', $spec->tax_cd)->where('deduct_flag', 'N')->value('tax_rate') ?? 0);

            $bill = $ifca->table('mgr.v_ot_debtor_tenancy')
                ->where('entity_cd', $entity)->where('project_no', $project)->where('debtor_acct', $debtor)
                ->first(['bill_debtor_acct', 'inv_group', 'currency_cd', 'min_hours_type', 'min_over_hours', 'credit_terms']);
            if (!$bill) {
                return $this->fail(__('admin/overtime.no_debtor'));
            }

            $overtimes = DB::connection('ifcaadm')->table('v_ot_tenancy')
                ->where('entity_cd', $entity)->where('project_no', $project)
                ->where('tenant_no', $bill->bill_debtor_acct)
                ->where('approved', 'N')->where('status', 'A')
                ->where('start_overtime', '>=', $startDate . ' 00:00:00')
                ->where('start_overtime', '<=', $endDate . ' 23:59:59')
                ->orderBy('start_overtime')
                ->get();
            if ($overtimes->isEmpty()) {
                return $this->fail(__('admin/overtime.nothing_to_post'));
            }

            // rincian per unit: tarif dari zona lembur unit (pm_lot -> ot_type -> ot_rate_scl)
            $details = $zones = [];
            $billAmt = 0;
            foreach ($overtimes as $ot) {
                $lot = $ifca->table('mgr.pm_lot AS l')
                    ->join('mgr.ot_type AS t', function ($j) {
                        $j->on('l.entity_cd', '=', 't.entity_cd')->on('l.over_ot_cd', '=', 't.over_cd');
                    })
                    ->where('l.entity_cd', $entity)->where('l.project_no', $project)->where('l.lot_no', $ot->lot_no)
                    ->first(['l.over_ot_cd', 'l.zone_ot_cd', 't.trx_type']);
                if (!$lot) {
                    return $this->fail(__('admin/overtime.no_lot_rate', ['lot' => $ot->lot_no]));
                }
                $rate = (float) ($ifca->table('mgr.ot_rate_scl')
                    ->where('over_cd', $lot->over_ot_cd)->where('zone_cd', $lot->zone_ot_cd)
                    ->where('entity_cd', $entity)->where('project_no', $project)->value('rate') ?? 0);

                $hours = (strtotime($ot->end_overtime) - strtotime($ot->start_overtime)) / 3600;
                $base = $hours * $rate;
                $tax = $base * $taxRate / 100;
                $billAmt += $base;

                $common = [
                    'entity_cd' => $entity, 'project_no' => $project, 'debtor_acct' => $debtor,
                    'lot_no' => $ot->lot_no, 'inv_group' => $bill->inv_group, 'trx_type' => $lot->trx_type,
                    'over_cd' => $lot->over_ot_cd, 'zone_cd' => $lot->zone_ot_cd,
                    'bill_date' => $sqlDate($postDate),
                    'begin_date' => date('Ymd H:i:s', strtotime($ot->start_overtime)),
                    'end_date' => date('Ymd H:i:s', strtotime($ot->end_overtime)),
                    'rate' => $rate, 'trx_amt' => $base, 'base_amt' => $base, 'tax_amt' => $tax,
                    'audit_user' => 'TWP', 'audit_date' => $now, 'descs' => $ot->description,
                ];
                $details[] = $common + [
                    'apport_percent' => 100, 'usage' => $hours, 'tax_cd' => $spec->tax_cd,
                    'usage_zone' => (int) round($hours * 60), 'area' => 0,
                ];
                $zones[] = $common + [
                    'start_date' => $common['begin_date'], 'scale' => $hours * 60, 'scale_use' => $hours * 60, 'rate_type' => 'O',
                ];
            }

            $ifca->transaction(function () use ($ifca, $entity, $project, $debtor, $bill, $spec, $postDate, $startDate, $endDate, $remarks, $now, $sqlDate, $billAmt, &$details, &$zones) {
                $rowId = $ifca->table('mgr.ot_trx_fji')->insertGetId([
                    'entity_cd' => $entity, 'project_no' => $project, 'debtor_acct' => $debtor,
                    'inv_group' => $bill->inv_group, 'trx_type' => $spec->trx_type,
                    'bill_date' => $sqlDate($postDate), 'currency_cd' => $bill->currency_cd,
                    'remarks' => $remarks, 'status' => 'N', 'audit_user' => 'TWP', 'audit_date' => $now,
                    'min_hours_type' => $bill->min_hours_type, 'min_over_hours' => $bill->min_over_hours,
                    'bill_amt' => $billAmt, 'flag_round' => 'N', 'credit_terms' => $bill->credit_terms,
                    'start_period' => $sqlDate($startDate), 'end_period' => $sqlDate($endDate, '23:59:59'),
                ], 'rowID');
                foreach ($details as &$d) { $d['rowid_hd'] = $rowId; }
                foreach ($zones as &$z) { $z['rowid_hd'] = $rowId; }
                $ifca->table('mgr.ot_trxdt_fji')->insert($details);
                $ifca->table('mgr.ot_trxdt_zone_fji')->insert($zones);
            });

            DB::connection('ifcaadm')->table('ot_trx')
                ->whereIn('id', $overtimes->pluck('id')->all())
                ->update(['approved' => 'Y', 'status' => 'Z']);
        } catch (\Throwable $e) {
            return $this->fail(__('admin/overtime.posting_unavailable', ['message' => $e->getMessage()]));
        }

        return response()->json(['status' => 'OK', 'pesan' => __('admin/overtime.posted_msg', ['count' => $overtimes->count()])]);
    }

    /** dd/mm/yyyy -> Y-m-d (null kalau tidak valid) */
    private function ymd($value)
    {
        $d = \DateTime::createFromFormat('!d/m/Y', trim((string) $value));
        return $d ? $d->format('Y-m-d') : null;
    }

    private function fail($pesan)
    {
        return response()->json(['status' => 'Fail', 'pesan' => $pesan]);
    }
}
