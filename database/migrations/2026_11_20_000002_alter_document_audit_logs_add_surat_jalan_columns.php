<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas tabel document_audit_logs (awalnya dibuat untuk modul Adhoc)
 * agar bisa dipakai bersama modul Surat Jalan dan modul lain di masa depan.
 *
 * Perubahan:
 * - Hapus FK constraint document_id -> documents.id (agar bisa menampung id dari tabel lain)
 * - Tambah kolom document_type (string, nullable)
 * - Tambah kolom actor_position (string, nullable)
 * - Tambah kolom metadata (json, nullable)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop FK constraint on document_id (MySQL auto-names it)
        // try/catch di dalam closure Schema::table tidak berguna (perintah baru
        // dieksekusi setelah closure selesai), jadi cek dulu FK yang benar-benar ada.
        $fkNames = collect(Schema::getForeignKeys('document_audit_logs'))
            ->filter(fn ($fk) => $fk['columns'] === ['document_id'])
            ->pluck('name');

        if ($fkNames->isNotEmpty()) {
            Schema::table('document_audit_logs', function (Blueprint $table) use ($fkNames) {
                foreach ($fkNames as $name) {
                    $table->dropForeign($name);
                }
            });
        }

        // 2. Tambah kolom baru bila belum ada
        Schema::table('document_audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('document_audit_logs', 'document_type')) {
                $table->string('document_type', 50)->nullable()->after('id');
            }
            if (!Schema::hasColumn('document_audit_logs', 'actor_position')) {
                $table->string('actor_position', 100)->nullable()->after('actor_email');
            }
            if (!Schema::hasColumn('document_audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('user_agent');
            }
        });

        // 3. Isi document_type untuk baris lama (adhoc) agar tidak NULL
        DB::table('document_audit_logs')
            ->whereNull('document_type')
            ->update(['document_type' => 'ADHOC']);

        // 4. Index untuk query per dokumen (document_type + document_id)
        if (!Schema::hasIndex('document_audit_logs', 'idx_audit_doc')) {
            Schema::table('document_audit_logs', function (Blueprint $table) {
                $table->index(['document_type', 'document_id', 'created_at'], 'idx_audit_doc');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('document_audit_logs', 'idx_audit_doc')) {
            Schema::table('document_audit_logs', function (Blueprint $table) {
                $table->dropIndex('idx_audit_doc');
            });
        }

        Schema::table('document_audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('document_audit_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('document_audit_logs', 'actor_position')) {
                $table->dropColumn('actor_position');
            }
            if (Schema::hasColumn('document_audit_logs', 'document_type')) {
                $table->dropColumn('document_type');
            }
        });
    }
};
