<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'attendance_punches',
            'leave_balances',
            'leave_applications',
            'payroll_periods',
            'payroll_runs',
            'payroll_calculations',
            'payslips',
            'attendance_corrections',
        ];

        foreach ($tables as $tbl) {
            Schema::table($tbl, function (Blueprint $t) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'deleted_at')) {
                    $t->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'attendance_punches',
            'leave_balances',
            'leave_applications',
            'payroll_periods',
            'payroll_runs',
            'payroll_calculations',
            'payslips',
            'attendance_corrections',
        ];

        foreach ($tables as $tbl) {
            Schema::table($tbl, function (Blueprint $t) use ($tbl) {
                $t->dropSoftDeletes();
            });
        }
    }
};
