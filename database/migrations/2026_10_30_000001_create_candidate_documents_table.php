<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('document_requirement_id')->constrained('document_requirements')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['candidate_id', 'document_requirement_id'], 'candidate_doc_unique');
            $table->index(['candidate_id', 'is_verified']);
            $table->foreign('verified_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreign('uploaded_by')->nullable()->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('candidate_documents');
        Schema::enableForeignKeyConstraints();
    }
};
