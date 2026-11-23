<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Surat Jalan (Online Sign Approval).
 *
 * Tabel audit log (document_audit_logs) TIDAK dibuat di sini: tabel itu dibuat
 * oleh migration Ad-Hoc (2026_11_20_000001) lalu diperluas untuk dipakai
 * bersama oleh 2026_11_20_000002 (kolom document_type, actor_position, metadata).
 *
 * Bila beberapa migration lama berstatus Pending padahal tabelnya sudah ada
 * dari dump SQL, jalankan per file:
 *   php artisan migrate --path=database/migrations/2026_11_19_000001_create_surat_jalan_tables.php
 *   php artisan migrate --path=database/migrations/2026_11_20_000001_create_adhoc_documents_tables.php
 *   php artisan migrate --path=database/migrations/2026_11_20_000002_alter_document_audit_logs_add_surat_jalan_columns.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_surat_jalan', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('sj_no', 50)->nullable()->unique();   // diisi saat submit: SJ/DEPT/YYYY/MM/XXXX
            $table->unsignedBigInteger('departement_id');
            $table->string('location', 50)->default('Karawang');
            $table->date('sj_date')->nullable();                  // tanggal pengajuan (saat submit)
            $table->string('kepada_tipe', 30)->nullable();        // SUPPLIER, CUSTOMER, VENDOR_REPAIR, LAINNYA
            $table->string('kepada', 150);
            $table->string('no_kendaraan', 30)->nullable();
            $table->json('kategori');                             // array kode kategori (checklist)
            $table->string('kategori_lain', 150)->nullable();
            $table->string('status', 20)->default('DRAFT');       // DRAFT, IN_PROGRESS, COMPLETED, REJECTED
            $table->unsignedTinyInteger('current_step')->default(0);
            $table->text('rejected_reason')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_by');
        });

        Schema::create('trx_surat_jalan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_jalan_id');
            $table->unsignedSmallInteger('line_no');
            $table->string('jenis_barang', 200);
            $table->decimal('qty', 12, 2);
            $table->string('satuan', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index(['surat_jalan_id', 'line_no']);
        });

        Schema::create('trx_surat_jalan_signers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_jalan_id');
            $table->unsignedTinyInteger('step');                  // 1..4 (urutan & posisi kotak)
            $table->string('slot', 20);                           // DISERAHKAN, MENGETAHUI, PEMERIKSA, DITERIMA
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('signer_name', 100);
            $table->string('signer_position', 100)->nullable();
            $table->string('signer_departement', 100)->nullable();
            $table->string('status', 20)->default('WAITING');     // WAITING, PENDING, SIGNED, REJECTED
            $table->string('stamp_type', 20)->nullable();         // IMAGE, QR, PAD
            $table->string('stamp_path', 255)->nullable();        // snapshot stamp (disk local)
            $table->timestamp('signed_at', 3)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('note')->nullable();                     // alasan reject
            $table->timestamps();

            $table->unique(['surat_jalan_id', 'step']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_surat_jalan_signers');
        Schema::dropIfExists('trx_surat_jalan_items');
        Schema::dropIfExists('trx_surat_jalan');
        // document_audit_logs sengaja tidak di-drop: bersifat permanen & dipakai modul lain.
    }
};
