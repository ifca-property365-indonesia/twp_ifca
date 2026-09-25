<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan bahasa tampilan per user (email login all_login). User tanpa baris di sini
 * memakai English (default) sampai mengganti bahasa sendiri. Dipakai App\Support\UserLocale.
 * SQL manual yang sama: database/sql/2026-09-24_create_user_locale.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mgr.user_locale')) {
            return;
        }

        Schema::create('mgr.user_locale', function (Blueprint $table) {
            $table->string('email', 100)->primary();
            $table->string('locale', 5)->default('en')->comment('en | id');
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mgr.user_locale');
    }
};
