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
        Schema::create('m_alat_ukur', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique()->comment('No. Inventaris / Kode Alat');
            $table->string('name', 150)->comment('Nama Alat Ukur');
            $table->enum('category_type', ['KI', 'KE'])->default('KI')->comment('Tipe: KI (Internal) / KE (Eksternal)');
            $table->string('standard_spec', 100)->nullable()->comment('Spesifikasi / Rentang Ukur');
            $table->string('brand_model', 120)->nullable()->comment('Merk / Model');
            $table->string('serial_number', 100)->nullable()->comment('Nomor Seri Alat');
            $table->string('calibrator_ref', 150)->nullable()->comment('Standar / Alat Kalibrator');
            $table->string('location', 100)->nullable()->default('QA')->comment('Area / Lab / Lokasi Penggunaan');
            $table->unsignedTinyInteger('calibration_interval_months')->default(12)->comment('Interval kalibrasi dalam bulan (default 12)');
            $table->date('last_calibration_date')->nullable()->comment('Tanggal kalibrasi terakhir');
            $table->date('next_calibration_date')->comment('Tanggal jatuh tempo kalibrasi berikutnya');
            $table->enum('current_status', [
                'ACTIVE',
                'PENDING_APPROVAL',
                'OUT_OF_PREMISE',
                'BROKEN',
                'RETIRED'
            ])->default('ACTIVE')->comment('Status operasional alat');
            $table->string('photo_path', 255)->nullable()->comment('Path foto alat ukur');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('next_calibration_date');
            $table->index('current_status');
            $table->index('category_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_alat_ukur');
    }
};
