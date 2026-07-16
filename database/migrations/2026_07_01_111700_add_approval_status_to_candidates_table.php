<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->enum('applicant_type', ['Fresher', 'Experienced', 'Rejoining'])->nullable()->after('final_status');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable()->after('final_status');
            $table->foreignId('old_employee_id')->nullable()->constrained('users')->nullOnDelete()->after('approval_status');
            $table->text('approval_notes')->nullable()->after('old_employee_id');
            $table->timestamp('approved_at')->nullable()->after('approval_notes');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['old_employee_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'applicant_type',
                'approval_status',
                'old_employee_id',
                'approval_notes',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};