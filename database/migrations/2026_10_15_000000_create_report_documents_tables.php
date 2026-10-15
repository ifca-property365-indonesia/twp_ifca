<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tabel menu admin Reports & Documents (App\Support\ReportDocuments):
 *   mgr.report_doc, mgr.report_doc_version, mgr.report_doc_file
 * SQL manual yang sama: database/sql/2026-10-15_create_report_documents.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('sql/2026-10-15_create_report_documents.sql'));
        // jalankan per pernyataan (dipisah baris kosong), komentar diabaikan
        foreach (preg_split('/\n\s*\n/', $sql) as $statement) {
            $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement));
            if ($statement !== '') {
                DB::connection('ifcaadm')->statement($statement);
            }
        }
    }

    public function down(): void
    {
        $db = DB::connection('ifcaadm');
        foreach (['report_doc_file', 'report_doc_version', 'report_doc'] as $table) {
            $db->statement("IF OBJECT_ID('mgr.{$table}', 'U') IS NOT NULL DROP TABLE mgr.{$table}");
        }
    }
};
