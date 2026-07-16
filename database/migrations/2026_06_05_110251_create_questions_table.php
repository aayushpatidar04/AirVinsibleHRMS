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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('interview_rounds')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['short_answer','long_answer','rating_scale','yes_no','multiple_choice'])->default('short_answer');
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->integer('order')->default(0);
            $table->json('options')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['round_id', 'is_mandatory', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};