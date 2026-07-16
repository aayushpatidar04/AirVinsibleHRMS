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
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('registration_forms')->restrictOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->json('submission_data');
            $table->string('resume_path')->nullable();
            $table->string('resume_original_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_form_submissions');
    }
};