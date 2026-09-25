<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * mgr.log_login dipakai untuk login admin juga (dulu hanya tenant), jadi perlu tahu jenis
 * akunnya, email yang dipakai saat login, dan browser / perangkat (App\Support\LoginLog).
 *   tableforeign : 'tenant' | 'administrator' (baris lama = tenant)
 *   email        : email login saat itu
 *   user_agent   : header User-Agent browser
 * SQL manual yang sama: database/sql/2026-09-25_log_login_account.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection('ifcaadm');

        foreach ([
            'tableforeign' => 'NVARCHAR(20) NULL',
            'email'        => 'NVARCHAR(100) NULL',
            'user_agent'   => 'NVARCHAR(500) NULL',
        ] as $column => $type) {
            if (!$this->hasColumn($column)) {
                $db->statement("ALTER TABLE mgr.log_login ADD {$column} {$type}");
            }
        }

        $db->statement("UPDATE mgr.log_login SET tableforeign = 'tenant' WHERE tableforeign IS NULL");
    }

    public function down(): void
    {
        foreach (['user_agent', 'email', 'tableforeign'] as $column) {
            if ($this->hasColumn($column)) {
                DB::connection('ifcaadm')->statement("ALTER TABLE mgr.log_login DROP COLUMN {$column}");
            }
        }
    }

    private function hasColumn(string $column): bool
    {
        return DB::connection('ifcaadm')->table('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_SCHEMA', 'mgr')->where('TABLE_NAME', 'log_login')->where('COLUMN_NAME', $column)
            ->exists();
    }
};
