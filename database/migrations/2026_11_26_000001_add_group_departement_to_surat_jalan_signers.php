<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback MAI #2: kotak Pemeriksa ditujukan ke SELURUH member HR-GA, bukan 1 orang.
 *
 * - group_departement_id: bila terisi, kotak ini milik grup departemen tsb (mis. HR-GA);
 *   siapa pun member grup boleh menandatangani, cukup satu orang.
 * - user_id menjadi nullable: untuk kotak grup, user_id baru terisi dengan orang yang
 *   benar-benar menandatangani / menolak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trx_surat_jalan_signers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('group_departement_id')->nullable()->after('user_id');
            $table->index(['group_departement_id', 'status'], 'idx_sj_signer_group');
        });
    }

    public function down(): void
    {
        Schema::table('trx_surat_jalan_signers', function (Blueprint $table) {
            $table->dropIndex('idx_sj_signer_group');
            $table->dropColumn('group_departement_id');
        });
        // user_id dibiarkan nullable: baris grup yang belum ditandatangani tidak punya user_id.
    }
};
