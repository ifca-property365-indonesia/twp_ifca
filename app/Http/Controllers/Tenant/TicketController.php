<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class TicketController extends Controller
{
    public function index($id=null, $form=null)
    {
        $buss_id = Session::get('business_no');
        $tenant_no = Session::get('tenant_df');
        $project_no = Session::get('project_no');
        $crit = array(
            'business_no' => $buss_id,
            'tenant_no'   => $tenant_no
        );

        $data_tenancy = DB::table('mgr.pm_tenancy')->where($crit)->get();
        $combo_tenant='';
        if($data_tenancy){
            $combo_tenant = $this->get_combo($buss_id, $data_tenancy[0]->id, $project_no);
        }

        $crit_spec = array(
            'entity_cd' => $data_tenancy[0]->entity_cd,
            'project_no' => $data_tenancy[0]->project_no
        );
        $dataspec = DB::connection('dblive')
            ->table('mgr.sv_spec')
            ->where($crit_spec)
            ->get();

        $complain_no = DB::connection('dblive')
            ->table('mgr.sv_spec')
            ->where($crit_spec)
            ->max('complain_seq_no');

        $complain_no = ($complain_no ?? 0) + 1;

        if(empty($id) || empty($form))
        {
            $id   = '0';
            $jdl  = __('tenant/ticket.new_ticket');
            $form = 'add';
        } else {
            $id   = $id;
            $jdl  = __('tenant/ticket.edit_ticket');
            $form = 'edit';
        }

        $content = array(
            'id' => $id,
            'jdl'=> $jdl,
            'form'=> $form,
            'combo_tenant' => $combo_tenant,
            'number' => $complain_no,
        );
        return view('tenant.ticket.index', $content);
    }

    public function getByID($id = '')
    {
        $where = array('id' => $id);
        $data = DB::table('mgr.sv_entry_multi')->where($where)->get();
        echo json_encode($data);
    }

    public function getTicket($ent = '', $prj = '')
    {
        $where = array(
            'entity_cd' => $ent,
            'project_no' => $prj
        );
        $data = DB::connection('dblive')
                    ->table('mgr.sv_spec')
                    ->where($where)
                    ->get();
        echo json_encode($data);
    }

    public function getTicketNew(Request $request)
    {
        $where = [
            'entity_cd'  => $request->ent,
            'project_no' => $request->prj,
        ];

        $data = DB::connection('dblive')
            ->table('mgr.sv_spec')
            ->where($where)
            ->first();

        if ($data) {
            return response()->json($data->complain_seq_no);
        }

        return response()->json(1);
    }

    public function getTicketPrefix($ent="")
    {
        $crit_cat = array(
            'entity_cd' => $ent,
            'year'  => date("Y")
        );
        $data_cat = DB::connection('dblive')
            ->table('mgr.cf_document_ctl_dtl')
            ->where($crit_cat)
            ->get();
        $next_doc_no = $data_cat[0]->next_doc_no;
        echo ($next_doc_no);
    }

    /**
     * Combo tenancy (tenant_no) di form ticket. Tenant biasa: tenancy milik business-nya;
     * mode semua tenant (admin): seluruh tenancy aktif.
     */
    function get_combo($business_no = "", $selected_id = "", $project_no = "")
    {
        if (TenantScope::all()) {
            $query = DB::table('mgr.pm_tenancy')->where('status', 'A')->orderBy('tenant_no')->get();
        } else {
            $where = array('business_no'=> $business_no,
                    'project_no'=>$project_no);
            $query = DB::table('mgr.pm_tenancy')->where($where)->get();
        }
        $combo[] = '<option></option>';
        $combo[] = "\n";
        foreach ($query as $result) {
            $value = TenantScope::all() ? $result->tenant_no.' - '.$result->entity_desc : $result->tenant_no;
            $combo[] = '<option value="' . $result->id . '" data-entity="'.$result->entity_cd.'" data-project="'.$result->project_no.'" '. '>' . $value . '</option>';
            $combo[] = "\n";
        }
        return implode("", $combo);
    }

    public function getCat(Request $request)
    {
        if($_POST)
        {
            $ticket_type = $request->ticket_type;
            if (is_null($ticket_type) || !isset($ticket_type))
            {
                echo('<option></option>');
            } else {
                $crit_cat = array('complain_type' => $ticket_type);
                $data_cat = DB::connection('dblive')
                    ->table('mgr.sv_category')
                    ->whereIn('category_cd', ['ENG', 'HS', 'OT'])
                    ->get();

                if(!empty($data_cat)) {
                    $list = '<option></option>';
                    foreach ($data_cat as $key) {                    
                        $list .= '<option value="' . $key->category_cd . '" data-ticketname="' . $key->descs . '"' . '>' . $key->descs . '</option>';
                    }
                    echo($list);
                }
            }
        }
    }

    public function getCatEdit($complain_type, $category_cd)
    {
        $crit_cat = array('complain_type' => $complain_type);
        $data_cat = DB::connection('dblive')
            ->table('mgr.sv_category')
            ->whereIn('category_cd', ['ENG', 'HS', 'OT'])
            ->get();

        if(!empty($data_cat)) {
            $list = '<option></option>';
            foreach ($data_cat as $key) {
                if ($category_cd == $key->category_cd) {
                    $pilih = ' selected = "1"';
                } else {
                    $pilih = '';
                }                  
                $list .= '<option ' . $pilih . ' value="' . $key->category_cd . '" data-ticketname="' . $key->descs . '"' . '>' . $key->descs . '</option>';
            }
            echo json_encode($list);
        }
    }

    /**
     * Daftar <option> unit milik satu tenancy (dipakai form ticket dan form permit).
     * Tenancy harus masuk cakupan TenantScope; kalau tidak, atau id tidak ada, hanya option kosong.
     */
    public function getLotNo(Request $request)
    {
        $tenancy = DB::table('mgr.pm_tenancy')->where('id', (int) $request->id_tenancy)->first();

        if (!$tenancy || !in_array((string) $tenancy->tenant_no, TenantScope::tenantNos(), true)) {
            return response('<option></option>');
        }

        $tenant_lot = $this->lotsOfTenancy($tenancy->entity_cd, $tenancy->project_no, $tenancy->tenant_no);

        if ($tenant_lot->isEmpty()) {
            return response('<option value="">' . e(__('tenant/ticket.no_lot')) . '</option>');
        }

        $list_lot = '<option></option>';
        foreach ($tenant_lot as $datalot) {
            $list_lot .= '<option data-level="' . e($datalot->level_no) . '" value="' . e($datalot->lot_no) . '">' . e($datalot->lot_no) . '</option>';
        }

        return response($list_lot);
    }

    /** Unit (lot) milik satu tenancy dari SQL Server (pm_lot join pm_tenant_lot). */
    private function lotsOfTenancy($entity, $project, $tenant_no)
    {
        return DB::connection('dblive')
            ->table('mgr.pm_lot AS l')
            ->join('mgr.pm_tenant_lot AS tl', function ($join) {
                $join->on('l.entity_cd', '=', 'tl.entity_cd')
                    ->on('l.project_no', '=', 'tl.project_no')
                    ->on('l.lot_no', '=', 'tl.lot_no');
            })
            ->where([
                ['l.entity_cd', '=', $entity],
                ['l.project_no', '=', $project],
                ['tl.tenant_no', '=', $tenant_no],
            ])
            ->select(
                'l.lot_no',
                'l.descs',
                'l.level_no',
                'tl.tenant_no'
            )
            ->orderBy('l.lot_no', 'ASC')
            ->get();
    }

    public function getLotnoEdit($tenant_no, $lot_no)
    {
        $data_tenancy = DB::table('mgr.pm_tenancy')
            ->where('id', $tenant_no)
            ->get();

        $entity = $data_tenancy[0]->entity_cd;
        $project = $data_tenancy[0]->project_no;
        $tenancy = $data_tenancy[0]->tenant_no;

        $tenant_lot = $this->lotsOfTenancy($entity, $project, $tenancy);

        if(!empty($tenant_lot)) {
            $list_lot = '<option></option>';
            foreach ($tenant_lot as $datalot) {
                
                if ($lot_no == $datalot->lot_no) {
                    $pilih = ' selected = "1"';
                } else {
                    $pilih = '';
                }
                $list_lot.='<option data-level="'.$datalot->level_no.'" ' . $pilih . ' value="'.$datalot->lot_no.'" >'.$datalot->descs.'</option>';
            }
            echo json_encode($list_lot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        }
    }

    public function savepic(Request $request)
    {
        $files = $_FILES;
        $picture = !empty($_FILES) ? $picture = $_FILES["ticket_image"] : '';
        if (!empty($picture["name"])) {
            $picname = str_replace(' ', '_', $picture["name"]);
            $picture = $_FILES["ticket_image"];
            $tmpName = $_FILES['ticket_image']['tmp_name'];
            $imgString = file_get_contents($tmpName);
            $imgData = bin2hex($imgString);
            $imgbin = "0x" . $imgData;
            $psn = '';
            $msg = '';
            $picture = array_filter($picture);

            $target_dir = "./storage/file_ticket/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir);
            }
            $target_file = $target_dir . str_replace(' ', '_', basename($_FILES["ticket_image"]["name"]));
            $uploadOk = 1;
            $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

            if ($_FILES["ticket_image"]["size"] > 2000000) {
                $msg = __('common.upload_max_size', ['size' => '2MB']);
                $uploadOk = 0;
                $psn = 'failed';
                $res = array("pesan" => $msg, "status" => $psn);

                echo json_encode($res);
                exit();
            }

            $imageFileType = strtolower($imageFileType);
            // Allow certain file formats
            if (
                $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif" && $imageFileType != "JPG"
            ) {
                $msg = __('common.upload_only_image');
                $uploadOk = 0;
                $psn = 'failed';
                $res = array("pesan" => $msg, "status" => $psn);

                echo json_encode($res);
                exit();
            }
            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                $msg = __('common.upload_not_saved');
                $psn = "Failed";
                // if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["ticket_image"]["tmp_name"], $target_file)) {
                    $msg = __('common.upload_done', ['name' => basename($_FILES["ticket_image"]["name"])]);
                    $psn = "OK";
                    $descs = "/storage/file_ticket/" . $picname;
                    $url = url('/tenant') . $descs;
                } else {
                    $msg = __('common.upload_error');
                    $psn = "Failed";
                }
            }
        } else {
            $msg = __('common.upload_error');
            $psn = "Failed";
        }
        $res = array(
            'pesan' => $msg,
            'status' => $psn,
            'url' => $url,
            'picname' => $picname,
            'pic_attached' => $imgbin
        );
        echo json_encode($res);
    }

    public function update(Request $request) 
    {
        try {
            
            $msg = "";
            $id = $request->id;
            $number = $request->angka;
            $pre = $request->pre;
            $ticket_type = $request->ticket_type;
            $tenant_no = $request->tenant_no;
            $lot_no = $request->lot_no;
            $floor = $request->floor;
            $location = $request->location;
            $req_by = $request->req_by;
            $contact_no = $request->contact_no;
            $category = $request->category;
            $description = $request->description;
            $picture = $request->picturepath;
            $pic_attached = $request->pictureattach;
            $entity = $request->entity;
            $project = $request->project;
            $webuser = 'TWP';

            $crit_spec = ['category_cd' => $category];
            $dataspec = DB::connection('dblive')
                ->table('mgr.sv_category')
                ->where($crit_spec)
                ->whereIn('category_cd', ['ENG', 'HS', 'OT'])
                ->get();
                
            $assign_to = $dataspec[0]->descs ?? null;

            // hanya tenancy dalam cakupan tenant yang login; edit hanya ticket milik tenancy itu
            $scopeIds = TenantScope::tenancies()->pluck('id')->map(fn ($v) => (int) $v)->all();
            $ownerTenancy = $id > 0 ? DB::table('mgr.sv_entry_multi')->where('id', (int) $id)->value('id_tenancy') : $tenant_no;
            if (!in_array((int) $tenant_no, $scopeIds, true) || !in_array((int) $ownerTenancy, $scopeIds, true)) {
                throw new \Exception(__('tenant/ticket.tenant_not_found', ['tenant' => $tenant_no]));
            }

            $data_tenant = DB::table('mgr.pm_tenancy')->where('id', $tenant_no)->get();

            if ($data_tenant->isEmpty()) {
                throw new \Exception(__('tenant/ticket.tenant_not_found', ['tenant' => $tenant_no]));
            }
            
            $dataopen = DB::connection('dblive')
                ->table('mgr.cf_document_ctl')
                ->where(['entity_cd' => $entity])
                ->get();
            if ($dataopen->isEmpty()) {
                throw new \Exception(__('tenant/ticket.doc_control_not_found', ['entity' => $entity, 'prefix' => $pre]));
            }
            

            $next_doc_noSave = $dataopen[0]->next_doc_no;
            $Type_format1 = $dataopen[0]->type_format;
            
            $dataopen2 = DB::connection('dblive')
                ->table('mgr.cf_document_format')
                ->where(['rowId' => $next_doc_noSave, 'type_format' => $Type_format1])
                ->get();
            
            if ($dataopen2->isEmpty()) {
                throw new \Exception(__('tenant/ticket.doc_format_not_found', ['row' => $next_doc_noSave, 'format' => $Type_format1]));
            }

            $typeformat2 = $dataopen2[0]->format;
            $year = date('y');
            $month = date('m');

            $typeformat2 = $number;

            // --- log nomor tiket ---
            \Log::info('Generated complain_no', ['complain_no' => $typeformat2]);

            // (lanjutkan semua kode insert/update kamu di sini tanpa ubahan)
            // ...
            if (is_array($description)) {
                $description = $description[0];
            }

            $critedit = [
                'id' => $id,
                'complain_no' => $typeformat2
            ];

            $data = array(
                'complain_no'     => $typeformat2,
                'id_tenant'       => $tenant_no,
                'reported_by'     => $webuser,
                'reported_date'   => date('Y-m-d'),
                'serv_req_by'     => $req_by,
                'location'        => $location,
                'floor'           => $floor,
                'contact_no'      => $contact_no,
                'billing_type'    => 'T',
                'complain_type'   => $ticket_type,
                'complain_source' => 'TWP',
                'category_cd'     => $category,
                'lot_no'          => $lot_no,
                'status'          => 'R',
                'work_requested' => $description,
                'id_tenancy'      => $tenant_no,
                'tenant_no'       => $data_tenant[0]->tenant_no,
                'picture'         => $picture,
                'entity_cd'       => $entity,
                'project_no'      => $project
            );
            if ($id > 0) {
                // ===== start insert ke HD (edit tidak mengembalikan status O ke R, demo_twp_adm) =====
                $oldStatus = DB::table('mgr.sv_entry_multi')->where($critedit)->value('status');
                if (trim((string) $oldStatus) === 'O') {
                    $data['status'] = 'O';
                }
                // ===== end insert ke HD =====
                $updated = DB::table('mgr.sv_entry_multi')->where($critedit)->update($data);
            } else {
                $updated = DB::table('mgr.sv_entry_multi')->insert($data);
            }
            
            $critedit2 = [
                'entity_cd' => $entity,
                'project_no'  => $project,
                'complain_no' => $typeformat2
            ];

            $dataServ1 = array(
                'entity_cd'       => $entity,
                'project_no'      => $project,
                'debtor_acct'     => $data_tenant[0]->tenant_no,
                'complain_no'     => $typeformat2,
                'reported_by'     => $webuser,
                'reported_date'   => date('d M Y H:i:s'),
                'serv_req_by'     => $req_by,
                'work_requested'  => $description,
                'location'        => $location,
                'floor'           => $floor,
                'contact_no'      => $contact_no,
                'billing_type'    => 'T',
                'status'          => 'R',
                'complain_source' => 'TWP',
                'lot_no'          => $lot_no,
                'complain_type'   => $ticket_type,
                'category_cd'     => $category,
                'post_status'     => 'N',
                'audit_user'      => 'MGR',
                'audit_date'      => date('d M Y H:i:s'),
            );

            $checkdataServ1 = DB::connection('dblive')
                ->table('mgr.sv_entry_multi')
                ->where($critedit2)
                ->get();

            $hdReportNo = null;   // report_no WO yang dibuat di blok "insert ke HD" (kalau berhasil)

            if (count($checkdataServ1) == 0) {
                DB::connection('dblive')
                ->table('mgr.sv_entry_multi')
                ->insert($dataServ1);

                // ===== start insert ke HD =====
                // Ticket baru juga dibuat di mgr.sv_entry_hd dengan report_no WOyymmnnnn
                // (nnnn = urutan dalam bulan berjalan, mulai 0001 setiap bulan baru).
                // complain_no disimpan di note1 sebagai penghubung ke sv_entry_multi.
                // Setelah berhasil, status sv_entry_multi (demo_twp & demo_twp_adm) -> O.
                // Kalau gagal, ticket tetap tersimpan (status tetap R) dan error dicatat di log.
                try {
                    $hdReportNo = DB::connection('dblive')->transaction(function () use ($entity, $project, $data_tenant, $webuser, $req_by, $description, $location, $floor, $contact_no, $lot_no, $ticket_type, $category, $typeformat2, $critedit2) {
                        $db = DB::connection('dblive');

                        // report_no terakhir di bulan berjalan (WOyymm....); belum ada -> mulai 0001.
                        // Baris dikunci sampai commit supaya dua submit bersamaan tidak
                        // mendapat nomor yang sama.
                        $prefix = 'WO' . date('ym');
                        $lastReportNo = $db->table('mgr.sv_entry_hd')
                            ->where('entity_cd', $entity)
                            ->where('project_no', $project)
                            ->where('report_no', 'like', $prefix . '%')
                            ->orderBy('report_no', 'desc')
                            ->lockForUpdate()
                            ->value('report_no');

                        $lastSeq = $lastReportNo ? (int) substr(trim($lastReportNo), -4) : 0;
                        if ($lastSeq >= 9999) {
                            throw new \RuntimeException('report_no ' . $prefix . ' sudah mencapai 9999');
                        }
                        $reportNo = $prefix . str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);

                        $now = date('d M Y H:i:s');
                        $db->table('mgr.sv_entry_hd')->insert([
                            'entity_cd'       => $entity,
                            'project_no'      => $project,
                            'debtor_acct'     => $data_tenant[0]->tenant_no,
                            'report_no'       => $reportNo,
                            'reported_by'     => $webuser,
                            'reported_date'   => $now,
                            'after_hr'        => ' ',
                            'work_requested'  => mb_substr((string) $description, 0, 255),
                            'location'        => $location,
                            'floor'           => $floor,
                            'serv_req_by'     => $req_by,
                            'contact_no'      => $contact_no,
                            'billing_type'    => 'T',
                            'status'          => 'O',
                            'audit_user'      => 'MGR',
                            'audit_date'      => $now,
                            'complain_source' => 'TWP',
                            'lot_no'          => $lot_no,
                            'request_type'    => $ticket_type,
                            'category_cd'     => $category,
                            'note1'           => $typeformat2,   // complain_no sv_entry_multi
                        ]);

                        $db->table('mgr.sv_entry_multi')
                            ->where($critedit2)
                            ->update(['status' => 'O']);

                        \Log::info('Ticket masuk sv_entry_hd', ['complain_no' => $typeformat2, 'report_no' => $reportNo]);

                        return $reportNo;
                    });

                    // salinan ticket di demo_twp_adm (mgr.sv_entry_multi) ikut -> O;
                    // dijalankan setelah transaksi SQL Server commit (koneksi berbeda)
                    DB::table('mgr.sv_entry_multi')
                        ->where($critedit2)
                        ->update(['status' => 'O']);
                } catch (\Throwable $e) {
                    \Log::error('Insert sv_entry_hd gagal: ' . $e->getMessage(), ['complain_no' => $typeformat2]);
                }
                // ===== end insert ke HD =====
            } else {
                // ===== start insert ke HD (edit tidak mengembalikan status O ke R, SQL Server) =====
                if (trim((string) $checkdataServ1[0]->status) === 'O') {
                    $dataServ1['status'] = 'O';
                }
                // ===== end insert ke HD =====
                DB::connection('dblive')
                ->table('mgr.sv_entry_multi')
                ->where($critedit2)
                ->update($dataServ1);
            }
            
            $dataServ2 = array(
                'entity_cd'      => $entity,
                'project_no'     => $project,
                'debtor_acct'    => $data_tenant[0]->tenant_no,
                'complain_no'    => $typeformat2,
                'seq_no'         => 1,
                'reported_by'    => $webuser,
                'reported_date'  => date('d M Y H:i:s'),
                'work_requested' => $description,
                'serv_req_by'    => $req_by,
                'location'       => $location,
                'floor'          => $floor,
                'contact_no'     => $contact_no,
                'billing_type'   => 'T',
                'status'         => 'A',
                'complain_source'=> 'TWP',
                'assign_to'      => $assign_to,
                'audit_user'     => 'MGR',
                'audit_date'     => date('d M Y H:i:s')
            );

            // ===== start insert ke HD (dt ikut menyimpan report_no, seperti sistem desktop) =====
            if ($hdReportNo) {
                $dataServ2['report_no'] = $hdReportNo;
            }
            // ===== end insert ke HD =====

            $checkdataServ2 = DB::connection('dblive')
                ->table('mgr.sv_entry_multi_dt')
                ->where($critedit2)
                ->get();
            
            if (count($checkdataServ2) == 0) {
                $query = DB::connection('dblive')
                ->table('mgr.sv_entry_multi_dt')
                ->insert($dataServ2);
                if ($query != "OK") {
                    $msg = $query;
                    $st = 'Fail';
                } else {
                    $msg = __('common.saved');
                    $st = 'OK';
                }
            } else {
                $query = DB::connection('dblive')
                ->table('mgr.sv_entry_multi_dt')
                ->where($critedit2)
                ->update($dataServ2);
                if ($query != "1") {
                    $msg = $query;
                    $st = 'Fail';
                } else {
                    $msg = __('common.updated');
                    $st = 'OK';
                }
            }

            if (!$id) {
                
                $autonum = $number + 1;
                
                $dataCompl = array('complain_seq_no' => $autonum);
                $crit = array(
                    'entity_cd'=>$entity,
                    'project_no'=>$project,
                );
                

                DB::connection('dblive')
                    ->table('mgr.sv_spec')
                    ->where($crit)
                    ->update($dataCompl);

                $dataCompl2 = array('next_doc_no' => $autonum);
                
                $crit2 = array(
                    'entity_cd'=>$entity,
                    'prefix'=>$pre,
                    'year'  => date("Y")
                );

                DB::connection('dblive')
                    ->table('mgr.cf_document_ctl_dtl')
                    ->where($crit2)
                    ->update($dataCompl2);

            }

            $callback = array(
                "pesan" => $msg,
                "status" => $st
            );

            return response()->json([
                "pesan" => $msg ?: __('common.saved'),
                "status" => $st ?? 'OK'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error in save(): ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Fail',
                'pesan' => __('common.error_occurred', ['message' => $e->getMessage()]),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function getHargaItem()
    {
        $data = DB::connection('dblive')
            ->table('mgr.sv_charge')
            ->get();

        $html = '
        <table class="table table-bordered table-striped" id="tblHargaItem">
            <thead>
                <tr>
                    <th>'.e(__('tenant/ticket.col_no')).'</th>
                    <th>'.e(__('tenant/ticket.col_code')).'</th>
                    <th>'.e(__('common.description')).'</th>
                    <th>'.e(__('tenant/ticket.col_price')).'</th>
                </tr>
            </thead>
            <tbody>
        ';

        $no = 1;

        foreach($data as $row){

            $html .= '
            <tr>
                <td>'.$no++.'</td>
                <td>'.$row->item_cd.'</td>
                <td>'.$row->descs.'</td>
                <td>'.number_format($row->charge_amt,2).'</td>
            </tr>
            ';
        }

        $html .= '
            </tbody>
        </table>
        ';

        return $html;
    }

    public function getHargaJasa()
    {
        $data = DB::connection('dblive')
            ->table('mgr.sv_master')
            ->get();

        $html = '
        <table class="table table-bordered table-striped" id="tblHargaJasa">
            <thead>
                <tr>
                    <th>'.e(__('tenant/ticket.col_no')).'</th>
                    <th>'.e(__('tenant/ticket.col_code')).'</th>
                    <th>'.e(__('common.description')).'</th>
                    <th>'.e(__('tenant/ticket.col_price')).'</th>
                </tr>
            </thead>
            <tbody>
        ';

        $no = 1;

        foreach($data as $row){

            $html .= '
            <tr>
                <td>'.$no++.'</td>
                <td>'.$row->service_cd.'</td>
                <td>'.$row->descs.'</td>
                <td>'.number_format($row->labour_rate,2).'</td>
            </tr>
            ';
        }

        $html .= '
            </tbody>
        </table>
        ';

        return $html;
    }
}
