<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Menu admin Reports & Documents: report per group (Consolidated / PT IFCA ...), tiap upload = versi baru.
 *
 * Versi baru = file versi sebelumnya + file yang di-upload; file dengan nama sama (tidak membedakan
 * huruf besar/kecil) diganti oleh file baru, file lain dibawa ke versi baru tanpa disalin ulang.
 *   V1: a, b, c, d, e   -> upload a, b, c, d (nama sama)  -> V2: a', b', c', d' + e (dari V1)
 *
 * Tabel: mgr.report_doc, mgr.report_doc_version, mgr.report_doc_file (koneksi ifcaadm).
 * File : disk 'local' (storage/app/private) -> reports/{id}/v{versi}/{nama file}
 */
class ReportDocuments
{
    /** Ekstensi yang boleh di-upload. */
    public const EXTENSIONS = ['pdf', 'xls', 'xlsx', 'xlsm', 'csv', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];

    /** Bisa dibuka langsung di browser (tombol lihat); lainnya diunduh. */
    public const INLINE = ['pdf', 'txt', 'jpg', 'jpeg', 'png'];

    /** Ukuran maksimum per file (KB). */
    public const MAX_KB = 20480;

    /** Panjang maksimum nama report. */
    public const MAX_NAME = 200;

    private const DISK = 'local';

    private static function db()
    {
        return DB::connection('ifcaadm');
    }

    /** Pilihan group: sama dengan filter Financials (Consolidated + perusahaan). */
    public static function groups(): array
    {
        return FinancialsDemo::options()['group'];
    }

    /**
     * Daftar report (versi terakhir + file-nya), terbaru di atas. $group 'consolidated' = semua group.
     * @return Collection<int, object>
     */
    public static function reports(string $group = 'consolidated'): Collection
    {
        $query = self::db()->table('mgr.report_doc as r')
            ->join('mgr.report_doc_version as v', function ($join) {
                $join->on('v.report_id', '=', 'r.id')->on('v.version', '=', 'r.current_version');
            })
            ->select('r.id', 'r.group_cd', 'r.name', 'r.current_version', 'r.created_at', 'v.id as version_id',
                'v.uploaded_by', 'v.uploaded_email', 'v.uploaded_at')
            ->orderByDesc('v.uploaded_at')->orderByDesc('r.id');
        if ($group !== 'consolidated') {
            $query->where('r.group_cd', $group);
        }
        $reports = $query->get();

        $files = self::filesOf($reports->pluck('version_id')->all());
        return $reports->map(function ($r) use ($files) {
            $r->files = $files->get($r->version_id, collect())->values();
            return $r;
        });
    }

    /** Satu report beserta semua versinya (terbaru dulu), tiap versi dengan file-nya. */
    public static function find(int $id): ?object
    {
        $report = self::db()->table('mgr.report_doc')->where('id', $id)->first();
        if (!$report) {
            return null;
        }
        $versions = self::db()->table('mgr.report_doc_version')->where('report_id', $id)->orderByDesc('version')->get();
        $files = self::filesOf($versions->pluck('id')->all());
        $report->versions = $versions->map(function ($v) use ($files) {
            $v->files = $files->get($v->id, collect())->values();
            return $v;
        });
        return $report;
    }

    /** Satu file (dengan id report & versi), untuk diunduh. */
    public static function file(int $fileId): ?object
    {
        return self::db()->table('mgr.report_doc_file as f')
            ->join('mgr.report_doc_version as v', 'v.id', '=', 'f.version_id')
            ->select('f.*', 'v.report_id', 'v.version')
            ->where('f.id', $fileId)
            ->first();
    }

    /** Path lengkap file di disk (null kalau file fisiknya hilang). */
    public static function absolutePath(object $file): ?string
    {
        $disk = Storage::disk(self::DISK);
        return $disk->exists($file->stored_path) ? $disk->path($file->stored_path) : null;
    }

    /** Apakah report dengan nama ini sudah ada di group (tidak membedakan huruf besar/kecil). */
    public static function nameExists(string $group, string $name): bool
    {
        return self::db()->table('mgr.report_doc')
            ->where('group_cd', $group)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    /**
     * Cek file upload: tipe, ukuran, nama ganda dalam satu upload. Kembali: daftar pesan error (kosong = OK).
     * @param UploadedFile[] $files
     */
    public static function validateFiles(array $files): array
    {
        $errors = [];
        $seen = [];
        foreach ($files as $file) {
            $name = self::cleanName($file->getClientOriginalName());
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!$file->isValid()) {
                $errors[] = __('admin/reports.err_upload', ['file' => $name, 'message' => $file->getErrorMessage()]);
            } elseif (!in_array($ext, self::EXTENSIONS, true)) {
                $errors[] = __('admin/reports.err_type', ['file' => $name, 'ext' => strtoupper(implode(', ', self::EXTENSIONS))]);
            } elseif ($file->getSize() > self::MAX_KB * 1024) {
                $errors[] = __('admin/reports.err_size', ['file' => $name, 'size' => (self::MAX_KB / 1024) . ' MB']);
            } elseif (isset($seen[mb_strtolower($name)])) {
                $errors[] = __('admin/reports.err_duplicate', ['file' => $name]);
            }
            $seen[mb_strtolower($name)] = true;
        }
        return $errors;
    }

    /**
     * Report baru (V1).
     * @param UploadedFile[] $files
     */
    public static function create(string $group, string $name, array $files): int
    {
        $user = self::user();
        $saved = [];
        try {
            return self::db()->transaction(function () use ($group, $name, $files, $user, &$saved) {
                $id = (int) self::db()->table('mgr.report_doc')->insertGetId([
                    'group_cd'        => $group,
                    'name'            => $name,
                    'current_version' => 1,
                    'created_by'      => $user['name'],
                    'updated_by'      => $user['name'],
                ]);
                $versionId = self::addVersion($id, 1, $user);
                foreach ($files as $file) {
                    $saved[] = self::storeFile($file, $id, 1, $versionId);
                }
                return $id;
            });
        } catch (Throwable $e) {
            self::cleanup($saved);
            throw $e;
        }
    }

    /**
     * Versi baru: file versi terakhir + file baru (nama sama = diganti). Kembali: nomor versi baru
     * dan ringkasan [replaced, added, kept] (nama file).
     * @param UploadedFile[] $files
     */
    public static function newVersion(int $id, array $files): array
    {
        $user = self::user();
        $saved = [];
        try {
            return self::db()->transaction(function () use ($id, $files, $user, &$saved) {
                $report = self::db()->table('mgr.report_doc')->where('id', $id)->lockForUpdate()->first();
                if (!$report) {
                    throw new RuntimeException(__('admin/reports.not_found'));
                }
                $previous = self::db()->table('mgr.report_doc_version')
                    ->where('report_id', $id)->where('version', $report->current_version)->first();
                $previousFiles = $previous
                    ? self::db()->table('mgr.report_doc_file')->where('version_id', $previous->id)->get()->keyBy(fn ($f) => mb_strtolower($f->file_name))
                    : collect();

                $version = (int) $report->current_version + 1;
                $versionId = self::addVersion($id, $version, $user);

                $summary = ['replaced' => [], 'added' => [], 'kept' => []];
                $uploaded = [];
                foreach ($files as $file) {
                    $row = self::storeFile($file, $id, $version, $versionId);
                    $saved[] = $row;
                    $key = mb_strtolower($row['file_name']);
                    $uploaded[$key] = true;
                    $summary[$previousFiles->has($key) ? 'replaced' : 'added'][] = $row['file_name'];
                }
                // file versi sebelumnya yang tidak diganti: dibawa (path & versi asal tetap)
                foreach ($previousFiles as $key => $f) {
                    if (isset($uploaded[$key])) {
                        continue;
                    }
                    self::db()->table('mgr.report_doc_file')->insert([
                        'version_id'   => $versionId,
                        'file_name'    => $f->file_name,
                        'stored_path'  => $f->stored_path,
                        'size'         => $f->size,
                        'mime'         => $f->mime,
                        'from_version' => $f->from_version,
                    ]);
                    $summary['kept'][] = $f->file_name;
                }

                self::db()->table('mgr.report_doc')->where('id', $id)->update([
                    'current_version' => $version,
                    'updated_by'      => $user['name'],
                    'updated_at'      => now(),
                ]);
                return ['version' => $version, 'summary' => $summary];
            });
        } catch (Throwable $e) {
            self::cleanup($saved);
            throw $e;
        }
    }

    /**
     * Hapus report beserta semua versi & file-nya (baris versi/file ikut terhapus lewat ON DELETE CASCADE).
     * File fisik (reports/{id}) dihapus setelah data terhapus. Kembali: nama report, atau null kalau tidak ada.
     */
    public static function delete(int $id): ?string
    {
        $name = self::db()->transaction(function () use ($id) {
            $report = self::db()->table('mgr.report_doc')->where('id', $id)->lockForUpdate()->first();
            if (!$report) {
                return null;
            }
            // hapus eksplisit (tidak bergantung pada cascade kalau tabel dibuat manual tanpa FK)
            $versionIds = self::db()->table('mgr.report_doc_version')->where('report_id', $id)->pluck('id')->all();
            if ($versionIds) {
                self::db()->table('mgr.report_doc_file')->whereIn('version_id', $versionIds)->delete();
            }
            self::db()->table('mgr.report_doc_version')->where('report_id', $id)->delete();
            self::db()->table('mgr.report_doc')->where('id', $id)->delete();
            return $report->name;
        });
        if ($name !== null) {
            Storage::disk(self::DISK)->deleteDirectory('reports/' . $id);
        }
        return $name;
    }

    /** Nama file aman: tanpa folder & karakter terlarang Windows, maksimal 200 karakter. */
    public static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F<>:"\/\\\\|?*]+/u', '_', $name);
        $name = trim($name, " .");
        if (mb_strlen($name) > 200) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $name = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 190) . ($ext !== '' ? '.' . $ext : '');
        }
        return $name !== '' ? $name : 'file';
    }

    /** Ukuran file ringkas: 1,8 MB / 980 KB. */
    public static function size($bytes): string
    {
        $bytes = (float) $bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }
        return (int) $bytes . ' B';
    }

    // ------------------------------------------------------------------

    private static function addVersion(int $reportId, int $version, array $user): int
    {
        return (int) self::db()->table('mgr.report_doc_version')->insertGetId([
            'report_id'      => $reportId,
            'version'        => $version,
            'uploaded_by'    => $user['name'],
            'uploaded_email' => $user['email'],
        ]);
    }

    /** Simpan file ke reports/{id}/v{versi}/ dan catat di mgr.report_doc_file. */
    private static function storeFile(UploadedFile $file, int $reportId, int $version, int $versionId): array
    {
        $name = self::cleanName($file->getClientOriginalName());
        $dir = 'reports/' . $reportId . '/v' . $version;
        $path = Storage::disk(self::DISK)->putFileAs($dir, $file, $name);
        if (!$path) {
            throw new RuntimeException(__('admin/reports.err_store', ['file' => $name]));
        }
        $row = [
            'version_id'   => $versionId,
            'file_name'    => $name,
            'stored_path'  => $path,
            'size'         => (int) $file->getSize(),
            'mime'         => mb_substr((string) $file->getClientMimeType(), 0, 150),
            'from_version' => $version,
        ];
        self::db()->table('mgr.report_doc_file')->insert($row);
        return $row;
    }

    /** Hapus file yang sudah tersimpan kalau proses gagal di tengah jalan. */
    private static function cleanup(array $rows): void
    {
        foreach ($rows as $row) {
            Storage::disk(self::DISK)->delete($row['stored_path']);
        }
    }

    private static function filesOf(array $versionIds): Collection
    {
        if (!$versionIds) {
            return collect();
        }
        return self::db()->table('mgr.report_doc_file')
            ->whereIn('version_id', $versionIds)
            ->orderBy('file_name')
            ->get()
            ->groupBy('version_id');
    }

    /** User admin yang login (nama & email dari session). */
    private static function user(): array
    {
        return [
            'name'  => mb_substr((string) (Session::get('Tsuname') ?: Session::get('Tsemail') ?: 'Admin'), 0, 100),
            'email' => mb_substr((string) Session::get('Tsemail'), 0, 100) ?: null,
        ];
    }
}
