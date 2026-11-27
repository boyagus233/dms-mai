<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_alat_ukur', function (Blueprint $table) {
            $table->date('next_calibration_date')->nullable()->comment('Tanggal jatuh tempo kalibrasi berikutnya')->change();
        });
    }

    public function down(): void
    {
        Schema::table('m_alat_ukur', function (Blueprint $table) {
            $table->date('next_calibration_date')->nullable(false)->comment('Tanggal jatuh tempo kalibrasi berikutnya')->change();
        });
    }
};
