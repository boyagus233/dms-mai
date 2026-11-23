<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * actor_name dibuat NOT NULL oleh migration Ad-Hoc, sedangkan DocumentAuditLogger
 * (Surat Jalan) bisa mencatat event tanpa user login (aksi sistem) sehingga
 * actor_name bernilai null. Jadikan nullable agar insert log tidak gagal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->string('actor_name', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('document_audit_logs')->whereNull('actor_name')->update(['actor_name' => 'System']);

        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->string('actor_name', 150)->nullable(false)->change();
        });
    }
};
