<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_intent_letters', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('reporting_to')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_intent_letters', function (Blueprint $table) {
            $table->dropColumn(['department', 'reporting_to']);
        });
    }
};
