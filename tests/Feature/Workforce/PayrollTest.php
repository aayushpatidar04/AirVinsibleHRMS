<?php

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\SalaryStructureItem;
use App\Models\User;
use App\Services\Workforce\PayrollEngine;
use App\Services\Workforce\PayrollException;
use App\Services\Workforce\PayrollRunService;
use App\Services\Workforce\PayslipService;
use Carbon\Carbon;
use Tests\Feature\Workforce\BuildsWorkforce;

uses(BuildsWorkforce::class);

beforeEach(function () {
    $this->seedRoles();
    Carbon::setTestNow('2026-04-05 10:00');

    $this->client = $this->client();
    $this->shift = $this->shift($this->client);
    $this->policy = $this->policy($this->client);
    $this->emp = $this->employee($this->client, [], $this->shift);

    $this->period = PayrollPeriod::create([
        'client_id' => $this->client->id, 'name' => 'March 2026',
        'start_date' => '2026-03-01', 'end_date' => '2026-03-31',
        'attendance_cutoff_date' => '2026-04-02', 'salary_processing_date' => '2026-04-10',
    ]);

    $this->settings = PayrollSetting::create(['client_id' => $this->client->id, 'effective_from' => '2026-01-01'])->fresh();
    $this->engine = app(PayrollEngine::class);
});

afterEach(fn () => Carbon::setTestNow());

/** The salary structure from the sample offer: basic 6,500 + HRA 6,500, PF 12 %, ESIC 0.75 % / 3.25 %. */
function structure($test, $user = null, string $from = '2026-01-01', array $extra = []): EmployeeSalaryStructure
{
    $user ??= $test->emp;
    $s = EmployeeSalaryStructure::create(array_merge(['user_id' => $user->id, 'client_id' => $user->client_id, 'effective_from' => $from], $extra));

    $items = [
        ['Basic', 'BASIC', 'earning', 'fixed', 6500, null, null, true],
        ['HRA', 'HRA', 'earning', 'fixed', 6500, null, null, false],
        ['PF employee Contribution', 'PF_EE', 'deduction', 'percent_of_basic', 0, 12, 'pf_employee', false],
        ['ESIC employee Contribution', 'ESIC_EE', 'deduction', 'percent_of_basic', 0, 0.75, 'esic_employee', false],
        ['PF Company Contribution', 'PF_ER', 'employer_contribution', 'percent_of_basic', 0, 12, 'pf_employer', false],
        ['ESIC Company Contribution', 'ESIC_ER', 'employer_contribution', 'percent_of_basic', 0, 3.25, 'esic_employer', false],
    ];

    foreach ($items as $i => [$name, $code, $type, $calc, $amount, $pct, $stat, $basic]) {
        SalaryStructureItem::create([
            'structure_id' => $s->id, 'name' => $name, 'code' => $code, 'type' => $type, 'calc_type' => $calc,
            'amount' => $amount, 'percentage' => $pct, 'statutory_code' => $stat, 'prorate' => true, 'is_basic' => $basic, 'sort_order' => $i,
        ]);
    }

    return $s;
}

/**
 * Punches Mon–Sat of March 2026 for a user (09:00–18:00), skipping or
 * altering chosen dates. Sundays are weekly offs.
 */
function workMarch($test, $user = null, array $skip = [], array $late = [], array $overtime = [], string $from = '2026-03-01'): void
{
    $user ??= $test->emp;

    for ($day = Carbon::parse($from); $day->lte(Carbon::parse('2026-03-31')); $day->addDay()) {
        $date = $day->toDateString();

        if ($day->isSunday() || in_array($date, $skip, true)) {
            continue;
        }

        $in = in_array($date, $late, true) ? '09:30' : '09:00';
        $out = in_array($date, $overtime, true) ? '19:30' : '18:00';

        foreach ([[$in, 'in'], [$out, 'out']] as [$time, $type]) {
            AttendancePunch::create([
                'user_id' => $user->id, 'client_id' => $user->client_id, 'attendance_date' => $date,
                'punched_at' => Carbon::parse("$date $time"), 'type' => $type, 'source' => 'portal', 'geo_status' => 'not_required',
            ]);
        }
    }
}

function item($test, $user = null, $adjustments = null): array
{
    return $test->engine->compute($user ?? $test->emp, $test->period, $test->settings, $adjustments);
}

function lineAmount(array $lines, string $code): float
{
    return (float) collect($lines)->firstWhere('code', $code)['amount'];
}

/* ───────────────────────── calculation ───────────────────────── */

test('a full month reproduces the sample offer figures', function () {
    structure($this);
    workMarch($this);

    $r = item($this);

    expect($r['calendar_days'])->toBe(31)->and($r['lop_days'])->toBe(0.0)->and($r['payable_days'])->toBe(31.0)
        ->and($r['present_days'])->toBe(26.0)->and($r['weekly_off_days'])->toBe(5.0)
        ->and($r['gross_earnings'])->toBe(13000.0)
        ->and(lineAmount($r['deductions'], 'PF_EE'))->toBe(780.0)
        ->and(lineAmount($r['deductions'], 'ESIC_EE'))->toBe(49.0)
        ->and($r['net_pay'])->toBe(12171.0)
        ->and($r['employer_contributions'])->toBe(991.0)
        ->and(collect($r['exceptions'])->where('blocking', true))->toHaveCount(0);
});

test('absent days reduce pay by loss of pay and statutory items follow earned wages', function () {
    structure($this);
    workMarch($this, null, ['2026-03-07', '2026-03-14']); // two Saturdays absent

    $r = item($this);

    expect($r['absent_days'])->toBe(2.0)->and($r['lop_days'])->toBe(2.0)->and($r['payable_days'])->toBe(29.0)
        ->and(lineAmount($r['earnings'], 'BASIC'))->toBe(6081.0)   // 6500 - 6500/31*2
        ->and($r['gross_earnings'])->toBe(12162.0)
        ->and(lineAmount($r['deductions'], 'PF_EE'))->toBe(730.0)  // 12 % of earned basic
        ->and($r['net_pay'])->toBe(11386.0);
});

test('a fixed divisor changes the per-day rate', function () {
    structure($this);
    workMarch($this, null, ['2026-03-07']);
    $this->settings->update(['divisor_mode' => 'fixed', 'fixed_divisor' => 26]);

    $r = item($this);

    expect(lineAmount($r['earnings'], 'BASIC'))->toBe(6250.0); // 6500 - 6500/26
});

test('someone who joins mid month is paid only from the joining date', function () {
    $new = $this->employee($this->client, ['date_of_joining' => '2026-03-16'], $this->shift);
    structure($this, $new, '2026-03-16');
    workMarch($this, $new, [], [], [], '2026-03-16');

    $r = item($this, $new);

    expect($r['lop_days'])->toBe(15.0)->and($r['gross_earnings'])->toBe(6710.0);
    expect(collect($r['exceptions'])->pluck('code'))->toContain('joined_mid_period');
});

test('a salary revision part way through the month is paid pro rata', function () {
    $s = structure($this, null, '2026-01-01');
    $s->update(['effective_to' => '2026-03-15']);
    $new = structure($this, null, '2026-03-16');
    $new->items()->where('code', 'BASIC')->update(['amount' => 9000]);
    workMarch($this);

    $r = item($this);

    // basic: 15 days at 6500 + 16 days at 9000 -> weighted (6500*15 + 9000*16)/31
    expect(lineAmount($r['earnings'], 'BASIC'))->toBe(round((6500 * 15 + 9000 * 16) / 31));
});

test('late penalties convert to half days after the free allowance', function () {
    $this->policy->update(['deduct_late' => true, 'lates_per_half_day' => 3, 'max_late_per_month' => 1]);
    structure($this);
    workMarch($this, null, [], ['2026-03-02', '2026-03-03', '2026-03-04', '2026-03-05', '2026-03-06']); // 5 lates

    $r = item($this);

    // 5 lates - 1 free = 4 chargeable -> 1 full block of 3 -> half a day
    expect($r['late_count'])->toBe(5)->and($r['attendance_summary']['late_penalty_days'])->toBe(0.5)->and($r['lop_days'])->toBe(0.5);
});

test('approved overtime is paid at the policy multiplier', function () {
    // 9 hour shift, so a 9 hour day is a full day and only the 19:30 exit earns overtime
    $this->policy->update(['overtime_eligible' => true, 'overtime_requires_approval' => false, 'overtime_multiplier' => 1.5, 'full_day_minutes' => 540, 'half_day_minutes' => 270]);
    structure($this);
    workMarch($this, null, [], [], ['2026-03-03']);

    $r = item($this);

    // span 09:00-19:30 = 630 min, full day 540 -> 90 min overtime; hourly = 13000 / (31 * 9)
    $expected = round((13000 / (31 * 9)) * 1.5 * 1.5);
    expect($r['overtime_minutes'])->toBe(90)->and(lineAmount($r['earnings'], 'OT'))->toBe((float) $expected);
});

test('overtime that needs approval is not paid until approved', function () {
    $this->policy->update(['overtime_eligible' => true, 'overtime_requires_approval' => true]);
    structure($this);
    workMarch($this, null, [], [], ['2026-03-03']);

    expect(item($this)['overtime_minutes'])->toBe(0);

    AttendanceRecord::whereDate('attendance_date', '2026-03-03')->update(['overtime_approved' => true, 'overtime_minutes' => 150]);
    expect(item($this)['overtime_minutes'])->toBe(150);
});

test('adjustments add to earnings or deductions with their reason', function () {
    structure($this);
    workMarch($this);

    $hr = $this->employee($this->client, ['client_id' => null], null, 'hr_recruiter');
    $run = app(PayrollRunService::class)->create($this->period, $hr);
    app(PayrollRunService::class)->calculate($run, $hr);

    app(PayrollRunService::class)->addAdjustment($run, $this->emp, ['type' => 'incentive', 'label' => 'March incentive', 'amount' => 1000, 'reason' => 'Target met'], $hr);
    app(PayrollRunService::class)->addAdjustment($run, $this->emp, ['type' => 'deduction', 'label' => 'Advance recovery', 'amount' => 200, 'reason' => 'Agreed'], $hr);

    $item = $run->items()->where('user_id', $this->emp->id)->first();

    expect($item->gross_earnings)->toBe(14000.0)->and($item->total_deductions)->toBe(780.0 + 49.0 + 200.0)
        ->and($item->net_pay)->toBe(14000.0 - 1029.0);
});

test('hourly clients are paid for hours worked', function () {
    $this->settings->update(['calculation_method' => 'hourly']);
    structure($this, null, '2026-01-01', ['hourly_rate' => 100]);
    workMarch($this); // 26 days x 9 h

    $r = item($this);

    expect(lineAmount($r['earnings'], 'HOURLY'))->toBe(100.0 * 26 * 9);
});

test('PF wage ceiling and ESIC eligibility ceiling are honoured', function () {
    $this->settings->update(['pf_wage_ceiling' => 5000, 'esic_gross_ceiling' => 12000]);
    structure($this);
    workMarch($this);

    $r = item($this);

    expect(lineAmount($r['deductions'], 'PF_EE'))->toBe(600.0)  // 12 % of 5,000
        ->and(collect($r['deductions'])->pluck('code'))->not->toContain('ESIC_EE'); // gross 13,000 > 12,000
});

/* ───────────────────────── exceptions ───────────────────────── */

test('missing salary structures, unreviewed attendance and open periods block payroll', function () {
    workMarch($this);
    $r = item($this);
    expect(collect($r['exceptions'])->firstWhere('code', 'no_salary_structure')['blocking'])->toBeTrue();

    structure($this);
    AttendanceRecord::whereDate('attendance_date', '2026-03-03')->update(['review_status' => 'needs_review']);
    // recalculation keeps an unreviewed record unreviewed only if flags demand it, so inject a real exception
    AttendancePunch::whereDate('attendance_date', '2026-03-03')->where('type', 'out')->delete();

    $r = item($this);
    expect(collect($r['exceptions'])->pluck('code'))->toContain('attendance_review', 'attendance_unresolved');

    Carbon::setTestNow('2026-03-20 10:00');
    expect(collect(item($this)['exceptions'])->pluck('code'))->toContain('period_not_ended');
});

test('pending leave and pending corrections are reported', function () {
    structure($this);
    workMarch($this);

    $type = \App\Models\LeaveType::create(['name' => 'Casual', 'code' => 'CL', 'is_paid' => true]);
    \App\Models\LeaveApplication::create([
        'user_id' => $this->emp->id, 'client_id' => $this->client->id, 'leave_type_id' => $type->id,
        'start_date' => '2026-03-20', 'end_date' => '2026-03-20', 'total_days' => 1, 'reason' => 'x', 'status' => 'pending',
    ]);
    \App\Models\AttendanceCorrection::create([
        'user_id' => $this->emp->id, 'attendance_date' => '2026-03-10', 'reason' => 'x', 'status' => 'pending', 'requested_by' => $this->emp->id,
    ]);

    expect(collect(item($this)['exceptions'])->pluck('code'))->toContain('pending_leave', 'pending_corrections');
});

/* ───────────────────────── run workflow ───────────────────────── */

function readyRun($test): array
{
    structure($test);
    workMarch($test);

    $hr = $test->employee($test->client, ['client_id' => null], null, 'hr_recruiter');
    $admin = $test->employee($test->client, ['client_id' => null], null, 'admin');
    $svc = app(PayrollRunService::class);

    $run = $svc->create($test->period, $hr);
    $svc->calculate($run, $hr);

    return [$svc, $run->fresh(), $hr, $admin];
}

test('a run moves through the full lifecycle with the right permissions', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);

    expect($run->status)->toBe('calculated')->and($run->employee_count)->toBe(1)->and($run->total_net)->toBe(12171.0);

    $svc->transition($run, 'submit', $hr);
    expect($run->fresh()->status)->toBe('pending_review');

    $svc->transition($run->fresh(), 'review', $hr);
    expect($run->fresh()->status)->toBe('pending_approval');

    // HR prepares payroll but does not approve or publish it.
    expect(fn () => $svc->transition($run->fresh(), 'approve', $hr))->toThrow(PayrollException::class, 'permission');

    $svc->transition($run->fresh(), 'approve', $admin);
    $run = $run->fresh();
    expect($run->status)->toBe('approved')->and($run->approved_by)->toBe($admin->id);

    expect(fn () => $svc->transition($run, 'publish', $hr))->toThrow(PayrollException::class, 'permission');
    $svc->transition($run, 'publish', $admin);
    expect($run->fresh()->status)->toBe('payslips_published');

    $svc->transition($run->fresh(), 'close', $admin);
    expect($run->fresh()->status)->toBe('closed')->and($this->period->fresh()->status)->toBe('closed');

    expect(\App\Models\AuditLog::where('model_type', $run->getMorphClass())->where('event', 'like', 'payroll.%')->count())->toBeGreaterThanOrEqual(7);
});

test('steps cannot be skipped or repeated', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);

    expect(fn () => $svc->transition($run, 'approve', $admin))->toThrow(PayrollException::class, 'not available');
    expect(fn () => $svc->transition($run, 'publish', $admin))->toThrow(PayrollException::class, 'not available');

    $svc->transition($run, 'submit', $hr);
    expect(fn () => $svc->transition($run->fresh(), 'submit', $hr))->toThrow(PayrollException::class, 'not available');
});

test('unresolved exceptions stop a run from being submitted until fixed or held', function () {
    structure($this);
    workMarch($this);
    $other = $this->employee($this->client, [], $this->shift); // no salary structure
    workMarch($this, $other);

    $hr = $this->employee($this->client, ['client_id' => null], null, 'hr_recruiter');
    $svc = app(PayrollRunService::class);
    $run = $svc->create($this->period, $hr);
    $svc->calculate($run, $hr);

    expect($run->fresh()->exception_count)->toBe(1);
    expect(fn () => $svc->transition($run->fresh(), 'submit', $hr))->toThrow(PayrollException::class, 'unresolved');

    $item = $run->items()->where('user_id', $other->id)->first();
    expect(fn () => $svc->hold($item, true, '', $hr))->toThrow(PayrollException::class, 'reason');

    $svc->hold($item, true, 'Awaiting bank details', $hr);
    $svc->transition($run->fresh(), 'submit', $hr);

    expect($run->fresh()->status)->toBe('pending_review')->and($run->fresh()->exception_count)->toBe(0);

    // A held employee stays held when the run is recalculated.
    $svc->transition($run->fresh(), 'send_back', $hr, 'Recheck');
    $svc->calculate($run->fresh(), $hr);
    expect($run->items()->where('user_id', $other->id)->first()->status)->toBe('held');
});

test('separation of duties stops the preparer from approving their own run', function () {
    $this->settings->update(['separate_reviewer' => true]);
    [$svc, $run, $hr, $admin] = readyRun($this);
    $svc->transition($run, 'submit', $hr);

    $reviewerAdmin = $this->employee($this->client, ['client_id' => null], null, 'admin');
    $svc->transition($run->fresh(), 'review', $reviewerAdmin);

    $adminWhoPrepared = $hr;
    $hr->syncRoles(['admin']);

    expect(fn () => $svc->transition($run->fresh(), 'approve', $adminWhoPrepared->fresh()))
        ->toThrow(PayrollException::class, 'Separation');
    $svc->transition($run->fresh(), 'approve', $admin);

    expect($run->fresh()->status)->toBe('approved');
});

test('approval locks attendance and creates payslips that stay hidden', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);

    $svc->transition($run, 'submit', $hr);
    $svc->transition($run->fresh(), 'review', $hr);
    $svc->transition($run->fresh(), 'approve', $admin);

    expect(AttendanceRecord::where('user_id', $this->emp->id)->whereNotNull('locked_at')->count())->toBe(31);

    $payslip = Payslip::first();
    expect($payslip->net_pay)->toBe(12171.0)->and($payslip->isPublished())->toBeFalse()
        ->and($payslip->snapshot['net_in_words'])->toBe('Twelve Thousand One Hundred Seventy One Rupees Only')
        ->and(Payslip::visibleToEmployee()->count())->toBe(0);

    // Attendance edits are refused while locked.
    $punch = AttendancePunch::where('user_id', $this->emp->id)->first();
    expect(fn () => app(\App\Services\Workforce\PunchService::class)->void($punch, $hr, 'late fix'))
        ->toThrow(\App\Services\Workforce\PunchRejectedException::class, 'locked');

    // And so are payroll edits.
    expect(fn () => $svc->addAdjustment($run->fresh(), $this->emp, ['type' => 'bonus', 'label' => 'x', 'amount' => 1, 'reason' => 'x'], $hr))
        ->toThrow(PayrollException::class, 'before approval');
    expect(fn () => $svc->calculate($run->fresh(), $hr))->toThrow(PayrollException::class, 'recalculated');
});

test('payslips become visible only when published, and only for the employee', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);
    foreach (['submit' => $hr, 'review' => $hr, 'approve' => $admin] as $action => $by) {
        $svc->transition($run->fresh(), $action, $by);
    }

    expect(Payslip::visibleToEmployee()->where('user_id', $this->emp->id)->count())->toBe(0);

    $svc->transition($run->fresh(), 'publish', $admin);

    expect(Payslip::visibleToEmployee()->where('user_id', $this->emp->id)->count())->toBe(1)
        ->and(Payslip::visibleToEmployee()->where('user_id', $hr->id)->count())->toBe(0)
        ->and($this->emp->notifications()->count())->toBe(1);
});

test('reopening an approved run unlocks attendance and voids the unpublished payslips', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);
    foreach (['submit' => $hr, 'review' => $hr, 'approve' => $admin] as $action => $by) {
        $svc->transition($run->fresh(), $action, $by);
    }

    expect(fn () => $svc->transition($run->fresh(), 'reopen', $admin))->toThrow(PayrollException::class, 'reason');

    $svc->transition($run->fresh(), 'reopen', $admin, 'Wrong incentive');

    expect($run->fresh()->status)->toBe('calculated')
        ->and(AttendanceRecord::where('user_id', $this->emp->id)->whereNotNull('locked_at')->count())->toBe(0)
        ->and(Payslip::whereNull('voided_at')->count())->toBe(0)
        ->and(Payslip::count())->toBe(1);

    // Fixed and approved again: a fresh payslip is issued, the old one stays voided.
    $svc->calculate($run->fresh(), $hr);
    foreach (['submit' => $hr, 'review' => $hr, 'approve' => $admin] as $action => $by) {
        $svc->transition($run->fresh(), $action, $by);
    }

    expect(Payslip::count())->toBe(2)->and(Payslip::whereNull('voided_at')->count())->toBe(1);
});

test('a published run cannot be reopened', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);
    foreach (['submit' => $hr, 'review' => $hr, 'approve' => $admin, 'publish' => $admin] as $action => $by) {
        $svc->transition($run->fresh(), $action, $by);
    }

    expect(fn () => $svc->transition($run->fresh(), 'reopen', $admin, 'oops'))->toThrow(PayrollException::class, 'not available');
});

test('only one open run per period and run numbers are unique', function () {
    [$svc, $run, $hr] = readyRun($this);

    expect(fn () => $svc->create($this->period, $hr))->toThrow(PayrollException::class, 'already has');
    expect($run->run_number)->toStartWith('PR-202603-');
});

test('another clients employees never appear in a run', function () {
    structure($this);
    workMarch($this);
    $otherClient = $this->client('OTH');
    $stranger = $this->employee($otherClient, [], $this->shift($otherClient));

    $hr = $this->employee($this->client, ['client_id' => null], null, 'hr_recruiter');
    $run = app(PayrollRunService::class)->create($this->period, $hr);
    app(PayrollRunService::class)->calculate($run, $hr);

    expect($run->items()->pluck('user_id')->all())->toBe([$this->emp->id]);
});

test('payslip pdf renders and the net in words is correct', function () {
    [$svc, $run, $hr, $admin] = readyRun($this);
    foreach (['submit' => $hr, 'review' => $hr, 'approve' => $admin] as $action => $by) {
        $svc->transition($run->fresh(), $action, $by);
    }

    $bytes = app(PayslipService::class)->pdf(Payslip::first());

    expect(substr($bytes, 0, 4))->toBe('%PDF')->and(Payslip::first()->pdf_path)->not->toBeNull();
    expect(PayslipService::inWords(156000))->toBe('One Lakh Fifty Six Thousand Rupees Only')
        ->and(PayslipService::inWords(13991))->toBe('Thirteen Thousand Nine Hundred Ninety One Rupees Only');
});
