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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->date('attendance_date');
            $table->time('scheduled_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->time('first_punch_in')->nullable();
            $table->time('last_punch_out')->nullable();
            $table->time('total_working_hours')->nullable(); // stored as hours.decimal
            $table->time('system_session_hours')->nullable();
            $table->time('break_duration')->nullable();
            $table->time('overtime_hours')->nullable();
            $table->string('status')->default('pending'); // present, absent, late, half_day, weekly_off, holiday, leave
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->boolean('is_early_departure')->default(false);
            $table->integer('early_departure_minutes')->default(0);
            $table->boolean('missing_punch')->default(false);
            $table->text('punch_source')->nullable(); // portal, machine, manual
            $table->json('geolocation')->nullable(); // latitude, longitude, accuracy
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->index(['user_id', 'attendance_date']);
            $table->index(['client_id', 'attendance_date']);
            $table->index('status');
            $table->index('attendance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
