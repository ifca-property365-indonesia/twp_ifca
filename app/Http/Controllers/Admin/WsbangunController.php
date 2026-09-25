<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DefaultPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WsbangunController extends Controller
{
    public function update_ticket(Request $request){
        $par = $request->params;
        $par = explode(':',$par);
        $entity = $par[0]; 
        $project = $par[1]; 
        $debtor = $par[2]; 
        $complain_no = $par[3]; 
        $status = $par[4]; 
        $data = array(
            'status'=>$status
        );
        $crit1 = array(
            'entity_cd'=>$entity,
            'project_no'=>$project,
            'tenant_no'=>$debtor,
            'complain_no'=>$complain_no
        );
        try{
            DB::connection('ifcaadm')
                ->table('mgr.sv_entry_multi')
                ->where($crit1)
                ->update($data);
            $feedback = "Data has been updated successfully";
        } catch(\Illuminate\Database\QueryException $ex){ 
         
            $feedback = "Insert failed: " . $ex->getMessage();
        }

        echo $feedback;
        
    }

    /**
     * POST api/business/{method}/{value}, value dipisah ":".
     *   insert/entity:project:tenant_no
     *   edit/entity:project:tenant_no      (sama dengan insert, beda format balasan)
     *   delete/entity:project:tenant_no
     * insert & edit sama-sama "upsert": data yang belum ada dibuat, yang sudah ada diperbarui
     * (lihat syncBusiness). Format balasan tetap seperti dulu per method.
     */
    public function business($method='', $value='')
    {
        if(!empty($method)&&!empty($value))
        {
            $method = strtolower($method);
            $list_value = explode(":",$value);
            if (count($list_value) < 3) {
                echo 'Bad request';
                return;
            }
            switch ($method) {
                case 'insert':
                    $this->new_business(array(
                        'entity_cd'=>$list_value[0],
                        'project_no'=>$list_value[1],
                        'tenant_no'=>$list_value[2]
                    ));
                    break;
                case 'delete':
                    $content = array(
                        'entity_cd'=>$list_value[0],
                        'project_no'=>$list_value[1],
                        'tenant_no'=>$list_value[2]
                    );
                    $this->del_business($content);
                    break;
                case 'edit':
                    $this->edit_business(array(
                        'entity_cd'=>$list_value[0],
                        'project_no'=>$list_value[1],
                        'tenant_no'=>$list_value[2]
                    ));
                    break;
            }
        }
    }

    /** business/insert/entity:project:tenant_no. Balasan: JSON data debtor itu, seperti dulu. */
    public function new_business($value = "")
    {
        try {
            list($rows, $all) = $this->businessRows($value['entity_cd'], $value['project_no'], $value['tenant_no']);
            if (empty($rows)) {
                echo 'Bad request';
                return;
            }
            $this->syncBusiness($all);
            echo json_encode($rows);
        } catch (\Illuminate\Database\QueryException $ex) {
            echo "Insert failed: " . $ex->getMessage();
        }
    }

    /** business/edit/entity:project:tenant_no (sama dengan insert). Balasan: 'Tenant id: x data inserted|updated!' */
    public function edit_business($value="")
    {
        try {
            list($rows, $all) = $this->businessRows($value['entity_cd'], $value['project_no'], $value['tenant_no']);
            if (empty($rows)) {
                echo 'Bad request';
                return;
            }
            $result = $this->syncBusiness($all);
            echo 'Tenant id: ' . $result['id'] . ' data ' . ($result['created'] ? 'inserted' : 'updated') . '!';
        } catch (\Illuminate\Database\QueryException $ex) {
            echo "Update failed: " . $ex->getMessage();
        }
    }

    /**
     * Baris mgr.v_tenant_login untuk debtor ini, plus semua debtor lain dengan business_id yang
     * sama (satu business bisa punya beberapa debtor, mis. 00046 kontrak habis & 00046-2 masih
     * aktif). syncBusiness memilih kontrak terbaru dari $all.
     * Kalau tidak ditemukan sebagai debtor, dicoba sebagai business_id (format edit lama).
     *
     * @return array [$rows (debtor yang diminta), $all (semua debtor business itu)]
     */
    private function businessRows($entity, $project, $tenantNo)
    {
        $rows = $this->tenantLoginRows($entity, $project, 'debtor_acct', $tenantNo);
        if (empty($rows)) {
            $rows = $this->tenantLoginRows($entity, $project, 'business_id', $tenantNo);
        }
        if (empty($rows)) {
            return array(array(), array());
        }
        $all = $this->tenantLoginRows($entity, $project, 'business_id', $rows[0]->business_id);
        return array($rows, $all ?: $rows);
    }

    /**
     * Data tenant dari IFCA (mgr.v_tenant_login) per entity + project, dicari lewat
     * debtor_acct atau business_id. Satu business bisa punya beberapa debtor.
     * (mgr.tenant_login yang dulu dipakai edit sudah tidak ada di database.)
     */
    private function tenantLoginRows($entity, $project, $column, $key)
    {
        return DB::connection('ifcapb')->select(
            "SELECT * FROM mgr.v_tenant_login WHERE entity_cd = ? AND RTRIM(project_no) = ? AND {$column} = ? ORDER BY debtor_acct",
            [$entity, trim($project), $key]
        );
    }

    /**
     * Upsert akun tenant dari baris mgr.v_tenant_login (semua business_id yang sama):
     * - tenant (per business_no + flag): hanya email_addr -> satu baris F; ada email_addr_fin ->
     *   F (email_addr_fin) + O (email_addr). Baris yang belum ada dibuat, yang sudah ada diperbarui
     *   (nama, telepon, alamat, email). contact_name diisi dari contact_person hanya saat baris
     *   dibuat; contact_mobile selalu kosong saat dibuat. Keduanya tidak pernah ditimpa (diisi
     *   user lewat View Profile TWP). Baris lain tidak dihapus.
     *   email_addr_fin kosong/NULL/tidak ada = tidak ada.
     * - all_login tiap baris tenant: belum ada -> dibuat dengan password default; sudah ada ->
     *   nama & email diperbarui (password tidak diubah).
     * - pm_tenancy: satu baris per business berisi debtor utama (kontrak terbaru); sudah ada ->
     *   diperbarui (status tidak diubah); belum ada -> dibuat (status 'A', id = id tenant F).
     *   tenant.tenant_no_df ikut debtor utama.
     *
     * @return array ['id' => id tenant untuk pm_tenancy, 'created' => true kalau tenant baru dibuat]
     */
    private function syncBusiness(array $rows)
    {
        // Debtor utama = kontrak terbaru (expiry_date terbesar). Business bisa punya debtor lama
        // yang kontraknya sudah habis (mis. 00046 s/d 2025, 00046-2 s/d 2029); login hanya
        // menampilkan tenancy yang expiry_date >= hari ini, jadi yang lama tidak boleh terpilih.
        usort($rows, function ($x, $y) {
            return strcmp((string) $y->expiry_date, (string) $x->expiry_date) ?: strcmp($y->debtor_acct, $x->debtor_acct);
        });
        $src = $rows[0];
        $businessNo = $src->business_id;
        $db = DB::connection('ifcaadm');

        // teks kosong / spasi saja (mis. tel_no ' ') disimpan NULL
        $text = function ($v) {
            $v = trim((string) $v);
            return $v === '' ? null : $v;
        };
        $info = array(
            // tenant_no_df harus sama dengan pm_tenancy.tenant_no (dipakai join saat login)
            'tenant_no_df'   => $src->debtor_acct,
            'name'           => $text($src->name),
            'phone'          => $text($src->tel_no),
            'address'        => $text($src->address1 . ' ' . $src->address2 . ' ' . $src->address3 . ' ' . $src->post_cd),
        );
        $email = trim((string) $src->email_addr);
        // kolom email_addr_fin belum ada di view: otomatis terpakai begitu kolomnya ditambahkan
        $emailFin = property_exists($src, 'email_addr_fin') ? trim((string) $src->email_addr_fin) : '';

        // Flag tenant: F = Finance (semua menu), O = Operational (tanpa menu Invoice).
        // Hanya email_addr -> satu baris F. Ada email_addr_fin -> F (email_addr_fin) + O (email_addr).
        // F dibuat lebih dulu supaya id-nya yang dipakai pm_tenancy (sama seperti data yang sudah ada).
        $want = $emailFin !== '' ? array('F' => $emailFin, 'O' => $email) : array('F' => $email);

        return $db->transaction(function () use ($db, $rows, $src, $businessNo, $info, $email, $emailFin, $want, $text) {
            // --- tenant (per business_no + flag) ---
            $existing = array();
            foreach ($db->table('mgr.tenant')->where('business_no', $businessNo)->orderBy('id')->get() as $t) {
                $existing[$t->flag ?: 'O'] = $existing[$t->flag ?: 'O'] ?? $t;
            }
            $created = empty($existing);

            // email_addr_fin baru terisi untuk business yang selama ini hanya punya F (email_addr):
            // baris itu jadi O (akun login email_addr tetap), lalu F baru untuk email finance
            if ($emailFin !== '' && !isset($existing['O']) && isset($existing['F'])
                && strcasecmp(trim((string) $existing['F']->email), $email) === 0) {
                $db->table('mgr.tenant')->where('id', $existing['F']->id)->update(array('flag' => 'O'));
                $existing['O'] = $existing['F'];
                unset($existing['F']);
            }

            foreach ($want as $flag => $mail) {
                if (isset($existing[$flag])) {
                    $update = $info;
                    // email yang sudah dipakai baris lain business ini (mis. email_addr_fin dikosongkan
                    // lagi: F jangan ikut memakai email O) tidak dipasang -> email F tetap yang lama
                    $taken = false;
                    foreach ($existing as $f => $t) {
                        $taken = $taken || ($f !== $flag && strcasecmp(trim((string) $t->email), $mail) === 0);
                    }
                    if (!$taken) {
                        $update['email'] = $mail;
                    }
                    $db->table('mgr.tenant')->where('id', $existing[$flag]->id)->update($update);
                } else {
                    $db->table('mgr.tenant')->insert($info + array(
                        // nama kontak hanya diisi saat baris baru dibuat; sesudahnya diubah user
                        // lewat View Profile TWP, jadi sinkron berikutnya tidak menimpanya
                        'contact_name'   => $text($src->contact_person),
                        // no. HP kontak diisi sendiri oleh user lewat TWP (tidak diambil dari hand_phone)
                        'contact_mobile' => null,
                        'email'        => $mail,
                        'status'       => 1,
                        'business_no'  => $businessNo,
                        'flag'         => $flag,
                    ));
                }
            }

            $tenants = $db->table('mgr.tenant')->where('business_no', $businessNo)->orderBy('id')->get();
            // id baris pm_tenancy baru: baris F (kalau tidak ada, baris pertama)
            $firstId = $tenants->firstWhere('flag', 'F')->id ?? $tenants[0]->id;

            // --- all_login ---
            $password = null;
            foreach ($tenants as $tenant) {
                $crit = array('tableforeign' => 'tenant', 'idforeign' => $tenant->id);
                if ($db->table('mgr.all_login')->where($crit)->exists()) {
                    $db->table('mgr.all_login')->where($crit)->update(array('name' => $tenant->name, 'email' => $tenant->email));
                } else {
                    // password awal akun tenant baru: tabel defaultpassword (menu Default Password)
                    $password = $password ?: DefaultPassword::hash();
                    $db->table('mgr.all_login')->insert($crit + array(
                        'name'     => $tenant->name,
                        'email'    => $tenant->email,
                        'password' => $password,
                    ));
                }
            }

            // --- pm_tenancy: satu baris per business (PRIMARY KEY id = id tenant F) ---
            // selalu berisi debtor utama (kontrak terbaru); sudah ada -> diperbarui termasuk
            // tenant_no (kontrak diperpanjang dengan debtor baru), status tidak diubah
            $tenancy = array(
                'tenant_no'     => $src->debtor_acct,
                'business_no'   => $businessNo,
                'entity_cd'     => trim($src->entity_cd),
                'project_no'    => trim($src->project_no),
                'contract_date' => $this->ymd($src->contract_date),
                'commence_date' => $this->ymd($src->commence_date),
                'expiry_date'   => $this->ymd($src->expiry_date),
                'entity_desc'   => $src->entity_desc,
                'project_desc'  => $src->project_desc,
            );
            $current = $db->table('mgr.pm_tenancy')->where('business_no', $businessNo)->orderBy('id')->first()
                ?: $db->table('mgr.pm_tenancy')->where('id', $firstId)->first();
            if ($current) {
                $db->table('mgr.pm_tenancy')->where('id', $current->id)->update($tenancy);
            } else {
                // pm_tenancy.id kolom IDENTITY di SQL Server: id eksplisit butuh IDENTITY_INSERT
                $db->unprepared('SET IDENTITY_INSERT mgr.pm_tenancy ON');
                try {
                    $db->table('mgr.pm_tenancy')->insert($tenancy + array('id' => $firstId, 'status' => 'A'));
                } finally {
                    $db->unprepared('SET IDENTITY_INSERT mgr.pm_tenancy OFF');
                }
            }

            return array('id' => $firstId, 'created' => $created);
        });
    }

    /** Tanggal SQL Server -> Y-m-d (null tetap null) */
    private function ymd($value)
    {
        return empty($value) ? null : date('Y-m-d', strtotime($value));
    }
    /**
     * business/delete/entity:project:tenant_no -> hapus pm_tenancy debtor itu. Kalau business-nya
     * tidak punya pm_tenancy lain, semua baris tenant business itu (F dan O) beserta akun
     * all_login masing-masing ikut dihapus. Debtor yang tidak ditemukan -> 'Bad request'.
     */
    public function del_business($value="")
    {
        $db = DB::connection('ifcaadm');
        try {
            $tenancy = $db->table('mgr.pm_tenancy')
                ->where('entity_cd', trim($value['entity_cd']))
                ->where('project_no', trim($value['project_no']))
                ->where('tenant_no', $value['tenant_no'])
                ->first();
            if (!$tenancy) {
                echo 'Bad request';
                return;
            }

            $db->transaction(function () use ($db, $tenancy) {
                $db->table('mgr.pm_tenancy')->where('id', $tenancy->id)->delete();

                if (!$db->table('mgr.pm_tenancy')->where('business_no', $tenancy->business_no)->exists()) {
                    $ids = $db->table('mgr.tenant')->where('business_no', $tenancy->business_no)->pluck('id');
                    $db->table('mgr.all_login')->where('tableforeign', 'tenant')->whereIn('idforeign', $ids)->delete();
                    $db->table('mgr.tenant')->where('business_no', $tenancy->business_no)->delete();
                }
            });
            echo 'Tenant no : ' . $tenancy->tenant_no . ' deleted!';
        } catch (\Illuminate\Database\QueryException $ex) {
            echo "Delete failed: " . $ex->getMessage();
        }
    }
}
