<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Selected
            $table->string('process_name')->nullable()->after('final_designation');
            
            // Not Selected
            $table->string('rejection_reason')->nullable()->after('process_name');
            $table->text('rejection_remarks')->nullable()->after('rejection_reason');
            
            // Hold / Pending
            $table->string('hold_reason')->nullable()->after('rejection_remarks');
            $table->text('hold_remarks')->nullable()->after('hold_reason');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'process_name',
                'rejection_reason',
                'rejection_remarks',
                'hold_reason',
                'hold_remarks',
            ]);
        });
    }
};