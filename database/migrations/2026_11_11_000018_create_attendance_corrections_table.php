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
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Employee whose attendance is being corrected
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->onDelete('set null');
            $table->date('correction_date');
            $table->time('original_punch_in')->nullable();
            $table->time('original_punch_out')->nullable();
            $table->time('corrected_punch_in')->nullable();
            $table->time('corrected_punch_out')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index('correction_date');
            $table->index('requested_by');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
