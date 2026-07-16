<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('candidate_round_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('round_id')->constrained('interview_rounds')->restrictOnDelete();
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending','in_progress','completed','rejected'])->default('pending');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('overall_feedback')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable(); // 0.00 - 5.00
            $table->text('rejection_reason')->nullable();
            // Salary Offer (populated during HR round)
            $table->decimal('salary_offer_amount', 10, 2)->nullable();
            $table->string('offered_designation')->nullable();
            $table->enum('salary_offer_status', ['pending','accepted','negotiating','declined'])->nullable();
            // Next round assignment (filled before closing round)
            $table->foreignId('next_round_id')->nullable()->constrained('interview_rounds')->nullOnDelete();
            $table->foreignId('next_interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['candidate_id', 'round_id']);
            $table->index(['interviewer_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('candidate_round_progress'); }
};