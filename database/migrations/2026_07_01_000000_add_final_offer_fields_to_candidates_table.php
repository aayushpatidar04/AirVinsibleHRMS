<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->decimal('final_ctc_offered', 10, 2)->nullable()->after('final_salary_offered');
            $table->decimal('final_in_hand_offered', 10, 2)->nullable()->after('final_ctc_offered');
            $table->boolean('final_pf_allowed')->default(false)->after('final_in_hand_offered');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['final_ctc_offered', 'final_in_hand_offered', 'final_pf_allowed']);
        });
    }
};
