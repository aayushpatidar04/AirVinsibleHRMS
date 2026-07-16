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
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->foreignId('progress_id')->constrained('candidate_round_progress')->cascadeOnDelete();
            $table->longText('response_text')->nullable();
            $table->integer('rating_value')->nullable();
            $table->boolean('yes_no_value')->nullable();
            $table->json('selected_options')->nullable();
            $table->text('interviewer_notes')->nullable();
            $table->decimal('question_rating', 3, 2)->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'question_id', 'progress_id']);
            $table->index('progress_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};