<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_kalibrasi', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 100)->unique()->comment('No. Sertifikat Kalibrasi');
            $table->foreignId('alat_ukur_id')->constrained('m_alat_ukur')->onDelete('cascade');
            $table->enum('calibration_type', ['INTERNAL', 'EKSTERNAL'])->comment('Tipe kalibrasi');
            $table->date('calibration_date')->comment('Tanggal pelaksanaan kalibrasi');
            $table->date('next_due_date')->comment('Tanggal jatuh tempo berikutnya');
            $table->enum('conclusion', ['PASS', 'PASS_WITH_NOTE', 'FAIL'])->default('PASS')->comment('Kesimpulan hasil kalibrasi');
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade')->comment('Teknisi / Karyawan penginput');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null')->comment('Manager/Atasan yang menyetujui');
            $table->foreignId('vendor_id')->nullable()->constrained('m_vendor')->onDelete('set null')->comment('Vendor kalibrasi eksternal');
            $table->string('vendor_name', 200)->nullable()->comment('Nama vendor kalibrasi');
            $table->decimal('temperature', 5, 2)->nullable()->default(20.0)->comment('Kondisi suhu ruangan (°C)');
            $table->decimal('humidity', 5, 2)->nullable()->default(55.0)->comment('Kondisi kelembaban ruangan (% RH)');
            $table->string('calibrator_used', 150)->nullable()->comment('Alat standar kalibrator yang digunakan');
            $table->string('raw_attachment_pdf', 255)->nullable()->comment('PDF lembar ukur manual teknisi / sertifikat vendor');
            $table->string('final_merged_pdf', 255)->nullable()->comment('File PDF utuh final');
            $table->enum('status', [
                'DRAFT',
                'PENDING_APPROVAL',
                'COMPLETED',
                'REJECTED'
            ])->default('PENDING_APPROVAL')->comment('Status proses kalibrasi');
            $table->text('rejection_note')->nullable()->comment('Alasan penolakan dari atasan');
            $table->timestamp('approved_at')->nullable()->comment('Waktu persetujuan atasan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('calibration_type');
            $table->index('calibration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_kalibrasi');
    }
};
