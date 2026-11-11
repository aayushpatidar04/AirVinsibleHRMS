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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->integer('break_duration_minutes')->default(30);
            $table->integer('grace_period_minutes')->default(0);
            $table->boolean('is_rotational')->default(false);
            $table->boolean('is_overnight')->default(false);
            $table->json('working_days')->nullable(); // e.g., [1,2,3,4,5] for Mon-Fri
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
        Schema::dropIfExists('shifts');
    }
};
