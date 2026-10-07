<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Lokasi file log per controller per tanggal:
 *   storage/logs/{Y-m-d}/{admin|tenant|shared}/{Controller}.log
 * mis. Admin\PermitController  -> storage/logs/2026-10-07/admin/Permit.log
 *      Tenant\PermitController -> storage/logs/2026-10-07/tenant/Permit.log
 *      PortalLoginController   -> storage/logs/2026-10-07/shared/PortalLogin.log
 * Di luar request HTTP (artisan, queue) -> storage/logs/{Y-m-d}/system/Console.log.
 *
 * Dipakai channel log 'controller' (App\Logging\ControllerLogHandler) dan middleware LogActivity.
 */
class ControllerLog
{
    /** Kunci input yang nilainya disamarkan di log. */
    private const SECRET = '/pass|pwd|token|secret|otp|captcha/i';

    /** Panjang maksimum satu nilai input di log (mis. gambar base64 dipotong). */
    private const MAX_VALUE = 300;

    /** Folder tanggal yang sudah dicek/dibuat pada proses ini (pembersihan cukup sekali per hari). */
    private static array $prepared = [];

    /** Path file log untuk request saat ini. */
    public static function path(): string
    {
        [$portal, $name] = self::target();
        $date = date('Y-m-d');
        $dir = storage_path('logs/' . $date . '/' . $portal);
        if (!isset(self::$prepared[$dir])) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
                self::prune($date);
            }
            self::$prepared[$dir] = true;
        }
        return $dir . '/' . $name . '.log';
    }

    /**
     * [portal, nama file] dari controller rute aktif. Portal mengikuti namespace controller
     * (Admin / Tenant); controller di luar keduanya mengikuti prefix URL, selain itu 'shared'.
     */
    public static function target(): array
    {
        $route = app()->bound('router') ? Route::current() : null;
        if (!$route) {
            return app()->runningInConsole() ? ['system', 'Console'] : [self::portalFromPath(), 'Http'];
        }

        $action = $route->getActionName();
        $class = str_contains($action, '@') ? strstr($action, '@', true) : $action;
        if ($class === 'Closure' || !str_starts_with($class, 'App\\')) {
            // Route::view / closure: dicatat sebagai halaman portal
            return [self::portalFromPath(), 'Page'];
        }

        $portal = match (true) {
            str_contains($class, '\\Controllers\\Admin\\')  => 'admin',
            str_contains($class, '\\Controllers\\Tenant\\') => 'tenant',
            default                                       => self::portalFromPath(),
        };
        $name = preg_replace('/Controller$/', '', class_basename($class)) ?: 'App';
        return [$portal, $name];
    }

    /** 'Controller@method' rute aktif, untuk isi log. */
    public static function action(): string
    {
        $route = Route::current();
        if (!$route) {
            return '-';
        }
        $action = $route->getActionName();
        return str_contains($action, '@') ? class_basename(strstr($action, '@', true)) . strstr($action, '@') : class_basename($action);
    }

    /** Input request yang aman untuk dicatat: rahasia disamarkan, nilai panjang dipotong, file = nama & ukuran. */
    public static function sanitize(array $input): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            if (is_string($key) && preg_match(self::SECRET, $key)) {
                $out[$key] = '***';
            } elseif (is_array($value)) {
                $out[$key] = self::sanitize($value);
            } elseif ($value instanceof UploadedFile) {
                $out[$key] = '[file ' . $value->getClientOriginalName() . ', ' . round($value->getSize() / 1024, 1) . ' KB]';
            } elseif (is_string($value) && mb_strlen($value) > self::MAX_VALUE) {
                $out[$key] = mb_substr($value, 0, 80) . '… [' . mb_strlen($value) . ' karakter]';
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    private static function portalFromPath(): string
    {
        if (app()->runningInConsole() && !app()->bound('request')) {
            return 'system';
        }
        $path = ltrim(request()->path(), '/');
        return match (true) {
            str_starts_with($path, 'admin')  => 'admin',
            str_starts_with($path, 'tenant') => 'tenant',
            default                          => 'shared',
        };
    }

    /** Hapus folder tanggal yang lebih tua dari LOG_KEEP_DAYS hari (default 30). */
    private static function prune(string $today): void
    {
        $keep = (int) config('logging.controller_keep_days', 30);
        if ($keep <= 0) {
            return;
        }
        $limit = date('Y-m-d', strtotime($today . ' -' . $keep . ' days'));
        foreach (glob(storage_path('logs/????-??-??'), GLOB_ONLYDIR) ?: [] as $dir) {
            if (basename($dir) < $limit) {
                File::deleteDirectory($dir);
            }
        }
    }
}
