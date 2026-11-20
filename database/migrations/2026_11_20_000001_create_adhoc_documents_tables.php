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
        // 1. Table documents
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('original_file_name', 255);
            $table->string('original_file_path', 255);
            $table->string('signed_file_path', 255)->nullable();
            $table->string('original_file_hash', 64)->nullable(); // SHA-256
            $table->string('signed_file_hash', 64)->nullable();   // SHA-256
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('page_count')->default(1);
            $table->enum('workflow_type', ['SEQUENTIAL', 'PARALLEL'])->default('SEQUENTIAL');
            $table->enum('status', ['DRAFT', 'IN_PROGRESS', 'COMPLETED', 'REJECTED'])->default('DRAFT');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. Table document_signers
        Schema::create('document_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('order_index')->default(1);
            $table->string('role_name', 100)->nullable();
            $table->enum('status', ['WAITING', 'PENDING', 'SIGNED', 'REJECTED'])->default('WAITING');
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->string('signature_path', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'order_index']);
        });

        // 3. Table document_signature_fields
        Schema::create('document_signature_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('document_signer_id')->constrained('document_signers')->onDelete('cascade');
            $table->enum('field_type', ['SIGNATURE', 'INITIAL_PARAF', 'SIGN_DATE', 'NAME_TITLE']);
            $table->unsignedInteger('page_number')->default(1);
            $table->decimal('x_percent', 8, 4);
            $table->decimal('y_percent', 8, 4);
            $table->decimal('width_percent', 8, 4);
            $table->decimal('height_percent', 8, 4);
            $table->timestamps();

            $table->index(['document_id', 'page_number']);
        });

        // 4. Table document_audit_logs
        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('actor_name', 150);
            $table->string('actor_email', 150)->nullable();
            $table->string('action', 50); // UPLOADED, INVITATION_SENT, DOCUMENT_VIEWED, SIGNED, REJECTED, COMPLETED
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_audit_logs');
        Schema::dropIfExists('document_signature_fields');
        Schema::dropIfExists('document_signers');
        Schema::dropIfExists('documents');
    }
};
