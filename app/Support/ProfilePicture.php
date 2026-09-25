<?php

namespace App\Support;

/**
 * Foto profil akun (all_login.pict) untuk header & halaman profil, admin dan tenant.
 *
 * Kosong, berakhiran '/' (data lama tanpa nama file), atau file images/user/ yang sudah
 * tidak ada -> images/default/defaultUser.png. URL dari host lain tidak bisa dicek dari
 * server, jadi <img> memakai onerror=ProfilePicture::onError() sebagai cadangan.
 */
class ProfilePicture
{
    public const DEFAULT = 'images/default/defaultUser.png';

    public static function url($stored): string
    {
        $stored = trim((string) $stored);
        // .../images/logoweb/... = logo aplikasi lama yang dulu dipakai sebagai foto default
        if ($stored === '' || str_ends_with($stored, '/') || str_contains($stored, '/images/logoweb/')) {
            return self::defaultUrl();
        }

        // foto yang diunggah di aplikasi ini (images/user/, dulu img/user/)
        if (preg_match('#(?:^|/)(?:images|img)/user/([^/?]+)#', $stored, $m)) {
            $file = basename(rawurldecode($m[1]));
            return is_file(base_path('images/user/' . $file))
                ? url('images/user/' . rawurlencode($file))
                : self::defaultUrl();
        }

        return preg_match('#^https?://#i', $stored) ? $stored : url($stored);
    }

    public static function defaultUrl(): string
    {
        return url(self::DEFAULT);
    }

    /** Isi atribut onerror untuk <img> (tulis dengan {{ }}): gagal dimuat -> foto default. */
    public static function onError(): string
    {
        return "this.onerror=null;this.src=" . json_encode(self::defaultUrl(), JSON_UNESCAPED_SLASHES);
    }
}
