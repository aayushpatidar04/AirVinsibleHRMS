<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_intent_letters', function (Blueprint $table) {
            $table->string('rejection_name')->nullable()->after('candidate_consent');
            $table->text('rejection_remark')->nullable()->after('rejection_name');
            $table->timestamp('rejected_at')->nullable()->after('rejection_remark');
            $table->string('rejection_ip', 45)->nullable()->after('rejected_at');
            $table->text('rejection_user_agent')->nullable()->after('rejection_ip');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_intent_letters', function (Blueprint $table) {
            $table->dropColumn([
                'rejection_name',
                'rejection_remark',
                'rejected_at',
                'rejection_ip',
                'rejection_user_agent',
            ]);
        });
    }
};
