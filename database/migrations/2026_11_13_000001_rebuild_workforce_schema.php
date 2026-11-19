<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rebuilds the attendance, leave and payroll tables created by the
 * 2026_11_11_* migrations.
 *
 * The first iteration stored durations in TIME columns, had no geofence,
 * holiday, ledger, approval or audit structures, and no way to freeze the
 * policy used for a payroll run. This migration drops those tables and
 * creates the schema described in the Payroll & Workforce Attendance BRD.
 *
 * Clients, shifts, employee shifts, integrations, users.client_id and the
 * recruitment tables are kept as they are.
 */
return new class extends Migration
{
    private array $obsolete = [
        'payslips',
        'payroll_calculations',
        'payroll_runs',
        'payroll_periods',
        'employee_salary_structures',
        'payroll_components',
        'leave_applications',
        'leave_policies',
        'leave_balances',
        'leave_types',
        'attendance_corrections',
        'attendance_punches',
        'attendance_records',
        'attendance_policies',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ($this->obsolete as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        /* ───────────────────────── Attendance ───────────────────────── */

        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(10);
            $table->unsignedInteger('max_accuracy_meters')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->timestamps();
            $table->unique(['client_id', 'date']);
            $table->index('date');
        });

        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);

            // Working time
            $table->json('weekly_offs')->nullable();            // ISO weekdays 1 (Mon) .. 7 (Sun)
            $table->unsignedSmallInteger('full_day_minutes')->default(480);
            $table->unsignedSmallInteger('half_day_minutes')->default(240);
            $table->unsignedSmallInteger('min_session_minutes')->default(0);

            // Late / early rules
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->unsignedSmallInteger('early_departure_minutes')->default(0);
            $table->unsignedSmallInteger('max_late_per_month')->default(0);      // 0 = unlimited
            $table->unsignedSmallInteger('lates_per_half_day')->default(0);      // 0 = never deduct
            $table->boolean('deduct_late')->default(false);
            $table->boolean('deduct_early')->default(false);

            // Sources
            $table->boolean('require_portal_punch')->default(true);
            $table->boolean('require_machine_punch')->default(false);
            $table->json('source_priority')->nullable();        // e.g. ["machine","portal"]
            $table->unsignedSmallInteger('source_tolerance_minutes')->default(15);
            $table->boolean('geofence_required')->default(true);

            // Breaks
            $table->unsignedSmallInteger('paid_break_minutes')->default(30);
            $table->unsignedSmallInteger('max_break_minutes')->default(60);
            $table->boolean('deduct_excess_break')->default(true);
            $table->boolean('track_idle_time')->default(false);

            // Overtime
            $table->boolean('overtime_eligible')->default(false);
            $table->unsignedSmallInteger('overtime_min_minutes')->default(30);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.50);
            $table->boolean('overtime_requires_approval')->default(true);

            // Exceptions
            $table->string('missing_punch_treatment')->default('review');       // review | half_day | absent

            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'is_active', 'effective_from']);
        });

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('punched_at');
            $table->string('type');                              // in | out | break_start | break_end
            $table->string('source');                            // portal | machine | agent | manual
            $table->string('device_id')->nullable();
            $table->string('dedupe_hash', 64)->nullable()->unique();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->foreignId('office_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('geo_status')->nullable();            // inside | outside | low_accuracy | unavailable | not_required
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('correction_id')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'attendance_date']);
            $table->index(['user_id', 'punched_at']);
            $table->index(['client_id', 'attendance_date']);
        });

        Schema::create('attendance_activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('active_seconds')->nullable();
            $table->unsignedInteger('idle_seconds')->nullable();
            $table->string('end_reason')->nullable();            // logout | shutdown | sleep | lock | network | inactivity
            $table->string('source')->default('agent');
            $table->string('device_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'attendance_date']);
        });

        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('machine');          // machine | activity
            $table->string('filename')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('unmatched_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained('attendance_policies')->nullOnDelete();
            $table->json('policy_snapshot')->nullable();
            $table->json('shift_snapshot')->nullable();

            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            $table->unsignedSmallInteger('scheduled_minutes')->default(0);
            $table->unsignedSmallInteger('span_minutes')->default(0);
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('session_minutes')->default(0);
            $table->unsignedSmallInteger('active_minutes')->default(0);
            $table->unsignedSmallInteger('idle_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->boolean('overtime_approved')->default(false);

            $table->string('status')->default('pending');        // present | half_day | absent | leave | weekly_off | holiday | incomplete | pending
            $table->decimal('payable_fraction', 3, 2)->default(0);
            $table->boolean('is_late')->default(false);
            $table->boolean('is_early')->default(false);
            $table->foreignId('leave_application_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('leave_is_paid')->nullable();
            $table->json('flags')->nullable();

            $table->string('review_status')->default('ok');      // ok | needs_review | reviewed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->string('status_override')->nullable();
            $table->decimal('override_fraction', 3, 2)->nullable();
            $table->string('override_reason')->nullable();
            $table->foreignId('override_by')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('computed_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->unsignedBigInteger('locked_run_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'attendance_date']);
            $table->index(['client_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index(['review_status']);
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('requested_in')->nullable();
            $table->dateTime('requested_out')->nullable();
            $table->text('reason');
            $table->json('original_snapshot')->nullable();
            $table->string('status')->default('pending');        // pending | approved | rejected | cancelled
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'attendance_date']);
        });

        /* ───────────────────────── Leave ───────────────────────── */

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 12);
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['client_id', 'code']);
        });

        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->string('employment_type')->nullable();       // null = every employment type
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('accrual_frequency')->default('monthly');    // monthly | yearly | upfront | none
            $table->decimal('accrual_amount', 5, 2)->default(0);       // days credited per period
            $table->decimal('annual_cap', 6, 2)->nullable();
            $table->unsignedTinyInteger('credit_day')->default(1);
            $table->unsignedSmallInteger('eligible_after_days')->default(0);
            $table->unsignedSmallInteger('probation_days')->default(0);
            $table->boolean('allow_during_probation')->default(true);

            $table->boolean('allow_half_day')->default(true);
            $table->decimal('max_consecutive_days', 5, 2)->nullable();
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->boolean('allow_negative')->default(false);
            $table->decimal('max_negative_days', 5, 2)->default(0);

            $table->string('carry_forward_mode')->default('none');      // none | capped | all
            $table->decimal('carry_forward_cap', 6, 2)->nullable();
            $table->unsignedTinyInteger('expiry_months')->nullable();
            $table->boolean('allow_encashment')->default(false);

            $table->boolean('count_weekly_offs')->default(false);
            $table->boolean('count_holidays')->default(false);
            $table->json('approval_levels')->nullable();                // ["reporting_manager","hr"]

            $table->timestamps();
            $table->softDeletes();
            $table->index(['leave_type_id', 'is_active', 'effective_from']);
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained('leave_policies')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_session')->nullable();             // first_half | second_half
            $table->decimal('total_days', 6, 2);
            $table->text('reason');
            $table->string('status')->default('pending');               // pending | approved | rejected | cancelled
            $table->unsignedTinyInteger('current_level')->default(1);
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('approver_role');                            // reporting_manager | hr
            $table->string('status')->default('pending');               // pending | approved | rejected
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acted_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['leave_application_id', 'level']);
        });

        Schema::create('leave_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->string('entry_type');       // opening | accrual | carry_forward | debit | reversal | adjustment | lapse | encashment
            $table->decimal('days', 7, 2);      // signed: credits positive, debits negative
            $table->date('effective_date');
            $table->foreignId('leave_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period_key')->nullable();                   // e.g. 2026-11 — makes accrual idempotent
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'leave_type_id', 'effective_date']);
            $table->unique(['user_id', 'leave_type_id', 'entry_type', 'period_key'], 'leave_ledger_period_unique');
        });

        /* ───────────────────────── Payroll ───────────────────────── */

        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('effective_from');
            $table->string('calculation_method')->default('monthly_fixed');   // monthly_fixed | hourly
            $table->string('divisor_mode')->default('calendar_days');         // calendar_days | fixed
            $table->unsignedTinyInteger('fixed_divisor')->default(30);
            $table->string('rounding')->default('nearest');                   // nearest | up | down
            $table->unsignedTinyInteger('precision')->default(0);
            $table->string('overtime_base')->default('gross');                // basic | gross
            $table->boolean('block_on_pending_attendance')->default(true);
            $table->boolean('block_on_pending_leave')->default(true);
            $table->boolean('separate_reviewer')->default(false);

            $table->boolean('pf_enabled')->default(true);
            $table->decimal('pf_employee_rate', 5, 2)->default(12);
            $table->decimal('pf_employer_rate', 5, 2)->default(12);
            $table->decimal('pf_wage_ceiling', 12, 2)->nullable();            // null = no ceiling
            $table->boolean('esic_enabled')->default(true);
            $table->decimal('esic_employee_rate', 5, 3)->default(0.75);
            $table->decimal('esic_employer_rate', 5, 3)->default(3.25);
            $table->decimal('esic_gross_ceiling', 12, 2)->default(21000);

            $table->timestamps();
            $table->index(['client_id', 'effective_from']);
        });

        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_offer_id')->nullable()->constrained('candidate_offers')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'effective_from']);
        });

        Schema::create('salary_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('employee_salary_structures')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->string('type');                              // earning | deduction | employer_contribution
            $table->string('calc_type')->default('fixed');       // fixed | percent_of_basic | percent_of_gross
            $table->decimal('amount', 12, 2)->default(0);        // monthly amount for fixed items
            $table->decimal('percentage', 7, 4)->nullable();
            $table->string('statutory_code')->nullable();        // pf_employee | pf_employer | esic_employee | esic_employer
            $table->boolean('prorate')->default(true);
            $table->boolean('is_basic')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('attendance_cutoff_date');
            $table->date('salary_processing_date');
            $table->string('status')->default('open');           // open | closed
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'start_date', 'end_date']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->string('run_number')->unique();
            $table->string('status')->default('draft');
            $table->unsignedSmallInteger('version')->default(0);
            $table->unsignedInteger('employee_count')->default(0);
            $table->unsignedInteger('exception_count')->default(0);
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->decimal('total_employer_cost', 15, 2)->default(0);
            $table->json('settings_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('calculated_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'status']);
        });

        Schema::create('payroll_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('structure_id')->nullable()->constrained('employee_salary_structures')->nullOnDelete();

            $table->unsignedSmallInteger('calendar_days')->default(0);
            $table->decimal('payable_days', 6, 2)->default(0);
            $table->decimal('lop_days', 6, 2)->default(0);
            $table->decimal('present_days', 6, 2)->default(0);
            $table->decimal('absent_days', 6, 2)->default(0);
            $table->decimal('paid_leave_days', 6, 2)->default(0);
            $table->decimal('unpaid_leave_days', 6, 2)->default(0);
            $table->decimal('weekly_off_days', 6, 2)->default(0);
            $table->decimal('holiday_days', 6, 2)->default(0);
            $table->unsignedSmallInteger('late_count')->default(0);
            $table->unsignedSmallInteger('early_count')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->decimal('gross_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->decimal('employer_contributions', 15, 2)->default(0);
            $table->json('earnings')->nullable();
            $table->json('deductions')->nullable();
            $table->json('employer')->nullable();
            $table->json('attendance_summary')->nullable();
            $table->json('exceptions')->nullable();
            $table->string('status')->default('calculated');     // calculated | held
            $table->string('hold_reason')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'user_id']);
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');                              // incentive | bonus | commission | reimbursement | allowance | arrears | deduction
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_deduction')->default(false);
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['payroll_run_id', 'user_id']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payslip_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->json('snapshot');
            $table->string('pdf_path')->nullable();
            $table->dateTime('generated_at');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'payslips', 'payroll_adjustments', 'payroll_run_items', 'payroll_runs', 'payroll_periods',
            'salary_structure_items', 'employee_salary_structures', 'payroll_settings',
            'leave_ledger', 'leave_approvals', 'leave_applications', 'leave_policies', 'leave_types',
            'attendance_corrections', 'attendance_records', 'attendance_import_batches',
            'attendance_activity_sessions', 'attendance_punches', 'attendance_policies',
            'holidays', 'office_locations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
};
