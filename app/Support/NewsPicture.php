<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Gambar News & Promo (tabel newsfeed.picture), file di images/newspromo/.
 *
 * Kolom picture sekarang berisi path relatif "images/newspromo/<file>". Data lama berisi URL
 * absolut dari aplikasi/host lain (mis. .../webadmin/images/newspromo/x.jpg atau
 * .../admin/images/newspromo/x.jpg) -> url() mengambil nama filenya saja supaya tetap tampil.
 */
class NewsPicture
{
    public const DIR = 'images/newspromo';

    /** Simpan file upload, kembalikan path relatif untuk kolom picture */
    public static function store(UploadedFile $file): string
    {
        $dir = base_path(self::DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // nama unik supaya file lain dengan nama sama tidak tertimpa
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') ?: 'news';
        $name = date('YmdHis') . '_' . Str::limit($base, 60, '') . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return self::DIR . '/' . $name;
    }

    /** Pengganti gambar yang tidak ada: logo IFCA. */
    public const FALLBACK = 'images/logo/IFCA.png';

    /** URL untuk <img src>; null kalau kosong, logo IFCA kalau file newspromo-nya tidak ada */
    public static function url(?string $stored): ?string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return null;
        }

        $pos = strpos($stored, self::DIR . '/');
        if ($pos !== false) {
            $file = basename(rawurldecode(parse_url(substr($stored, $pos), PHP_URL_PATH) ?: substr($stored, $pos)));
            return is_file(base_path(self::DIR . '/' . $file))
                ? url(self::DIR . '/' . rawurlencode($file))
                : self::fallbackUrl();
        }

        // gambar dari luar (bukan folder newspromo) dibiarkan apa adanya; <img> memakai onError()
        return preg_match('#^https?://#i', $stored) ? $stored : url($stored);
    }

    public static function fallbackUrl(): string
    {
        return url(self::FALLBACK);
    }

    /** Isi atribut onerror untuk <img> (tulis dengan {{ }}): gagal dimuat -> logo IFCA. */
    public static function onError(): string
    {
        return "this.onerror=null;this.src=" . json_encode(self::fallbackUrl(), JSON_UNESCAPED_SLASHES);
    }
}
