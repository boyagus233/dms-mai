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
        Schema::create('tr_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('document_id');
            $table->string('document_no',30);
            $table->string('document_name',30);
            $table->string('action',50);
            $table->bigInteger('employee_id');
            $table->string('employee_name',50);
            $table->integer('departement_id');
            $table->string('departement_name',50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_audit_trails');
    }
};
