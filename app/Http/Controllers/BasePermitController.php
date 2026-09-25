<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use DataTables;
use PDF;

/**
 * Permit Letter (Work Permit W, Entry I / Exit O Permit of Goods) — logika bersama
 * portal tenant (App\Http\Controllers\Tenant\PermitController) dan portal admin
 * (App\Http\Controllers\Admin\PermitController). View-nya juga bersama: resources/views/permit/.
 *
 * Semua data di SQL Server (koneksi dblive):
 *   mgr.sv_entry_letter      header (complain_type = W/I/O, complain_no = nomor permit, status)
 *   mgr.permit_letter_hd/dtl detail Work Permit + daftar pekerja
 *   mgr.permit_letter_tools  rincian kegiatan + peralatan / APD Work Permit
 *   mgr.permit_goods_hd/dtl  detail Permit of Goods + daftar barang
 *   mgr.sv_entry_letter_log  log; tiap perubahan status di-INSERT (tidak pernah di-update)
 *   mgr.sv_spec.letter_no    nomor permit berikutnya per entity/project (LP100001, LP100002, ...)
 *
 * Beda portal:
 *   - tenant hanya melihat/mengubah permit miliknya (TenantScope), admin semua tenant;
 *   - saat ubah permit, tenant boleh mengubah bagian 3-6, admin hanya bagian 4-6
 *     (jadwal, pekerja, kegiatan & peralatan);
 *   - menyimpan perubahan selalu -> Modify (M); Cancel (X) lewat tombol di History;
 *   - Approved (Y) hanya lewat admin mengunggah dokumen bertanda tangan (uploadSigned),
 *     disimpan sebagai {permit_no}.{ext} di storage/app/private/permit_signed/{entity}/{project}/
 *     (disk 'local', tidak bisa diakses langsung lewat URL; dibuka lewat signed());
 *   - cetak: admin mencetak formulir PDF (untuk ditandatangani) selama belum Cancel;
 *     tenant hanya setelah Approved, dan yang dicetak adalah dokumen bertanda tangan.
 */
abstract class BasePermitController extends Controller
{
    /** Jenis permit (complain_type) -> label. */
    public const TYPES = [
        'W' => 'Work Permit',
        'I' => 'Entry Permit of Goods',
        'O' => 'Exit Permit of Goods',
    ];

    /** Status sv_entry_letter -> label (kode yang sama dengan modul ticket). */
    public const STATUSES = [
        'R' => 'Open',
        'A' => 'Accepted',
        'S' => 'Survey',
        'P' => 'Process',
        'F' => 'Confirm',
        'M' => 'Modify',
        'Z' => 'Charged Approved',
        'Y' => 'Approved',
        'C' => 'Closed',
        'X' => 'Cancel',
    ];

    /** Status yang isinya masih boleh diubah / dibatalkan (belum disetujui atau dibatalkan). */
    public const EDITABLE_STATUSES = ['R', 'M'];

    /** Status setelah admin mengunggah dokumen bertanda tangan; hanya status ini yang bisa dicetak tenant. */
    public const PRINTABLE_STATUS = 'Y';

    /** Status yang masih boleh diberi dokumen bertanda tangan (unggah ulang untuk Y = mengganti file). */
    public const UPLOADABLE_STATUSES = ['R', 'M', 'Y'];

    /** Dokumen bertanda tangan: tipe & ukuran maksimal (KB), folder di disk 'local'. */
    public const SIGNED_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];
    public const SIGNED_MAX_KB = 5120;
    protected const SIGNED_DIR = 'permit_signed';

    /**
     * Pilihan Jam Kerja Work Permit (sesuai form Surat Izin Kerja): kode -> [mulai, selesai].
     * Tidak disimpan sebagai kolom sendiri; diturunkan lagi dari start_time/end_time.
     * 'O' (lain-lain) = jam diisi bebas.
     */
    public const WORK_SHIFTS = [
        'D' => ['10:00', '22:00'],
        'N' => ['22:00', '10:00'],
    ];

    /** Jam operasional pengelola gedung (ditampilkan di form permit). */
    public const OFFICE_HOURS = '08:00 - 17:00';

    protected const AUDIT_USER = 'TWP';

    // ------------------------------------------------------------------
    // Label tampilan (terjemahan); kode di konstanta di atas tetap dipakai untuk logika
    // ------------------------------------------------------------------

    /** TYPES dengan label sesuai bahasa aktif. */
    public static function typeLabels()
    {
        $labels = [];
        foreach (array_keys(self::TYPES) as $code) {
            $labels[$code] = __('shared/permit.types.' . $code);
        }

        return $labels;
    }

    /** STATUSES dengan label sesuai bahasa aktif. */
    public static function statusLabels()
    {
        $labels = [];
        foreach (array_keys(self::STATUSES) as $code) {
            $labels[$code] = __('common.statuses.' . $code);
        }

        return $labels;
    }

    /** Label satu jenis permit (kode asli kalau tidak dikenal). */
    public static function typeLabel($code)
    {
        return isset(self::TYPES[$code]) ? __('shared/permit.types.' . $code) : $code;
    }

    /** Label satu status (kode asli, atau '-' kalau kosong, kalau tidak dikenal). */
    public static function statusLabel($code)
    {
        if (isset(self::STATUSES[$code])) {
            return __('common.statuses.' . $code);
        }

        return $code !== '' ? $code : '-';
    }

    // ------------------------------------------------------------------
    // Bagian yang berbeda per portal
    // ------------------------------------------------------------------

    /** 'tenant' | 'admin' — dipakai untuk URL dan aturan di view. */
    abstract protected function portal();

    /** Layout blade portal ini. */
    abstract protected function layout();

    /** Tenancy yang boleh dipilih di form (tenant: miliknya, admin: semua yang aktif). */
    abstract protected function tenancies();

    /** Daftar tenant_no yang boleh dilihat; null = semua (admin). */
    abstract protected function tenantNos();

    /** Data pemohon: ['name' => ..., 'email' => ..., 'hp' => ...] atau null. */
    abstract protected function applicant();

    protected function isAdmin()
    {
        return $this->portal() === 'admin';
    }

    /** Batasi query ke permit yang boleh dilihat portal ini. */
    protected function scoped($query, $column = 'debtor_acct')
    {
        $nos = $this->tenantNos();

        return $nos === null ? $query : $query->whereIn($column, $nos);
    }

    /** URL dasar modul permit portal ini, mis. '.../tenant/permit'. */
    protected function base($path = '')
    {
        return url($this->portal() . '/permit' . ($path === '' ? '' : '/' . ltrim($path, '/')));
    }

    // ------------------------------------------------------------------
    // Halaman
    // ------------------------------------------------------------------

    /** Form Request Permit. */
    public function index()
    {
        $tenancies = $this->tenancies();
        $first = $tenancies->first();
        $applicant = $this->applicant();

        return view('permit.form', $this->formData([
            'tenancies' => $tenancies,
            'letter_no' => $first ? $this->currentLetterNo($first->entity_cd, $first->project_no) : '',
            'applicant' => $applicant,
        ]));
    }

    /** Form ubah permit. Bagian 1-2 selalu hanya tampilan; bagian lain tergantung portal. */
    public function edit($doc_no)
    {
        $permit = $this->findPermit($doc_no);

        if (!$permit) {
            abort(404, __('shared/permit.not_found'));
        }

        $status = trim((string) $permit['header']->status);
        if (!in_array($status, self::EDITABLE_STATUSES, true)) {
            return redirect($this->base('index'))->with(
                'alert',
                __('shared/permit.cannot_change', ['no' => $doc_no, 'status' => self::statusLabel($status)])
            );
        }

        return view('permit.form', $this->formData([
            'tenancies' => $this->tenancies(),
            'letter_no' => $permit['header']->complain_no,
            'applicant' => $this->applicant(),
            'permit'    => $permit['header'],
            'detail'    => $permit['detail'],
            'lines'     => $permit['lines'],
            'tools'     => $permit['tools'],
            'locked'    => $this->lockedFields($permit['header']->complain_type),
        ]));
    }

    /** Halaman History Permit (isi tabel diambil via table()). */
    public function history()
    {
        return view('permit.history', [
            'layout'    => $this->layout(),
            'portal'    => $this->portal(),
            'is_admin'  => $this->isAdmin(),
            'types'     => self::typeLabels(),
            'statuses'  => self::statusLabels(),
            'editable'  => self::EDITABLE_STATUSES,
            'tenants'   => $this->isAdmin() ? $this->tenancies() : collect(),
        ]);
    }

    /** Data yang dibutuhkan view form (create maupun edit). */
    private function formData(array $extra)
    {
        return array_merge([
            'layout'       => $this->layout(),
            'portal'       => $this->portal(),
            'is_admin'     => $this->isAdmin(),
            'types'        => self::typeLabels(),
            'office_hours' => self::OFFICE_HOURS,
            'work_shifts'  => self::WORK_SHIFTS,
            'tools'        => [],
            'locked'       => [],
        ], $extra);
    }

    /**
     * Field bagian 3 yang tidak boleh diubah portal ini saat update.
     * Admin tidak boleh mengubah bagian 3 sama sekali (hanya bagian 4-6).
     */
    protected function lockedFields($type)
    {
        if (!$this->isAdmin()) {
            return [];
        }

        return $type === 'W'
            ? ['incharge', 'pic_hp', 'contractor', 'job_type']
            : ['owner', 'job_type'];
    }

    // ------------------------------------------------------------------
    // Endpoint AJAX
    // ------------------------------------------------------------------

    /** Nomor permit berikutnya untuk tenancy yang dipilih di form (JSON). */
    public function letterNo($id_tenancy)
    {
        $tenancy = $this->tenancyInScope($id_tenancy);

        return response()->json([
            'letter_no' => $tenancy ? $this->currentLetterNo($tenancy->entity_cd, $tenancy->project_no) : '',
        ]);
    }

    /** Daftar <option> unit milik tenancy yang dipilih. */
    public function lots($id_tenancy)
    {
        $tenancy = $this->tenancyInScope($id_tenancy);

        if (!$tenancy) {
            return response('<option value=""></option>');
        }

        $lots = DB::connection('dblive')
            ->table('mgr.pm_lot AS l')
            ->join('mgr.pm_tenant_lot AS tl', function ($join) {
                $join->on('l.entity_cd', '=', 'tl.entity_cd')
                    ->on('l.project_no', '=', 'tl.project_no')
                    ->on('l.lot_no', '=', 'tl.lot_no');
            })
            ->where('l.entity_cd', $tenancy->entity_cd)
            ->where('l.project_no', $tenancy->project_no)
            ->where('tl.tenant_no', $tenancy->tenant_no)
            ->orderBy('l.lot_no')
            ->select('l.lot_no', 'l.level_no')
            ->get();

        if ($lots->isEmpty()) {
            return response('<option value="">' . e(__('shared/permit.no_unit')) . '</option>');
        }

        $html = '<option value=""></option>';
        foreach ($lots as $lot) {
            $html .= '<option data-level="' . e($lot->level_no) . '" value="' . e($lot->lot_no) . '">' . e($lot->lot_no) . '</option>';
        }

        return response($html);
    }

    // ------------------------------------------------------------------
    // Simpan permit baru
    // ------------------------------------------------------------------

    /**
     * Simpan permit (semua jenis). Dipanggil via AJAX, balasan JSON
     * {status: OK|Fail, pesan, permit_no?, errors?}.
     */
    public function save(Request $request)
    {
        $type = $request->input('permit_type');

        $this->normalizeDates($request);
        $this->applyWorkShift($request, $type);
        $validator = $this->permitValidator($request, $type);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        try {
            $ctx = $this->permitContext($request->tenant_no, $request->lot_no);
            if (is_string($ctx)) {
                return $this->fail($ctx, 422);
            }

            $doc_no = DB::connection('dblive')->transaction(function () use ($ctx, $request, $type) {
                // Nomor permit diambil di dalam transaksi dengan lock baris sv_spec,
                // jadi dua request bersamaan tidak pernah mendapat nomor yang sama.
                $ctx['doc_no'] = $this->takeLetterNo($ctx['entity_cd'], $ctx['project_no']);

                $rows = $type === 'W'
                    ? $this->workPermitRows($ctx, $request)
                    : $this->goodsPermitRows($ctx, $request, $type);

                $db = DB::connection('dblive');
                $db->table('mgr.sv_entry_letter')->insert($rows['header']);
                $db->table($rows['detail_table'])->insert($rows['detail']);
                $db->table($rows['lines_table'])->insert($rows['lines']);
                if (isset($rows['tools_table'])) {
                    $db->table($rows['tools_table'])->insert($rows['tools']);
                }
                $this->writeLog($ctx, 'Request created by ' . $this->portal());

                return $ctx['doc_no'];
            });

            return response()->json([
                'status'    => 'OK',
                'pesan'     => __('shared/permit.submitted', ['type' => self::typeLabel($type), 'no' => $doc_no]),
                'permit_no' => $doc_no,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('save', $e, __('shared/permit.save_failed'));
        }
    }

    // ------------------------------------------------------------------
    // Ubah permit
    // ------------------------------------------------------------------

    /**
     * Simpan perubahan permit. Jenis permit, nomor, tenant, unit dan lantai tidak ikut
     * diubah; admin juga tidak boleh mengubah field bagian 3.
     * Status: tenant -> M (Modify); admin -> Y/X kalau dipilih, selain itu M.
     */
    public function update(Request $request)
    {
        $permit = $this->findPermit($request->input('doc_no'));

        if (!$permit) {
            return $this->fail(__('shared/permit.not_found'), 404);
        }

        $header = $permit['header'];
        $status = trim((string) $header->status);

        if (!in_array($status, self::EDITABLE_STATUSES, true)) {
            return $this->fail(__('shared/permit.cannot_change', ['no' => $header->complain_no, 'status' => self::statusLabel($status)]), 422);
        }

        // Jenis permit mengikuti data tersimpan, bukan kiriman form.
        $type = $header->complain_type;
        $request->merge(['permit_type' => $type]);

        // Field yang tidak boleh diubah portal ini: pakai nilai yang tersimpan.
        $locked = $this->lockedFields($type);
        $request->merge($this->storedValues($permit, $locked));
        $this->normalizeDates($request);
        $this->applyWorkShift($request, $type);

        $validator = $this->permitValidator($request, $type, true, $locked);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        // Menyimpan perubahan selalu -> Modify. Approve hanya lewat upload dokumen
        // bertanda tangan (uploadSigned), Cancel lewat tombol di History (cancel()).
        $newStatus = 'M';
        $remarks = 'Modified by ' . $this->portal();

        try {
            $ctx = $this->contextOf($permit);

            // floor & jenis tidak berubah: dipakai headerRow() lewat request
            $request->merge(['floor' => $permit['detail']->floor ?? $header->floor]);

            DB::connection('dblive')->transaction(function () use ($ctx, $request, $type, $permit, $newStatus, $remarks) {
                $rows = $type === 'W'
                    ? $this->workPermitRows($ctx, $request)
                    : $this->goodsPermitRows($ctx, $request, $type);

                // Kolom kunci & data pemohon tidak ikut diubah; status diisi nilai baru.
                $lockedHeader = ['entity_cd', 'project_no', 'debtor_acct', 'complain_type', 'complain_no',
                    'reported_by', 'reported_date', 'floor', 'serv_req_by', 'contact_no', 'billing_type',
                    'status', 'complain_source', 'lot_no', 'post_status'];
                $lockedDetail = ['entity_cd', 'project_no', 'doc_no', 'member_email', 'member_name', 'member_hp',
                    'debtor_acct', 'tower', 'floor', 'unit'];

                $db = DB::connection('dblive');
                $keys = $permit['keys'];

                $db->table('mgr.sv_entry_letter')
                    ->where('entity_cd', $keys['entity_cd'])
                    ->where('project_no', $keys['project_no'])
                    ->where('complain_no', $keys['doc_no'])
                    ->update(array_diff_key($rows['header'], array_flip($lockedHeader)) + ['status' => $newStatus]);

                $db->table($rows['detail_table'])->where($keys)
                    ->update(array_diff_key($rows['detail'], array_flip($lockedDetail)));

                // Daftar pekerja / barang ditulis ulang
                $db->table($rows['lines_table'])->where($keys)->delete();
                $db->table($rows['lines_table'])->insert($rows['lines']);

                // Kegiatan & peralatan Work Permit juga ditulis ulang
                if (isset($rows['tools_table'])) {
                    $db->table($rows['tools_table'])->where($keys)->delete();
                    $db->table($rows['tools_table'])->insert($rows['tools']);
                }

                $this->writeLog($ctx, $remarks);
            });

            return response()->json([
                'status'    => 'OK',
                'pesan'     => __('shared/permit.updated.' . $newStatus, ['type' => self::typeLabel($type), 'no' => $ctx['doc_no']]),
                'permit_no' => $ctx['doc_no'],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('update', $e, __('shared/permit.update_failed'));
        }
    }

    /**
     * Batalkan permit dari halaman History (tanpa membuka form).
     * Hanya permit yang belum disetujui / dibatalkan.
     */
    public function cancel(Request $request)
    {
        $permit = $this->findPermit($request->input('doc_no'));

        if (!$permit) {
            return $this->fail(__('shared/permit.not_found'), 404);
        }

        $header = $permit['header'];
        $status = trim((string) $header->status);

        if (!in_array($status, self::EDITABLE_STATUSES, true)) {
            return $this->fail(__('shared/permit.cannot_cancel', ['no' => $header->complain_no, 'status' => self::statusLabel($status)]), 422);
        }

        try {
            $ctx = $this->contextOf($permit);

            DB::connection('dblive')->transaction(function () use ($ctx, $permit) {
                $keys = $permit['keys'];

                DB::connection('dblive')->table('mgr.sv_entry_letter')
                    ->where('entity_cd', $keys['entity_cd'])
                    ->where('project_no', $keys['project_no'])
                    ->where('complain_no', $keys['doc_no'])
                    ->update([
                        'status'     => 'X',
                        'audit_user' => self::AUDIT_USER,
                        'audit_date' => $ctx['audit_date'],
                    ]);

                $this->writeLog($ctx, 'Cancelled by ' . $this->portal());
            });

            return response()->json([
                'status'    => 'OK',
                'pesan'     => __('shared/permit.cancelled', ['no' => $ctx['doc_no']]),
                'permit_no' => $ctx['doc_no'],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('cancel', $e, __('shared/permit.cancel_failed'));
        }
    }

    /** Nilai tersimpan untuk field yang tidak boleh diubah portal ini. */
    private function storedValues(array $permit, array $fields)
    {
        $detail = $permit['detail'];
        $map = [
            'incharge'   => $detail->pic_name ?? null,
            'pic_hp'     => $detail->pic_hp ?? null,
            'contractor' => $detail->kontraktor_name ?? null,
            'job_type'   => $detail->work_type ?? null,
            'work_tool'  => $detail->work_tools ?? null,
            'owner'      => $detail->owner_name ?? null,
        ];

        $values = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $map)) {
                $values[$field] = $map[$field];
            }
        }

        return $values;
    }

    /** Konteks (kunci + data pemohon) dari permit yang sudah tersimpan. */
    private function contextOf(array $permit)
    {
        $header = $permit['header'];
        $detail = $permit['detail'];

        return [
            'entity_cd'    => $header->entity_cd,
            'project_no'   => $header->project_no,
            'debtor_acct'  => $header->debtor_acct,
            'doc_no'       => $header->complain_no,
            'lot_no'       => $header->lot_no,
            'tower'        => $detail->tower ?? null,
            'member_name'  => $detail->member_name ?? $header->serv_req_by,
            'member_email' => $detail->member_email ?? null,
            'member_hp'    => $detail->member_hp ?? $header->contact_no,
            'audit_date'   => now()->format('Y-m-d\TH:i:s'),
        ];
    }

    /** Satu baris log baru (tidak pernah di-update) untuk tiap perubahan status. */
    private function writeLog(array $ctx, $remarks)
    {
        DB::connection('dblive')->table('mgr.sv_entry_letter_log')->insert([
            'entity_cd'   => $ctx['entity_cd'],
            'project_no'  => $ctx['project_no'],
            'complain_no' => $ctx['doc_no'],
            'remarks'     => $remarks,
            'audit_user'  => self::AUDIT_USER,
            'audit_date'  => $ctx['audit_date'],
        ]);
    }

    // ------------------------------------------------------------------
    // Validasi & pembentukan baris
    // ------------------------------------------------------------------

    /**
     * Validator isi form permit. Saat update, Tenant/Unit/Floor (bagian 1-2) tidak ikut
     * dikirim, dan field yang terkunci untuk portal ini tidak divalidasi (nilainya
     * diambil dari data tersimpan).
     */
    private function permitValidator(Request $request, $type, $isUpdate = false, array $locked = [])
    {
        $rules = [
            'permit_type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'note'        => ['required', 'string', 'max:500'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
        ];

        if (!$isUpdate) {
            $rules += [
                'tenant_no' => ['required', 'integer'],
                'lot_no'    => ['required', 'string', 'max:8'],
                'floor'     => ['required', 'string', 'max:5'],
            ];
        }

        if ($type === 'W') {
            // Jam kerja boleh melewati tengah malam (22.00 - 10.00), jadi end_time
            // cukup berbeda dari start_time, tidak harus lebih besar.
            // Baris kegiatan: tool_activity[i] + tool_name[i] (+ tool_remarks[i] opsional).
            $rules += [
                'incharge'        => ['required', 'string', 'max:50'],
                'pic_hp'          => ['required', 'string', 'max:20'],
                'contractor'      => ['required', 'string', 'max:50'],
                'job_type'        => ['required', 'string', 'max:50'],
                'work_shift'      => ['required', 'in:' . implode(',', array_keys(self::WORK_SHIFTS)) . ',O'],
                'start_time'      => ['required', 'date_format:H:i,H:i:s'],
                'end_time'        => ['required', 'date_format:H:i,H:i:s', 'different:start_time'],
                'worker_name'     => ['required', 'array', 'min:1'],
                'worker_name.*'   => ['required', 'string', 'max:50'],
                'tool_activity'   => ['required', 'array', 'min:1'],
                'tool_activity.*' => ['required', 'string', 'max:100'],
                'tool_name'       => ['required', 'array', 'size:' . count((array) $request->input('tool_activity'))],
                'tool_name.*'     => ['required', 'string', 'max:100'],
                'tool_remarks'    => ['nullable', 'array'],
                'tool_remarks.*'  => ['nullable', 'string', 'max:255'],
            ];
        } else {
            // Jam keluar/masuk barang juga boleh lewat tengah malam (aturan: 22.00 - 10.00).
            // Baris barang: item_name[i] + item_qty[i] (+ item_remarks[i] opsional).
            $rules += [
                'owner'          => ['required', 'string', 'max:50'],
                'job_type'       => ['required', 'string', 'max:50'],
                'start_time'     => ['required', 'date_format:H:i,H:i:s'],
                'end_time'       => ['required', 'date_format:H:i,H:i:s', 'different:start_time'],
                'sender_name'    => ['required', 'string', 'max:50'],
                'sender_id_no'   => ['required', 'string', 'max:30'],
                'sender_address' => ['required', 'string', 'max:255'],
                'sender_hp'      => ['required', 'string', 'max:20'],
                'vehicle_type'   => ['required', 'string', 'max:30'],
                'vehicle_no'     => ['required', 'string', 'max:10'],
                'item_name'      => ['required', 'array', 'min:1'],
                'item_name.*'    => ['required', 'string', 'max:100'],
                'item_qty'       => ['required', 'array', 'size:' . count((array) $request->input('item_name'))],
                'item_qty.*'     => ['required', 'string', 'max:20'],
                'item_remarks'   => ['nullable', 'array'],
                'item_remarks.*' => ['nullable', 'string', 'max:255'],
            ];
        }

        $rules = array_diff_key($rules, array_flip($locked));

        $attr = function ($key) {
            return __('shared/permit.attributes.' . $key);
        };

        return Validator::make($request->all(), $rules, [], [
            'permit_type'   => $attr('permit_type'),
            'note'          => $attr('note'),
            'start_date'    => $attr('start_date'),
            'end_date'      => $attr('end_date'),
            'start_time'    => $attr('start_time'),
            'end_time'      => $attr('end_time'),
            'tenant_no'     => $attr('tenant_no'),
            'lot_no'        => $attr('lot_no'),
            'floor'         => $attr('floor'),
            'incharge'      => $attr('incharge'),
            'pic_hp'        => $attr('pic_hp'),
            'contractor'    => $attr('contractor'),
            'job_type'      => $attr('job_type'),
            'work_shift'    => $attr('work_shift'),
            'tool_activity'   => $attr('activity'),
            'tool_activity.*' => $attr('activity'),
            'tool_name'       => $attr('tools_ppe'),
            'tool_name.*'     => $attr('tools_ppe'),
            'tool_remarks.*'  => $attr('remarks'),
            'worker_name'   => $attr('worker'),
            'worker_name.*' => $attr('worker_name'),
            'owner'          => $attr('owner'),
            'sender_name'    => $attr('sender_name'),
            'sender_id_no'   => $attr('sender_id_no'),
            'sender_address' => $attr('sender_address'),
            'sender_hp'      => $attr('sender_hp'),
            'vehicle_type'   => $attr('vehicle_type'),
            'vehicle_no'     => $attr('vehicle_no'),
            'item_name'      => $attr('item'),
            'item_name.*'    => $attr('item_name'),
            'item_qty'       => $attr('quantity'),
            'item_qty.*'     => $attr('quantity'),
            'item_remarks.*' => $attr('remarks'),
        ]);
    }

    /** Baris-baris Work Permit: header sv_entry_letter, permit_letter_hd, permit_letter_dtl. */
    private function workPermitRows(array $ctx, Request $request)
    {
        $start_date = $this->fmtDate($request->start_date);
        $end_date   = $this->fmtDate($request->end_date);
        $start_time = substr($request->start_time, 0, 5);
        $end_time   = substr($request->end_time, 0, 5);

        // Kegiatan + peralatan / APD per baris; kolom work_tool(s) lama diisi ringkasannya.
        $toolNames = array_values((array) $request->tool_name);
        $remarks   = array_values((array) $request->tool_remarks);
        $tools = [];
        foreach (array_values((array) $request->tool_activity) as $i => $activity) {
            $remark = trim((string) ($remarks[$i] ?? ''));
            $tools[] = $this->lineRow($ctx) + [
                'activity'  => trim($activity),
                'tool_name' => trim((string) ($toolNames[$i] ?? '')),
                'remarks'   => $remark === '' ? null : $remark,
            ];
        }
        $summary = mb_substr(implode(', ', array_unique(array_column($tools, 'tool_name'))), 0, 255);

        $header = $this->headerRow($ctx, 'W', $request) + [
            'pj_name'    => $request->incharge,
            'contractor' => $request->contractor,
            'job_type'   => $request->job_type,
            'work_tool'  => $summary,
            'start_time' => $start_time,
            'end_time'   => $end_time,
        ];

        $detail = [
            'entity_cd'       => $ctx['entity_cd'],
            'project_no'      => $ctx['project_no'],
            'doc_no'          => $ctx['doc_no'],
            'member_email'    => $ctx['member_email'],
            'member_name'     => $ctx['member_name'],
            'member_hp'       => $ctx['member_hp'],
            'debtor_acct'     => $ctx['debtor_acct'],
            'pic_name'        => $request->incharge,
            'pic_hp'          => $request->pic_hp,
            'kontraktor_name' => $request->contractor,
            'tower'           => $ctx['tower'],
            'floor'           => $request->floor,
            'unit'            => $ctx['lot_no'],
            'work_type'       => $request->job_type,
            'work_tools'      => $summary,
            'start_day'       => date('l', strtotime($request->start_date)),
            'end_day'         => date('l', strtotime($request->end_date)),
            'start_date'      => $start_date,
            'end_date'        => $end_date,
            'start_time'      => $start_time,
            'end_time'        => $end_time,
            'note'            => $request->note,
            'audit_user'      => self::AUDIT_USER,
            'audit_date'      => $ctx['audit_date'],
        ];

        $lines = [];
        foreach ((array) $request->worker_name as $name) {
            $lines[] = $this->lineRow($ctx) + ['staff_name' => trim($name)];
        }

        return [
            'header'       => $header,
            'detail_table' => 'mgr.permit_letter_hd',
            'detail'       => $detail,
            'lines_table'  => 'mgr.permit_letter_dtl',
            'lines'        => $lines,
            'tools_table'  => 'mgr.permit_letter_tools',
            'tools'        => $tools,
        ];
    }

    /** Baris-baris Entry/Exit Permit of Goods: header sv_entry_letter, permit_goods_hd, permit_goods_dtl. */
    private function goodsPermitRows(array $ctx, Request $request, $type)
    {
        $start_time = substr($request->start_time, 0, 5);
        $end_time   = substr($request->end_time, 0, 5);

        // company_name tidak ada lagi di form (form kertas tidak memuatnya) -> null.
        $header = $this->headerRow($ctx, $type, $request) + [
            'owner_name' => $request->owner,
            'job_type'   => $request->job_type,
            'vehicle_no' => $request->vehicle_no,
            'start_time' => $start_time,
            'end_time'   => $end_time,
        ];

        $detail = [
            'entity_cd'      => $ctx['entity_cd'],
            'project_no'     => $ctx['project_no'],
            'doc_no'         => $ctx['doc_no'],
            'member_email'   => $ctx['member_email'],
            'member_name'    => $ctx['member_name'],
            'member_hp'      => $ctx['member_hp'],
            'debtor_acct'    => $ctx['debtor_acct'],
            'owner_name'     => $request->owner,
            'tower'          => $ctx['tower'],
            'floor'          => $request->floor,
            'unit'           => $ctx['lot_no'],
            'start_date'     => $this->fmtDate($request->start_date),
            'end_date'       => $this->fmtDate($request->end_date),
            'start_time'     => $start_time,
            'end_time'       => $end_time,
            'sender_name'    => $request->sender_name,
            'sender_id_no'   => $request->sender_id_no,
            'sender_address' => $request->sender_address,
            'sender_hp'      => $request->sender_hp,
            'vehicle_type'   => $request->vehicle_type,
            'vehicle_no'     => $request->vehicle_no,
            'work_type'      => $request->job_type,
            'note'           => $request->note,
            'audit_user'     => self::AUDIT_USER,
            'audit_date'     => $ctx['audit_date'],
        ];

        // Jenis barang + jumlah + keterangan (item_descs) per baris.
        $qtys    = array_values((array) $request->item_qty);
        $remarks = array_values((array) $request->item_remarks);
        $lines = [];
        foreach (array_values((array) $request->item_name) as $i => $name) {
            $remark = trim((string) ($remarks[$i] ?? ''));
            $lines[] = $this->lineRow($ctx) + [
                'item_name'  => trim($name),
                'item_qty'   => trim((string) ($qtys[$i] ?? '')),
                'item_descs' => $remark === '' ? null : $remark,
            ];
        }

        return [
            'header'       => $header,
            'detail_table' => 'mgr.permit_goods_hd',
            'detail'       => $detail,
            'lines_table'  => 'mgr.permit_goods_dtl',
            'lines'        => $lines,
        ];
    }

    /** Kolom sv_entry_letter yang sama untuk semua jenis permit. */
    private function headerRow(array $ctx, $type, Request $request)
    {
        return [
            'entity_cd'       => $ctx['entity_cd'],
            'project_no'      => $ctx['project_no'],
            'debtor_acct'     => $ctx['debtor_acct'],
            'complain_type'   => $type,
            'complain_no'     => $ctx['doc_no'],
            'reported_by'     => self::AUDIT_USER,
            'reported_date'   => $ctx['audit_date'],
            'floor'           => $request->floor,
            'serv_req_by'     => $ctx['member_name'],
            'contact_no'      => $ctx['member_hp'],
            'billing_type'    => 'T',
            'status'          => 'R',
            'audit_user'      => self::AUDIT_USER,
            'audit_date'      => $ctx['audit_date'],
            'complain_source' => 'TWP PERMIT',
            'lot_no'          => $ctx['lot_no'],
            'post_status'     => 'N',
            'note'            => $request->note,
            'start_date'      => $this->fmtDate($request->start_date),
            'end_date'        => $this->fmtDate($request->end_date),
        ];
    }

    /** Kolom kunci baris detail pekerja / barang. */
    private function lineRow(array $ctx)
    {
        return [
            'entity_cd'   => $ctx['entity_cd'],
            'project_no'  => $ctx['project_no'],
            'doc_no'      => $ctx['doc_no'],
            'debtor_acct' => $ctx['debtor_acct'],
            'lot_no'      => $ctx['lot_no'],
            'audit_user'  => self::AUDIT_USER,
            'audit_date'  => $ctx['audit_date'],
        ];
    }

    /**
     * Data bersama permit baru: tenancy yang dipilih (dicek cakupannya), tower dari unit,
     * dan data pemohon. Nomor dokumen diambil belakangan di dalam transaksi.
     * Mengembalikan array, atau string pesan error kalau ada yang tidak valid.
     */
    private function permitContext($id_tenancy, $lot_no)
    {
        $applicant = $this->applicant();
        if (!$applicant) {
            return __('shared/permit.applicant_not_found');
        }

        $tenancy = $this->tenancyInScope($id_tenancy);
        if (!$tenancy) {
            return __('shared/permit.tenant_invalid');
        }

        // Tower (block_no) dari unit yang dipilih
        $lot = DB::connection('dblive')->table('mgr.pm_lot')
            ->where('entity_cd', $tenancy->entity_cd)
            ->where('project_no', $tenancy->project_no)
            ->where('lot_no', $lot_no)
            ->select('block_no')
            ->first();

        if (!$lot) {
            return __('shared/permit.tower_not_found');
        }

        return [
            'entity_cd'    => $tenancy->entity_cd,
            'project_no'   => $tenancy->project_no,
            'debtor_acct'  => $tenancy->tenant_no,
            'lot_no'       => $lot_no,
            'tower'        => $lot->block_no,
            // Panjang kolom di SQL Server: member_name varchar(50), member_hp/contact_no varchar(20)
            'member_name'  => mb_substr((string) $applicant['name'], 0, 50),
            'member_email' => mb_substr((string) $applicant['email'], 0, 60),
            'member_hp'    => mb_substr((string) $applicant['hp'], 0, 20),
            'audit_date'   => now()->format('Y-m-d\TH:i:s'),   // ISO 8601 dengan 'T': tidak terpengaruh DATEFORMAT
        ];
    }

    /**
     * Header + detail + daftar satu permit yang boleh dilihat portal ini.
     * null kalau tidak ada / di luar cakupan.
     */
    protected function findPermit($doc_no)
    {
        $db = DB::connection('dblive');

        $header = $this->scoped(
            $db->table('mgr.sv_entry_letter')->where('complain_no', $doc_no),
            'debtor_acct'
        )->whereIn('complain_type', array_keys(self::TYPES))->first();

        if (!$header) {
            return null;
        }

        $keys = [
            'entity_cd'  => $header->entity_cd,
            'project_no' => $header->project_no,
            'doc_no'     => $header->complain_no,
        ];

        $tools = [];

        if ($header->complain_type === 'W') {
            $detail = $db->table('mgr.permit_letter_hd')->where($keys)->first();
            $lines  = $db->table('mgr.permit_letter_dtl')->where($keys)->orderBy('rowID')->pluck('staff_name')->all();
            $tools  = $db->table('mgr.permit_letter_tools')->where($keys)->orderBy('rowID')
                ->get(['activity', 'tool_name', 'remarks'])
                ->map(function ($t) {
                    return [
                        'activity'  => trim((string) $t->activity),
                        'tool_name' => trim((string) $t->tool_name),
                        'remarks'   => trim((string) $t->remarks),
                    ];
                })->all();

            // Permit lama (sebelum ada permit_letter_tools): satu baris dari work_type + work_tools.
            if (!$tools && $detail && trim((string) $detail->work_tools) !== '') {
                $tools = [[
                    'activity'  => trim((string) $detail->work_type),
                    'tool_name' => trim((string) $detail->work_tools),
                    'remarks'   => '',
                ]];
            }
        } else {
            // Barang: ['item_name', 'item_qty', 'remarks']. Data lama mengisi item_descs sama
            // dengan item_name, jadi yang seperti itu tidak dianggap keterangan.
            $detail = $db->table('mgr.permit_goods_hd')->where($keys)->first();
            $lines  = $db->table('mgr.permit_goods_dtl')->where($keys)->orderBy('rowID')
                ->get(['item_name', 'item_qty', 'item_descs'])
                ->map(function ($t) {
                    $name  = trim((string) $t->item_name);
                    $descs = trim((string) $t->item_descs);
                    return [
                        'item_name' => $name,
                        'item_qty'  => trim((string) $t->item_qty),
                        'remarks'   => $descs === $name ? '' : $descs,
                    ];
                })->all();
        }

        // lines = nama pekerja (W) atau baris barang (I/O); tools = kegiatan & peralatan (W).
        return ['header' => $header, 'detail' => $detail, 'lines' => $lines, 'tools' => $tools, 'keys' => $keys];
    }

    /**
     * Jam Kerja Work Permit: pilihan 10.00-22.00 / 22.00-10.00 mengisi start_time & end_time
     * dari WORK_SHIFTS (jam kiriman form diabaikan); 'O' (lain-lain) memakai jam yang diisi.
     */
    /**
     * start_date / end_date dari datepicker (dd/mm/yyyy) -> Y-m-d sebelum validasi, supaya
     * aturan date / after_or_equal dan fmtDate() membaca tanggal yang benar.
     * Tanggal tidak valid dibiarkan apa adanya sehingga validasi 'date' menolaknya.
     */
    private function normalizeDates(Request $request)
    {
        foreach (['start_date', 'end_date'] as $field) {
            $ymd = \App\Support\DateInput::format($request->input($field));
            if ($ymd) {
                $request->merge([$field => $ymd]);
            }
        }
    }

    private function applyWorkShift(Request $request, $type)
    {
        if ($type !== 'W') {
            return;
        }

        $shift = strtoupper(trim((string) $request->input('work_shift')));
        if (isset(self::WORK_SHIFTS[$shift])) {
            $request->merge([
                'start_time' => self::WORK_SHIFTS[$shift][0],
                'end_time'   => self::WORK_SHIFTS[$shift][1],
            ]);
        }
    }

    /** Kode Jam Kerja (D / N / O = lain-lain) dari jam tersimpan. */
    public static function workShiftOf($start_time, $end_time)
    {
        $times = [substr(trim((string) $start_time), 0, 5), substr(trim((string) $end_time), 0, 5)];

        foreach (self::WORK_SHIFTS as $code => $range) {
            if ($range === $times) {
                return $code;
            }
        }

        return 'O';
    }

    /** Nomor permit yang akan dipakai berikutnya (hanya untuk ditampilkan di form). */
    private function currentLetterNo($entity_cd, $project_no)
    {
        return (string) DB::connection('dblive')->table('mgr.sv_spec')
            ->where('entity_cd', $entity_cd)
            ->where('project_no', $project_no)
            ->value('letter_no');
    }

    /**
     * Ambil nomor permit dan naikkan sv_spec.letter_no (LP100001 -> LP100002) secara atomik.
     * Harus dipanggil di dalam transaksi: baris sv_spec dikunci (updlock/holdlock) sampai commit,
     * sehingga request lain menunggu dan mendapat nomor berikutnya, bukan nomor yang sama.
     */
    private function takeLetterNo($entity_cd, $project_no)
    {
        $db = DB::connection('dblive');

        $spec = $db->table('mgr.sv_spec')
            ->where('entity_cd', $entity_cd)
            ->where('project_no', $project_no)
            ->lockForUpdate()
            ->first();

        $doc_no = trim((string) ($spec->letter_no ?? ''));
        if ($doc_no === '') {
            throw new \RuntimeException('Permit number (sv_spec.letter_no) not found for ' . trim($entity_cd) . ' / ' . trim($project_no));
        }

        $updated = $db->table('mgr.sv_spec')
            ->where('entity_cd', $entity_cd)
            ->where('project_no', $project_no)
            ->where('letter_no', $doc_no)
            ->update(['letter_no' => $this->nextLetterNo($doc_no)]);

        if ($updated !== 1) {
            throw new \RuntimeException('Permit number ' . $doc_no . ' has already been used, please submit again.');
        }

        return $doc_no;
    }

    /** LP100001 -> LP100002 (prefix & jumlah digit dipertahankan). */
    private function nextLetterNo($letter_no)
    {
        if (!preg_match('/^(.*?)(\d+)$/', $letter_no, $m)) {
            throw new \RuntimeException('Invalid permit number format: ' . $letter_no);
        }

        return $m[1] . str_pad((int) $m[2] + 1, strlen($m[2]), '0', STR_PAD_LEFT);
    }

    /** Baris pm_tenancy yang dipilih di combo (value = pm_tenancy.id), hanya kalau masuk cakupan. */
    protected function tenancyInScope($id_tenancy)
    {
        $tenancy = DB::table('mgr.pm_tenancy')->where('id', (int) $id_tenancy)->first();

        if (!$tenancy) {
            return null;
        }

        $nos = $this->tenantNos();

        return ($nos === null || in_array((string) $tenancy->tenant_no, $nos, true)) ? $tenancy : null;
    }

    /**
     * Tanggal untuk kolom datetime SQL Server, format yyyymmdd.
     * Untuk tipe datetime, 'dd/mm/yyyy', 'yyyy-mm-dd' dan 'yyyy-mm-dd hh:mm:ss' semuanya
     * ikut SET DATEFORMAT sesi (koneksi ODBC ini dmy -> '2026-09-21' dibaca y-d-m, bulan 21).
     * Hanya 'yyyymmdd' dan 'yyyy-mm-ddThh:mm:ss' yang selalu dibaca benar.
     */
    private function fmtDate($date)
    {
        return $date ? date('Ymd', strtotime($date)) : null;
    }

    private function fail($pesan, $code = 500, array $errors = [])
    {
        $body = ['status' => 'Fail', 'pesan' => $pesan];
        if ($errors) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $code);
    }

    /** Error tak terduga: dicatat di log, detailnya hanya ditampilkan saat APP_DEBUG. */
    private function serverError($method, \Throwable $e, $message)
    {
        Log::error(static::class . '::' . $method . ': ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return $this->fail(config('app.debug') ? __('common.error_occurred', ['message' => $e->getMessage()]) : $message, 500);
    }

    // ------------------------------------------------------------------
    // Tabel & cetak
    // ------------------------------------------------------------------

    /** Sumber data DataTables (server side) untuk History Permit. */
    public function table(Request $request)
    {
        // Detail permit ada di dua tabel: permit_letter_hd (Work Permit) dan
        // permit_goods_hd (Entry/Exit Permit of Goods), jadi keduanya ikut di-join
        // dan tower/floor/unit diambil dari mana pun yang terisi.
        $permit = DB::connection('dblive')
            ->table('mgr.sv_entry_letter as sel')
            ->leftJoin('mgr.permit_letter_hd as plh', function ($join) {
                $join->on('sel.entity_cd', '=', 'plh.entity_cd')
                    ->on('sel.project_no', '=', 'plh.project_no')
                    ->on('sel.complain_no', '=', 'plh.doc_no');
            })
            ->leftJoin('mgr.permit_goods_hd as pgh', function ($join) {
                $join->on('sel.entity_cd', '=', 'pgh.entity_cd')
                    ->on('sel.project_no', '=', 'pgh.project_no')
                    ->on('sel.complain_no', '=', 'pgh.doc_no');
            })
            ->whereIn('sel.complain_type', array_keys(self::TYPES))
            ->select(
                'sel.entity_cd as entity_cd',
                'sel.project_no as project_no',
                'sel.complain_no as complain_no',
                'sel.complain_type as complain_type',
                'sel.debtor_acct as debtor_acct',
                'sel.note as note',
                'sel.start_time as start_time',
                'sel.end_time as end_time',
                'sel.start_date as start_date',
                'sel.end_date as end_date',
                'sel.status as status',
                'sel.audit_date as audit_date',
                DB::raw('COALESCE(plh.tower, pgh.tower) as tower'),
                DB::raw('COALESCE(plh.floor, pgh.floor, sel.floor) as floor'),
                DB::raw('COALESCE(plh.unit, pgh.unit, sel.lot_no) as unit')
            );

        $this->scoped($permit, 'sel.debtor_acct');

        // Filter opsional, boleh dipakai sendiri-sendiri atau digabung. Kalau semuanya kosong
        // (kondisi saat halaman pertama dibuka) seluruh permit dalam cakupan ikut tampil.
        $permit_no   = trim((string) $request->permit_no);
        $permit_type = strtoupper(trim((string) $request->permit_type));
        $status      = strtoupper(trim((string) $request->status));
        $tenant      = trim((string) $request->tenant_no);
        $start_date  = $this->parseDmy($request->start_date);

        if ($permit_no !== '') {
            $permit->where('sel.complain_no', 'like', '%' . $permit_no . '%');
        }

        if (isset(self::TYPES[$permit_type])) {
            $permit->where('sel.complain_type', $permit_type);
        }

        if (isset(self::STATUSES[$status])) {
            $permit->where('sel.status', $status);
        }

        if ($this->isAdmin() && $tenant !== '') {
            $permit->where('sel.debtor_acct', $tenant);
        }

        if ($start_date) {
            // Kolom datetime, jadi diambil satu hari penuh.
            $permit->where('sel.start_date', '>=', $this->fmtDate($start_date))
                ->where('sel.start_date', '<', $this->fmtDate($start_date . ' +1 day'));
        }

        // Dibungkus jadi subquery supaya kolom hasil join (note, floor, start_date, ...)
        // tidak ambigu saat DataTables melakukan search / order per kolom.
        // Tanpa order di sini: SQL Server menolak ORDER BY di derived table, dan query
        // count milik DataTables membungkus query ini lagi. Urutan diterapkan lewat
        // callback order() di bawah.
        $query = DB::connection('dblive')
            ->query()
            ->fromSub($permit, 'p');

        // escapeColumns([]): isi kolom dikirim apa adanya, karena history.blade sudah
        // meng-escape tiap kolom saat render (esc/dash). Kalau server juga meng-escape,
        // '&' tampil sebagai '&amp;'.
        $isAdmin = $this->isAdmin();

        return DataTables::of($query)
            ->escapeColumns([])
            ->addIndexColumn()
            // tombol di kolom Action (aturan yang sama dicek lagi di server saat diklik)
            ->addColumn('can_print', function ($row) use ($isAdmin) {
                return self::canPrint(trim((string) $row->status), $isAdmin);
            })
            ->addColumn('can_upload', function ($row) use ($isAdmin) {
                return $isAdmin && in_array(trim((string) $row->status), self::UPLOADABLE_STATUSES, true);
            })
            ->addColumn('has_signed', function ($row) {
                return $this->signedPath($row) !== null;
            })
            ->order(function ($query) use ($request) {
                // Kalau user klik header kolom, ikuti urutan itu. Kalau tidak
                // (halaman baru dibuka), urutkan dari tanggal mulai terbaru, lalu
                // nomor permit terbesar untuk tanggal yang sama.
                $orders = (array) $request->input('order', []);
                if (empty($orders)) {
                    $query->orderBy('start_date', 'desc')->orderBy('complain_no', 'desc');
                    return;
                }

                foreach ($orders as $order) {
                    $column = $request->input('columns.' . ($order['column'] ?? '') . '.data');
                    if ($column && $column !== 'DT_RowIndex') {
                        $query->orderBy($column, strtolower($order['dir'] ?? '') === 'asc' ? 'asc' : 'desc');
                    }
                }
            })
            ->make(true);
    }

    /** 'dd/mm/yyyy' -> 'yyyy-mm-dd'; null kalau kosong atau format lain. */
    private function parseDmy($date)
    {
        if (!$date || !preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', trim($date), $m)) {
            return null;
        }

        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    /*
     * Dokumen permit dibuka lewat dua halaman:
     *   /permit/unsigned/{no}  formulir yang belum ditandatangani  -> unsignedPage()
     *   /permit/signed/{no}    dokumen bertanda tangan (upload)    -> signedPage()
     * Keduanya halaman HTML kecil (permit.print_frame) yang memuat file-nya dari
     * .../{no}/file dalam iframe. File PDF / gambar sendiri tidak bisa membawa ikon,
     * jadi tanpa halaman ini tab browser memakai favicon root domain (XAMPP).
     */

    /** Halaman formulir belum ditandatangani (tombol Print admin). */
    public function unsignedPage($doc_no)
    {
        $header = $this->printablePermit($doc_no)['header'];
        $no = trim($header->complain_no);

        // Tenant mencetak dokumen bertanda tangan, bukan formulir. Permit yang Approved
        // sebelum ada fitur upload (tanpa dokumen) tetap memakai formulir.
        if (!$this->isAdmin() && $this->signedPath($header)) {
            return redirect($this->base('signed/' . rawurlencode($no)));
        }

        return $this->documentPage($no, $this->base('unsigned/' . rawurlencode($no) . '/file'), false);
    }

    /** Halaman dokumen bertanda tangan (tombol Print tenant, tombol lihat dokumen admin). */
    public function signedPage($doc_no)
    {
        [$header, $path] = $this->signedDocument($doc_no);
        $no = trim($header->complain_no);

        return $this->documentPage($no, $this->base('signed/' . rawurlencode($no) . '/file'), !preg_match('/\.pdf$/i', $path));
    }

    private function documentPage($no, $fileUrl, $isImage)
    {
        return view('permit.print_frame', [
            'title'    => $no,
            'pdf_url'  => $fileUrl,
            'is_image' => $isImage,
        ]);
    }

    /**
     * Permit yang boleh dicetak portal ini (abort kalau tidak):
     * admin -> semua status kecuali Cancel (formulir dicetak untuk ditandatangani);
     * tenant -> hanya Approved (Y), yaitu setelah admin mengunggah dokumen bertanda tangan.
     */
    private function printablePermit($doc_no)
    {
        $permit = $this->findPermit($doc_no);

        if (!$permit) {
            abort(404, __('shared/permit.not_found'));
        }

        $header = $permit['header'];
        $status = trim((string) $header->status);

        if (!self::canPrint($status, $this->isAdmin())) {
            abort(403, __('shared/permit.cannot_print', ['no' => trim($header->complain_no), 'status' => self::statusLabel($status)]));
        }

        return $permit;
    }

    /** Aturan cetak (dipakai server dan tombol di History). */
    public static function canPrint($status, $isAdmin)
    {
        return $isAdmin ? $status !== 'X' : $status === self::PRINTABLE_STATUS;
    }

    // ------------------------------------------------------------------
    // Dokumen bertanda tangan (upload admin -> Approved)
    // ------------------------------------------------------------------

    /** Folder dokumen satu permit di disk 'local' (per entity/project: nomor permit unik per project). */
    private function signedDir($header)
    {
        $safe = function ($v) {
            return preg_replace('/[^A-Za-z0-9_-]/', '_', trim((string) $v));
        };

        return self::SIGNED_DIR . '/' . $safe($header->entity_cd) . '/' . $safe($header->project_no);
    }

    /** Path dokumen bertanda tangan ({permit_no}.{ext}) kalau ada, selain itu null. */
    protected function signedPath($header)
    {
        $disk = Storage::disk('local');
        $base = $this->signedDir($header) . '/' . preg_replace('/[^A-Za-z0-9_-]/', '_', trim($header->complain_no));

        foreach (self::SIGNED_TYPES as $ext) {
            if ($disk->exists($base . '.' . $ext)) {
                return $base . '.' . $ext;
            }
        }

        return null;
    }

    /**
     * Admin: unggah dokumen bertanda tangan (PDF / JPG / PNG). File disimpan dengan nama
     * nomor permit ({permit_no}.{ext}, menggantikan file sebelumnya), lalu status -> Approved (Y).
     */
    public function uploadSigned(Request $request)
    {
        if (!$this->isAdmin()) {
            abort(403);
        }

        $permit = $this->findPermit($request->input('doc_no'));
        if (!$permit) {
            return $this->fail(__('shared/permit.not_found'), 404);
        }

        $header = $permit['header'];
        $status = trim((string) $header->status);
        $no = trim($header->complain_no);

        if (!in_array($status, self::UPLOADABLE_STATUSES, true)) {
            return $this->fail(__('shared/permit.cannot_upload', ['no' => $no, 'status' => self::statusLabel($status)]), 422);
        }

        $validator = Validator::make($request->all(), [
            'signed_file' => ['required', 'file', 'mimes:' . implode(',', self::SIGNED_TYPES), 'max:' . self::SIGNED_MAX_KB],
        ], [], ['signed_file' => __('shared/permit.attributes.signed_file')]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        $file = $request->file('signed_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $dir = $this->signedDir($header);
        $name = preg_replace('/[^A-Za-z0-9_-]/', '_', $no) . '.' . $ext;
        $disk = Storage::disk('local');
        $old = $this->signedPath($header);

        try {
            $disk->putFileAs($dir, $file, $name);

            $ctx = $this->contextOf($permit);
            DB::connection('dblive')->transaction(function () use ($ctx, $permit) {
                $keys = $permit['keys'];
                DB::connection('dblive')->table('mgr.sv_entry_letter')
                    ->where('entity_cd', $keys['entity_cd'])
                    ->where('project_no', $keys['project_no'])
                    ->where('complain_no', $keys['doc_no'])
                    ->update([
                        'status'     => self::PRINTABLE_STATUS,
                        'audit_user' => self::AUDIT_USER,
                        'audit_date' => $ctx['audit_date'],
                    ]);

                $this->writeLog($ctx, 'Signed document uploaded, approved by admin');
            });

            // file lama dengan ekstensi lain (mis. .pdf -> .jpg) dibuang
            if ($old && $old !== $dir . '/' . $name) {
                $disk->delete($old);
            }

            return response()->json([
                'status'    => 'OK',
                'pesan'     => __('shared/permit.uploaded', ['no' => $no]),
                'permit_no' => $no,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('uploadSigned', $e, __('shared/permit.upload_failed'));
        }
    }

    /**
     * [header, path] dokumen bertanda tangan yang boleh dibuka portal ini (abort kalau tidak):
     * tenant hanya untuk permit Approved, admin untuk semua permit dalam cakupan yang punya dokumen.
     */
    private function signedDocument($doc_no)
    {
        $permit = $this->findPermit($doc_no);
        if (!$permit) {
            abort(404, __('shared/permit.not_found'));
        }

        $header = $permit['header'];
        $status = trim((string) $header->status);
        $no = trim($header->complain_no);

        if (!$this->isAdmin() && $status !== self::PRINTABLE_STATUS) {
            abort(403, __('shared/permit.cannot_print', ['no' => $no, 'status' => self::statusLabel($status)]));
        }

        $path = $this->signedPath($header);
        if (!$path) {
            abort(404, __('shared/permit.signed_not_found', ['no' => $no]));
        }

        return [$header, $path];
    }

    /** File dokumen bertanda tangan (inline), dimuat oleh signedPage(). */
    public function signedFile($doc_no)
    {
        [, $path] = $this->signedDocument($doc_no);

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /** File PDF formulir (belum ditandatangani), dimuat oleh unsignedPage(). */
    public function unsignedFile($doc_no)
    {
        $permit = $this->printablePermit($doc_no);
        $header = $permit['header'];

        // Tenant yang permitnya sudah punya dokumen bertanda tangan -> dokumen itu, bukan formulir
        if (!$this->isAdmin() && $this->signedPath($header)) {
            return redirect($this->base('signed/' . rawurlencode(trim($header->complain_no))));
        }

        // Nama pengelola gedung & project untuk kop surat.
        $tenancy = DB::table('mgr.pm_tenancy')
            ->where('tenant_no', $header->debtor_acct)
            ->where('entity_cd', trim($header->entity_cd))
            ->where('project_no', trim($header->project_no))
            ->first();

        $tenant = DB::table('mgr.tenant')->where('tenant_no_df', $header->debtor_acct)->first();

        // Work Permit dicetak dengan format form "Surat Izin Kerja / Working Permit".
        if ($header->complain_type === 'W') {
            return PDF::loadView('permit.print_work', [
                'header'  => $header,
                'detail'  => $permit['detail'],
                'workers' => count($permit['lines']),
                'tools'   => $permit['tools'],
                'tenancy' => $tenancy,
                'tenant'  => $tenant,
                'logo'    => base_path('images/logo/IFCA.png'),
            ])
                ->setPaper('a4', 'portrait')
                ->stream($header->complain_no . '.pdf');
        }

        // Entry / Exit Permit of Goods: form "Surat Izin Keluar / Masuk Barang".
        return PDF::loadView('permit.print_goods', [
            'header'  => $header,
            'detail'  => $permit['detail'],
            'items'   => $permit['lines'],
            'tenancy' => $tenancy,
            'tenant'  => $tenant,
            'type'    => $header->complain_type,
            'logo'    => base_path('images/logo/IFCA.png'),
        ])
            ->setPaper('a4', 'portrait')
            ->stream($header->complain_no . '.pdf');
    }
}
