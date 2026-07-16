<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_round_progress', function (Blueprint $table) {
            $table->decimal('salary_offer_min', 10, 2)->nullable()->after('salary_offer_amount');
            $table->decimal('salary_offer_max', 10, 2)->nullable()->after('salary_offer_min');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_round_progress', function (Blueprint $table) {
            $table->dropColumn(['salary_offer_min', 'salary_offer_max']);
        });
    }
};
