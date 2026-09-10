<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'candidate_round_custom_questions',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Interview ownership
                |--------------------------------------------------------------------------
                */

                $table->foreignId('progress_id')
                    ->constrained(
                        'candidate_round_progress'
                    )
                    ->cascadeOnDelete();

                $table->foreignId('candidate_id')
                    ->constrained('candidates')
                    ->cascadeOnDelete();

                $table->foreignId('interviewer_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Question details
                |--------------------------------------------------------------------------
                */

                $table->text('question_text');

                $table->string('question_type', 50)
                    ->default('short_answer');

                $table->boolean('is_mandatory')
                    ->default(false);

                $table->json('options')
                    ->nullable();

                $table->unsignedInteger('order')
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Candidate response
                |--------------------------------------------------------------------------
                */

                $table->longText('response_text')
                    ->nullable();

                $table->unsignedTinyInteger('rating_value')
                    ->nullable();

                $table->boolean('yes_no_value')
                    ->nullable();

                $table->json('selected_options')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Interviewer evaluation
                |--------------------------------------------------------------------------
                */

                $table->text('interviewer_notes')
                    ->nullable();

                $table->decimal(
                    'question_rating',
                    3,
                    2
                )->nullable();

                $table->timestamp('answered_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'progress_id',
                    'interviewer_id',
                ]);

                $table->index([
                    'candidate_id',
                    'progress_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'candidate_round_custom_questions'
        );
    }
};