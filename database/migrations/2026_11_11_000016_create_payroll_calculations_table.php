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
        Schema::create('payroll_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('payable_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('paid_leave_days')->default(0);
            $table->integer('unpaid_leave_days')->default(0);
            $table->integer('weekly_off_days')->default(0);
            $table->integer('holiday_days')->default(0);
            $table->integer('late_arrivals')->default(0);
            $table->integer('early_departures')->default(0);
            $table->decimal('total_working_hours', 10, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('gross_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->json('earning_breakdown')->nullable(); // Component-wise amounts
            $table->json('deduction_breakdown')->nullable(); // Component-wise amounts
            $table->json('adjustments')->nullable(); // Manual adjustments
            $table->string('status')->default('calculated'); // calculated, adjusted, approved
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'user_id']);
            $table->index(['payroll_run_id', 'status']);
            $table->index(['user_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_calculations');
    }
};
