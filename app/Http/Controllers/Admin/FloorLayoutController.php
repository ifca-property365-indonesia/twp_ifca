<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FloorLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Menu Overtime -> Floor Layout: gambar denah lantai (images/layout/<entity>/<project>/)
 * untuk form Overtime tenant. Daftar floor plan dari mgr.pm_floor_plan (demo_twp);
 * nama file = kolom picture, lihat App\Support\FloorLayout.
 */
class FloorLayoutController extends Controller
{
    /** Batas ukuran per file (KB). */
    private const MAX_KB = 10240;

    public function index()
    {
        $entities = $projects = collect();
        try {
            $entities = DB::connection('ifcapb')->table('mgr.cf_entity')->orderBy('entity_cd')->get(['entity_cd', 'entity_name']);
            $projects = DB::connection('ifcapb')->table('mgr.pl_project')->orderBy('project_no')->get(['entity_cd', 'project_no', 'descs']);
        } catch (\Throwable $e) {
        }

        return view('admin.overtime.layout', [
            'entities'   => $entities,
            'projects'   => $projects,
            'extensions' => FloorLayout::EXTENSIONS,
            'maxSize'    => (self::MAX_KB / 1024) . 'MB',
        ]);
    }

    /** Floor plan entity + project beserta status file layout-nya (JSON untuk DataTables). */
    public function data(Request $request)
    {
        $entity = trim((string) $request->entity);
        $project = trim((string) $request->project);
        $rows = [];

        foreach ($this->plans($entity, $project) as $i => $plan) {
            $file = FloorLayout::find($entity, $project, $plan->picture);
            $rows[] = [
                'row_number' => $i + 1,
                'level_no'   => trim((string) $plan->level_no),
                'zone_cd'    => trim((string) $plan->zone_cd),
                'remarks'    => trim((string) $plan->remarks),
                'picture'    => trim((string) $plan->picture),
                'image_url'  => $file ? FloorLayout::url($file) : null,
            ];
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Upload banyak gambar sekaligus (files[]). Nama file tanpa ekstensi dicocokkan dengan
     * picture floor plan di entity + project yang dipilih (tidak membedakan huruf besar/kecil).
     * Dengan 'picture' terisi (tombol Ganti per baris), file apa pun disimpan untuk picture itu.
     */
    public function upload(Request $request)
    {
        $entity = trim((string) $request->entity);
        $project = trim((string) $request->project);
        if ($entity === '' || $project === '') {
            return response()->json(['status' => 'Fail', 'pesan' => __('admin/overtime.layout_choose_ep')]);
        }

        $files = $request->file('files', []);
        $files = is_array($files) ? $files : [$files];
        if (!$files) {
            return response()->json(['status' => 'Fail', 'pesan' => __('admin/overtime.layout_no_files')]);
        }

        // picture -> nama asli (huruf besar/kecil) dari pm_floor_plan
        $pictures = [];
        foreach ($this->plans($entity, $project) as $plan) {
            $name = trim((string) $plan->picture);
            if (FloorLayout::validName($name)) {
                $pictures[strtolower($name)] = $name;
            }
        }
        $forced = trim((string) $request->picture);

        $results = [];
        $saved = 0;
        $extText = strtoupper(implode(', ', FloorLayout::EXTENSIONS));
        foreach ($files as $file) {
            $original = $file->getClientOriginalName();
            $key = $forced !== '' ? $forced : pathinfo($original, PATHINFO_FILENAME);
            $picture = $pictures[strtolower(trim($key))] ?? null;
            $ext = strtolower($file->getClientOriginalExtension());

            if (!$file->isValid()) {
                $message = __('admin/overtime.layout_failed', ['message' => $file->getErrorMessage()]);
            } elseif (!in_array($ext, FloorLayout::EXTENSIONS, true) || !str_starts_with((string) $file->getMimeType(), 'image/')) {
                $message = __('admin/overtime.layout_invalid_type', ['ext' => $extText]);
            } elseif ($file->getSize() > self::MAX_KB * 1024) {
                $message = __('admin/overtime.layout_too_large', ['size' => (self::MAX_KB / 1024) . 'MB']);
            } elseif ($picture === null) {
                $message = __('admin/overtime.layout_unmatched', ['picture' => $key]);
            } else {
                try {
                    FloorLayout::store($file, $entity, $project, $picture);
                    $saved++;
                    $results[] = ['file' => $original, 'ok' => true, 'message' => __('admin/overtime.layout_saved', ['picture' => $picture])];
                    continue;
                } catch (\Throwable $e) {
                    $message = __('admin/overtime.layout_failed', ['message' => $e->getMessage()]);
                }
            }
            $results[] = ['file' => $original, 'ok' => false, 'message' => $message];
        }

        return response()->json([
            'status'  => $saved > 0 ? 'OK' : 'Fail',
            'pesan'   => __('admin/overtime.layout_result', ['saved' => $saved, 'total' => count($files)]),
            'results' => $results,
        ]);
    }

    public function delete(Request $request)
    {
        $entity = trim((string) $request->entity);
        $project = trim((string) $request->project);
        $picture = trim((string) $request->picture);

        $exists = $this->plans($entity, $project)->contains(function ($plan) use ($picture) {
            return strcasecmp(trim((string) $plan->picture), $picture) === 0;
        });
        if (!$exists) {
            return response()->json(['status' => 'Fail', 'pesan' => __('admin/overtime.layout_not_found')]);
        }

        FloorLayout::delete($entity, $project, $picture);

        return response()->json(['status' => 'OK', 'pesan' => __('admin/overtime.layout_deleted')]);
    }

    /** Baris mgr.pm_floor_plan (kolom CHAR IFCA: dibandingkan setelah RTRIM). */
    private function plans($entity, $project)
    {
        if ($entity === '' || $project === '') {
            return collect();
        }

        return DB::connection('ifcapb')->table('mgr.pm_floor_plan')
            ->whereRaw('RTRIM(entity_cd) = ?', [$entity])
            ->whereRaw('RTRIM(project_no) = ?', [$project])
            ->orderBy('level_no')
            ->get(['level_no', 'zone_cd', 'remarks', 'picture']);
    }
}
