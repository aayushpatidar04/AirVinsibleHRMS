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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->string('employee_category')->nullable(); // Full-time, Part-time, Contract, Intern
            $table->integer('annual_entitlement')->default(0);
            $table->integer('monthly_entitlement')->default(0);
            $table->string('accrual_frequency')->default('monthly');
            $table->date('credit_date')->nullable();
            $table->integer('eligibility_service_days')->default(0); // Days of service before eligible
            $table->boolean('allow_carry_forward')->default(false);
            $table->integer('max_carry_forward_days')->default(0);
            $table->boolean('allow_encashment')->default(false);
            $table->integer('max_consecutive_days')->default(3);
            $table->integer('notice_period_days')->default(1);
            $table->boolean('allow_half_day')->default(false);
            $table->boolean('allow_during_probation')->default(true);
            $table->boolean('allow_negative_balance')->default(false);
            $table->integer('expiry_days')->nullable();
            $table->string('approval_hierarchy')->default('manager'); // manager, hr, both
            $table->boolean('count_weekly_offs')->default(false);
            $table->boolean('count_public_holidays')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'leave_type_id', 'is_active']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
