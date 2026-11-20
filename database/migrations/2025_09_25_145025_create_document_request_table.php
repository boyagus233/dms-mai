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
        Schema::create('trx_document_request', function (Blueprint $table) {
            $table->id();
            $table->integer('document_type');
            $table->string('document_type_code');
            $table->integer('document_classification');
            $table->string('document_classification_code');
            $table->integer('document_function')->nullable();
            $table->string('document_function_code')->nullable();
            $table->string('document_form',10);
            $table->string('document_parent_no',20);
            $table->string('document_no',30);
            $table->string('document_name',100);
            $table->string('document_status',15);
            $table->string('notes',255);
            $table->boolean('is_used')->default(false);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_document_request');
    }
};
