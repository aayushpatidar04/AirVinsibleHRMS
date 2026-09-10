<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('score_category')
                ->nullable()
                ->after('question_type');

            $table->index('score_category');
        });

        Schema::table(
            'candidate_round_custom_questions',
            function (Blueprint $table) {
                $table->string('score_category')
                    ->nullable()
                    ->after('question_type');

                $table->index('score_category');
            }
        );
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['score_category']);
            $table->dropColumn('score_category');
        });

        Schema::table(
            'candidate_round_custom_questions',
            function (Blueprint $table) {
                $table->dropIndex(['score_category']);
                $table->dropColumn('score_category');
            }
        );
    }
};