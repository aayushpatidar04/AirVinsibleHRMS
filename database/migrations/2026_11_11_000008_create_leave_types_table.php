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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name'); // Casual Leave, Sick Leave, Earned Leave, Unpaid Leave
            $table->string('code')->unique(); // CL, SL, EL, UPL
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('allow_half_day')->default(false);
            $table->boolean('allow_during_probation')->default(true);
            $table->integer('annual_entitlement')->default(0);
            $table->integer('monthly_entitlement')->default(0);
            $table->string('accrual_frequency')->default('monthly'); // monthly, yearly, joining
            $table->date('credit_date')->nullable(); // Day of month when leave is credited
            $table->integer('max_consecutive_days')->default(3);
            $table->integer('notice_period_days')->default(1);
            $table->boolean('allow_carry_forward')->default(false);
            $table->integer('max_carry_forward_days')->default(0);
            $table->boolean('allow_encashment')->default(false);
            $table->boolean('allow_negative_balance')->default(false);
            $table->integer('expiry_days')->nullable(); // Days after which unused leave lapses
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
