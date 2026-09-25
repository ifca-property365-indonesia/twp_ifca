<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\OvertimeHours;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Request lembur (overtime) oleh tenant.
 *
 * Satu request = satu baris ot_trx per unit (status 'N' = menunggu persetujuan admin),
 * plus rincian ot_trxdt: bagian periode yang di luar jam kerja (lihat OvertimeHours).
 * Admin menyetujui / membatalkan lewat Admin\OvertimeController; riwayatnya di
 * menu Riwayat > Lembur (Tenant\HistoryController::overtimeTable).
 */
class OvertimeController extends Controller
{
    public function index()
    {
        return view('tenant.overtime.index', [
            'tenancies' => TenantScope::tenancies(),
        ]);
    }

    /** <option> unit milik tenancy yang dipilih (data-level = lantai, untuk denah). */
    public function lots($id_tenancy)
    {
        $tenancy = $this->tenancyInScope($id_tenancy);
        $html = '';
        foreach ($tenancy ? $this->tenancyLots($tenancy) : [] as $lot) {
            $html .= '<option value="' . e($lot->lot_no) . '" data-level="' . e(trim($lot->level_no)) . '">' . e($lot->lot_no) . '</option>';
        }
        return response($html);
    }

    /**
     * Jam yang boleh dipilih untuk tanggal lembur (dd/mm/yyyy):
     * start = jam pertama setelah jam kerja (hari libur: 00), end maksimal 24:00.
     */
    public function hours(Request $request)
    {
        $tenancy = $this->tenancyInScope($request->id_tenancy);
        $date = $this->ymd($request->date);
        if (!$date) {
            return response()->json(['status' => 'Fail', 'pesan' => __('tenant/overtime.invalid_date')]);
        }

        $work = OvertimeHours::workHours($tenancy->entity_cd ?? '', $date);
        $first = $work ? (int) ceil($this->minutes($work['end']) / 60) : 0;

        return response()->json([
            'status'     => 'OK',
            'first_hour' => min($first, 23),
            'work'       => $work,
            'holiday'    => $work === null,
            'closed'     => $this->pastCutoff($date),
        ]);
    }

    /** Denah lantai (mgr.pm_floor_plan) untuk unit yang dipilih. */
    public function layout(Request $request)
    {
        $tenancy = $this->tenancyInScope($request->id_tenancy);
        $lots = array_filter((array) $request->lot_no);
        if (!$tenancy || !$lots) {
            return response('<p class="text-body-secondary mb-0">' . e(__('tenant/overtime.choose_unit_first')) . '</p>');
        }

        $levels = collect($this->tenancyLots($tenancy))
            ->whereIn('lot_no', $lots)
            ->map(function ($l) { return trim($l->level_no); })
            ->unique()->values()->all();

        $plans = $levels ? DB::connection('dblive')->table('mgr.pm_floor_plan')
            ->whereRaw('RTRIM(entity_cd) = ?', [trim($tenancy->entity_cd)])
            ->whereRaw('RTRIM(project_no) = ?', [trim($tenancy->project_no)])
            ->whereIn(DB::raw('RTRIM(level_no)'), $levels)
            ->get(['level_no', 'picture']) : collect();

        $html = '';
        foreach ($plans as $plan) {
            $file = $this->floorPlanFile(trim((string) $plan->picture));
            $html .= '<figure class="text-center mb-3">'
                . ($file
                    ? '<img src="' . e(url('public/image/Res/' . rawurlencode($file))) . '" class="img-fluid border rounded" alt="">'
                    : '<div class="text-body-secondary py-4 border rounded">' . e(__('tenant/overtime.no_layout_image')) . '</div>')
                . '<figcaption class="form-note">' . e(__('common.floor')) . ' ' . e(trim($plan->level_no)) . '</figcaption></figure>';
        }

        return response($html !== '' ? $html : '<p class="text-body-secondary mb-0">' . e(__('tenant/overtime.no_layout')) . '</p>');
    }

    public function save(Request $request)
    {
        $tenancy = $this->tenancyInScope($request->id_tenancy);
        $lots = array_values(array_unique(array_filter((array) $request->lot_no)));
        $date = $this->ymd($request->overtime_date);
        $start = (string) $request->start;
        $end = (string) $request->end;
        $description = trim((string) $request->description);

        if (!$tenancy) {
            return $this->fail(__('tenant/overtime.choose_tenant'));
        }
        $allowed = collect($this->tenancyLots($tenancy))->pluck('lot_no')->all();
        if (!$lots || array_diff($lots, $allowed)) {
            return $this->fail(__('tenant/overtime.choose_unit'));
        }
        if (!$date || $date < date('Y-m-d') || date('N', strtotime($date)) == 7) {
            return $this->fail(__('tenant/overtime.invalid_date'));
        }
        if ($this->pastCutoff($date)) {
            return $this->fail(__('tenant/overtime.cutoff', ['time' => OvertimeHours::SAME_DAY_CUTOFF]));
        }
        if (!preg_match('/^([01]\d|2[0-3]):00$/', $start) || !preg_match('/^([01]\d|2[0-4]):00$/', $end)) {
            return $this->fail(__('tenant/overtime.invalid_time'));
        }
        if ($description === '') {
            return $this->fail(__('tenant/overtime.description_required'));
        }

        // jam mulai tidak boleh di dalam jam kerja; 24:00 = 00:00 hari berikutnya
        $work = OvertimeHours::workHours($tenancy->entity_cd, $date);
        if ($work && $this->minutes($start) < $this->minutes($work['end'])) {
            return $this->fail(__('tenant/overtime.start_in_work_hours', ['time' => $work['end']]));
        }
        $startAt = $date . ' ' . $start . ':00';
        $endAt = $end === '24:00' ? date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00' : $date . ' ' . $end . ':00';
        if (strtotime($endAt) - strtotime($startAt) < 3600) {
            return $this->fail(__('tenant/overtime.min_duration'));
        }

        // unit yang sudah punya lembur aktif (menunggu / disetujui / diposting) di jam yang bertabrakan
        $clash = DB::table('ot_trx')
            ->where('id_tenancy', $tenancy->id)
            ->whereIn('lot_no', $lots)
            ->whereIn('status', ['N', 'A', 'Z'])
            ->where('start_overtime', '<', $endAt)
            ->where('end_overtime', '>', $startAt)
            ->pluck('lot_no')->unique()->values()->all();
        if ($clash) {
            return $this->fail(__('tenant/overtime.already_exists', ['units' => implode(', ', $clash)]));
        }

        $tenant = DB::table('tenant')->where('business_no', $tenancy->business_no)
            ->orderByRaw("CASE WHEN flag = 'F' THEN 0 ELSE 1 END")->orderBy('id')->first();
        if (!$tenant) {
            return $this->fail(__('tenant/overtime.choose_tenant'));
        }

        $segments = OvertimeHours::segments($tenancy->entity_cd, $startAt, $endAt);
        $now = date('Y-m-d H:i:s');

        try {
            DB::transaction(function () use ($lots, $tenancy, $tenant, $startAt, $endAt, $description, $segments, $now) {
                foreach ($lots as $lot) {
                    $id = DB::table('ot_trx')->insertGetId([
                        'entity_cd'      => trim($tenancy->entity_cd),
                        'project_no'     => trim($tenancy->project_no),
                        'id_tenant'      => $tenant->id,
                        'id_tenancy'     => $tenancy->id,
                        'lot_no'         => $lot,
                        'status'         => 'N',
                        'status_email'   => 'N',
                        'approved'       => 'N',
                        'date_created'   => $now,
                        'start_overtime' => $startAt,
                        'end_overtime'   => $endAt,
                        'description'    => $description,
                    ]);
                    foreach ($segments as $s) {
                        DB::table('ot_trxdt')->insert([
                            'id_overtime'  => $id,
                            'date_created' => $now,
                            'dt_starttime' => $s['start'],
                            'dt_endtime'   => $s['end'],
                        ]);
                    }
                }
            });
        } catch (\Illuminate\Database\QueryException $ex) {
            return $this->fail(__('common.save_failed', ['message' => $ex->getMessage()]));
        }

        return response()->json(['status' => 'OK', 'pesan' => __('tenant/overtime.saved')]);
    }

    // ------------------------------------------------------------------

    /** Tenancy (MySQL pm_tenancy) yang masuk cakupan tenant ini, atau null. */
    private function tenancyInScope($id)
    {
        return TenantScope::tenancies()->firstWhere('id', (int) $id);
    }

    /** Unit tenancy ini dari mgr.pm_tenant_lot + lantainya (mgr.pm_lot). */
    private function tenancyLots($tenancy)
    {
        return DB::connection('dblive')
            ->table('mgr.pm_tenant_lot AS tl')
            ->join('mgr.pm_lot AS l', function ($join) {
                $join->on('l.entity_cd', '=', 'tl.entity_cd')
                    ->on('l.project_no', '=', 'tl.project_no')
                    ->on('l.lot_no', '=', 'tl.lot_no');
            })
            ->where('tl.entity_cd', $tenancy->entity_cd)
            ->where('tl.project_no', $tenancy->project_no)
            ->where('tl.tenant_no', $tenancy->tenant_no)
            ->orderBy('tl.lot_no')
            ->distinct()
            ->get(['tl.lot_no', 'l.level_no'])
            ->map(function ($l) { $l->lot_no = trim($l->lot_no); return $l; })
            ->all();
    }

    /** File denah di public/image/Res (nama di pm_floor_plan tanpa ekstensi). */
    private function floorPlanFile($picture)
    {
        if ($picture === '' || !preg_match('/^[\w .()-]+$/', $picture)) {
            return null;
        }
        foreach (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'JPG', 'JPEG', 'PNG', 'BMP'] as $ext) {
            if (is_file(public_path('image/Res/' . $picture . '.' . $ext))) {
                return $picture . '.' . $ext;
            }
        }
        return null;
    }

    /** Lembur untuk hari ini hanya bisa diajukan sebelum jam SAME_DAY_CUTOFF. */
    private function pastCutoff($date)
    {
        return $date === date('Y-m-d') && date('H:i') >= OvertimeHours::SAME_DAY_CUTOFF;
    }

    /** dd/mm/yyyy -> Y-m-d (null kalau tidak valid) */
    private function ymd($value)
    {
        $d = \DateTime::createFromFormat('!d/m/Y', trim((string) $value));
        return $d ? $d->format('Y-m-d') : null;
    }

    private function minutes($hhmm)
    {
        list($h, $m) = array_map('intval', explode(':', $hhmm) + [0, 0]);
        return $h * 60 + $m;
    }

    private function fail($pesan)
    {
        return response()->json(['status' => 'Fail', 'pesan' => $pesan]);
    }
}
