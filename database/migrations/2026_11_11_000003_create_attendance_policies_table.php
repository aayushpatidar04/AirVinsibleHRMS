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
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->time('shift_start_time');
            $table->time('shift_end_time');
            $table->time('grace_period_start')->nullable();
            $table->time('grace_period_end')->nullable();
            $table->integer('grace_period_minutes')->default(0);
            $table->integer('max_late_arrivals')->default(0);
            $table->boolean('allow_early_departure')->default(false);
            $table->integer('min_required_hours')->default(8);
            $table->integer('min_session_duration_minutes')->default(0);
            $table->boolean('mandatory_portal_punch')->default(true);
            $table->boolean('mandatory_machine_punch')->default(false);
            $table->integer('break_duration_minutes')->default(30);
            $table->integer('max_break_minutes')->default(30);
            $table->boolean('track_idle_time')->default(false);
            $table->boolean('overtime_eligible')->default(false);
            $table->decimal('overtime_multiplier', 5, 2)->default(1.50);
            $table->json('weekly_offs')->nullable(); // e.g., [1, 7] for Sunday and Saturday
            $table->json('public_holidays')->nullable(); // e.g., ["2026-01-26", "2026-08-15"]
            $table->decimal('half_day_threshold_hours', 4, 2)->default(4.00);
            $table->decimal('full_day_threshold_hours', 4, 2)->default(8.00);
            $table->boolean('allow_negative_leave_balance')->default(false);
            $table->boolean('deduct_late_arrival')->default(false);
            $table->boolean('deduct_early_departure')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'is_active']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
