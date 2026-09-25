<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Catatan login (mgr.log_login) untuk admin dan tenant, ditampilkan di Admin -> History -> Log User.
 * Dipanggil dari createSession masing-masing portal (login dan pindah portal lewat header).
 */
class LoginLog
{
    public const TENANT = 'tenant';
    public const ADMIN = 'administrator';

    /**
     * @param string $type  LoginLog::TENANT | LoginLog::ADMIN
     * @param int    $id    tenant.id / administrator.id
     * @param string $email email yang dipakai saat login
     */
    public static function record($type, $id, $email)
    {
        $request = request();

        try {
            DB::connection('ifcaadm')->table('mgr.log_login')->insert([
                'idforeign'    => (int) $id,
                'tableforeign' => $type,
                'email'        => mb_substr(strtolower(trim((string) ($email ?: Session::get('login_email')))), 0, 100) ?: null,
                'logintime'    => date('Y-m-d H:i:s'),
                // IP asli klien kalau lewat proxy / load balancer
                'ipaddress'    => mb_substr(trim(explode(',', (string) $request->header('X-Forwarded-For'))[0]) ?: (string) $request->ip(), 0, 50),
                'user_agent'   => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
            ]);
        } catch (\Throwable $e) {
            // gagal mencatat log tidak boleh menggagalkan login
            Log::warning('LoginLog::record: ' . $e->getMessage());
        }
    }

    /** Ringkasan User-Agent, mis. "Chrome 128 · Windows" (kosong kalau tidak dikenali). */
    public static function device($userAgent)
    {
        $ua = (string) $userAgent;
        if ($ua === '') {
            return '';
        }

        $browser = '';
        foreach ([
            'Edge'    => '#Edg(?:e|A|iOS)?/(\d+)#',
            'Opera'   => '#(?:OPR|Opera)/(\d+)#',
            'Samsung' => '#SamsungBrowser/(\d+)#',
            'Firefox' => '#(?:Firefox|FxiOS)/(\d+)#',
            'Chrome'  => '#(?:Chrome|CriOS)/(\d+)#',
            'Safari'  => '#Version/(\d+).*Safari#',
        ] as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                $browser = $name . ' ' . $m[1];
                break;
            }
        }

        $os = '';
        foreach ([
            'Android' => '#Android#i',
            'iOS'     => '#iPhone|iPad|iPod#i',
            'Windows' => '#Windows#i',
            'macOS'   => '#Mac OS X|Macintosh#i',
            'Linux'   => '#Linux#i',
        ] as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                $os = $name;
                break;
            }
        }

        return trim($browser . ($browser && $os ? ' · ' : '') . $os) ?: mb_substr($ua, 0, 40);
    }
}
