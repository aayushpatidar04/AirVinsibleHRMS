<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_round_progress', function (Blueprint $table) {
            $table->enum('assignment_status', ['pending', 'accepted', 'rejected'])
                ->default('pending')
                ->after('interviewer_id');
            $table->timestamp('assignment_responded_at')->nullable()
                ->after('assignment_status');
        });

        DB::table('candidate_round_progress')->update([
            'assignment_status' => 'accepted',
        ]);
    }

    public function down(): void
    {
        Schema::table('candidate_round_progress', function (Blueprint $table) {
            $table->dropColumn(['assignment_status', 'assignment_responded_at']);
        });
    }
};
