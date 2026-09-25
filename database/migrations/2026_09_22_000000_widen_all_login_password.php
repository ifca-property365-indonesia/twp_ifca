<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Password all_login pindah dari md5 (32 karakter) ke bcrypt (60 karakter),
 * jadi kolomnya harus dilebarkan dari varchar(40).
 *
 * Tabel ini dipakai lewat dua koneksi (mysql & ifcaadm). Di sebagian environment
 * keduanya menunjuk database yang sama, jadi ALTER dijalankan sekali per database.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->targets() as $connection) {
            DB::connection($connection)->statement(
                'ALTER TABLE mgr.all_login ALTER COLUMN password NVARCHAR(255) NOT NULL'
            );
        }
    }

    /**
     * Sengaja tidak mengecilkan kolom kembali: hash bcrypt yang sudah tersimpan
     * akan terpotong dan seluruh akun tidak bisa login lagi.
     */
    public function down(): void
    {
        //
    }

    /** Koneksi yang perlu di-ALTER, satu per database (menghindari ALTER ganda). */
    private function targets(): array
    {
        $targets = [];
        $seen = [];

        foreach (['mysql', 'ifcaadm'] as $connection) {
            try {
                $db = DB::connection($connection)->getDatabaseName();
            } catch (\Throwable $e) {
                continue;
            }

            if (!isset($seen[$db])) {
                $seen[$db] = true;
                $targets[] = $connection;
            }
        }

        return $targets;
    }
};
