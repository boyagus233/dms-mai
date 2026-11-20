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
        Schema::create('trx_document_upload', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('departement');
            $table->string('document_type');
            $table->string('document_classification');
            $table->string('document_function');
            $table->string('document_no',50);
            $table->string('document_name',100);
            $table->bigInteger('assign_to');
            $table->bigInteger('assign_to_2')->nullable();
            $table->string('document_status',20);
            $table->integer('rev_no')->default(0);
            $table->string('reason_revisi',255);
            $table->boolean('is_checked_by_dc')->default(false);
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('created_by')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_document_upload');
    }
};
