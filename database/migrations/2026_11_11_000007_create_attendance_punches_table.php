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
        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->timestamp('punch_time');
            $table->string('punch_type'); // in, out, break_start, break_end
            $table->string('source'); // portal, machine, manual, desktop_agent
            $table->string('device_id')->nullable();
            $table->json('geolocation')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_manual_correction')->default(false);
            $table->foreignId('corrected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'punch_time']);
            $table->index(['client_id', 'punch_time']);
            $table->index('source');
            $table->index('punch_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_punches');
    }
};
