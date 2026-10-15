<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ReportDocuments;
use Illuminate\Http\Request;
use Throwable;
use ZipArchive;

/**
 * Menu admin Reports & Documents: upload report per group (banyak file sekaligus) dengan versi.
 * Logika versi & penyimpanan: App\Support\ReportDocuments.
 */
class ReportController extends Controller
{
    /** Library report: selalu semua group; penyaringan lewat kotak cari (nama, perusahaan, file, pengunggah). */
    public function index()
    {
        return view('admin.reports.index', [
            'groups'       => ReportDocuments::groups(),
            'uploadGroups' => ReportDocuments::uploadGroups(),
            'reports'      => ReportDocuments::reports(),
            'extensions' => ReportDocuments::EXTENSIONS,
            'inline'     => ReportDocuments::INLINE,
            'maxKb'      => ReportDocuments::MAX_KB,
            'maxFiles'   => (int) ini_get('max_file_uploads') ?: 20,
            'maxPost'    => $this->iniBytes((string) ini_get('post_max_size')),
        ]);
    }

    /** Upload report baru (V1): group, name, files[]. */
    public function store(Request $request)
    {
        // upload hanya ke perusahaan; Consolidated hanya untuk melihat semua report
        $groups = ReportDocuments::uploadGroups();
        $group = (string) $request->input('group');
        $name = trim((string) $request->input('name'));
        $files = $this->files($request);

        $errors = [];
        if (!array_key_exists($group, $groups)) {
            $errors[] = __('admin/reports.err_group');
        }
        if ($name === '') {
            $errors[] = __('admin/reports.err_name');
        } elseif (mb_strlen($name) > ReportDocuments::MAX_NAME) {
            $errors[] = __('admin/reports.err_name_long', ['max' => ReportDocuments::MAX_NAME]);
        } elseif (!$errors && ReportDocuments::nameExists($group, $name)) {
            $errors[] = __('admin/reports.err_name_exists');
        }
        if (!$files) {
            $errors[] = __('admin/reports.err_no_files');
        }
        $errors = array_merge($errors, ReportDocuments::validateFiles($files));
        if ($errors) {
            return response()->json(['status' => 'Failed', 'pesan' => implode("\n", $errors)], 422);
        }

        try {
            ReportDocuments::create($group, $name, $files);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/reports.err_save', ['message' => $e->getMessage()])], 500);
        }
        return response()->json(['status' => 'OK', 'pesan' => trans_choice('admin/reports.saved', count($files), ['name' => $name, 'count' => count($files)])]);
    }

    /** Versi baru: files[] saja (versi lama tidak dibawa, tetap ada di riwayat). */
    public function version(Request $request, int $id)
    {
        if (!ReportDocuments::find($id)) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/reports.not_found')], 404);
        }
        $files = $this->files($request);
        $errors = $files ? ReportDocuments::validateFiles($files) : [__('admin/reports.err_no_files')];
        if ($errors) {
            return response()->json(['status' => 'Failed', 'pesan' => implode("\n", $errors)], 422);
        }

        try {
            $version = ReportDocuments::newVersion($id, $files);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/reports.err_save', ['message' => $e->getMessage()])], 500);
        }
        return response()->json([
            'status' => 'OK',
            'pesan'  => trans_choice('admin/reports.version_saved', count($files), ['version' => $version, 'count' => count($files)]),
        ]);
    }

    /** Hapus report beserta semua versi & file-nya. */
    public function destroy(int $id)
    {
        try {
            $name = ReportDocuments::delete($id);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/reports.err_delete', ['message' => $e->getMessage()])], 500);
        }
        if ($name === null) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/reports.not_found')], 404);
        }
        return response()->json(['status' => 'OK', 'pesan' => __('admin/reports.deleted', ['name' => $name])]);
    }

    /** Riwayat versi satu report (untuk dialog View). */
    public function history(int $id)
    {
        $report = ReportDocuments::find($id);
        if (!$report) {
            return response()->json(['status' => 'Failed', 'pesan' => __('admin/reports.not_found')], 404);
        }
        return view('admin.reports.history', ['report' => $report, 'groups' => ReportDocuments::groups(), 'inline' => ReportDocuments::INLINE]);
    }

    /** Unduh satu file; ?inline=1 membuka di browser (PDF, gambar, teks). */
    public function file(Request $request, int $fileId)
    {
        $file = ReportDocuments::file($fileId);
        $path = $file ? ReportDocuments::absolutePath($file) : null;
        abort_unless($path, 404);

        $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
        if ($request->boolean('inline') && in_array($ext, ReportDocuments::INLINE, true)) {
            return response()->file($path, ['Content-Disposition' => 'inline; filename="' . addslashes($file->file_name) . '"']);
        }
        return response()->download($path, $file->file_name);
    }

    /** Unduh semua file satu versi (default versi terakhir) sebagai .zip. */
    public function download(Request $request, int $id)
    {
        $report = ReportDocuments::find($id);
        abort_unless($report, 404);
        $version = $report->versions->firstWhere('version', (int) ($request->query('version') ?: $report->current_version));
        abort_unless($version && $version->files->isNotEmpty(), 404);

        $zipPath = tempnam(sys_get_temp_dir(), 'rpt');
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::OVERWRITE) === true, 500);
        foreach ($version->files as $f) {
            if ($path = ReportDocuments::absolutePath($f)) {
                $zip->addFile($path, $f->file_name);
            }
        }
        $zip->close();

        $zipName = ReportDocuments::cleanName($report->name) . ' - v' . $version->version . '.zip';
        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    /** @return \Illuminate\Http\UploadedFile[] */
    private function files(Request $request): array
    {
        $files = $request->file('files', []);
        return array_values(array_filter(is_array($files) ? $files : [$files]));
    }

    /** '40M' -> byte */
    private function iniBytes(string $value): int
    {
        $value = trim($value);
        $num = (int) $value;
        return match (strtoupper(substr($value, -1))) {
            'G' => $num * 1073741824,
            'M' => $num * 1048576,
            'K' => $num * 1024,
            default => $num,
        };
    }
}
