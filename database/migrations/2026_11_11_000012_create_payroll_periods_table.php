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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name'); // e.g., "November 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('attendance_cutoff_date');
            $table->date('salary_processing_date');
            $table->string('status')->default('open'); // open, closed, locked
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'start_date', 'end_date']);
            $table->index(['client_id', 'status']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
