<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * File denah lantai (layout) untuk form Overtime, di images/layout/<entity>/<project>/.
 *
 * Nama file (tanpa ekstensi) = kolom picture di mgr.pm_floor_plan (demo_twp), jadi satu
 * baris floor plan = satu file. Diunggah admin lewat menu Overtime -> Floor Layout
 * (Admin\FloorLayoutController), ditampilkan di Overtime tenant (Tenant\OvertimeController::layout).
 */
class FloorLayout
{
    public const DIR = 'images/layout';

    /** Ekstensi yang boleh diunggah / dicari (semua bisa ditampilkan browser). */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    /** Nama picture yang aman dipakai sebagai nama file. */
    public static function validName($picture): bool
    {
        return is_string($picture) && $picture !== '' && (bool) preg_match('/^[\w .()-]+$/', $picture);
    }

    /** Path relatif folder layout satu entity + project. */
    public static function folder($entity, $project): string
    {
        return self::DIR . '/' . self::segment($entity) . '/' . self::segment($project);
    }

    /** Path relatif file layout (mis. images/layout/01/01/PENTH.png), atau null kalau belum ada. */
    public static function find($entity, $project, $picture): ?string
    {
        $picture = trim((string) $picture);
        if (!self::validName($picture)) {
            return null;
        }
        foreach (self::EXTENSIONS as $ext) {
            foreach ([$ext, strtoupper($ext)] as $e) {
                $path = self::folder($entity, $project) . '/' . $picture . '.' . $e;
                if (is_file(base_path($path))) {
                    return $path;
                }
            }
        }
        return null;
    }

    /** URL untuk <img src> (nama file di-encode, boleh berisi spasi). */
    public static function url(string $path): string
    {
        $parts = explode('/', $path);
        $file = rawurlencode(array_pop($parts));
        // ?v= supaya browser tidak menampilkan gambar lama dari cache setelah diganti
        return url(implode('/', $parts) . '/' . $file) . '?v=' . @filemtime(base_path($path));
    }

    /** Simpan file sebagai <picture>.<ext>; file lama picture yang sama (ekstensi lain) dihapus. */
    public static function store(UploadedFile $file, $entity, $project, $picture): string
    {
        $picture = trim((string) $picture);
        $dir = base_path(self::folder($entity, $project));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        self::delete($entity, $project, $picture);

        $name = $picture . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return self::folder($entity, $project) . '/' . $name;
    }

    /** Hapus file layout picture ini (semua ekstensi). */
    public static function delete($entity, $project, $picture): bool
    {
        $deleted = false;
        while ($path = self::find($entity, $project, $picture)) {
            if (!@unlink(base_path($path))) {
                break;
            }
            $deleted = true;
        }
        return $deleted;
    }

    /** Kode entity / project sebagai nama folder (CHAR di IFCA: spasi di belakang dibuang). */
    private static function segment($value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', trim((string) $value)) ?: '_';
    }
}
