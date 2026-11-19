<?php

use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveLedgerEntry;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Services\Workforce\LeaveException;
use App\Services\Workforce\LeaveService;
use App\Services\Workforce\WorkforceScope;
use Carbon\Carbon;
use Tests\Feature\Workforce\BuildsWorkforce;

uses(BuildsWorkforce::class);

beforeEach(function () {
    $this->seedRoles();
    Carbon::setTestNow('2026-03-02 10:00'); // Monday

    $this->client = $this->client();
    $this->shift = $this->shift($this->client);
    $this->policy = $this->policy($this->client);

    $this->branch = \App\Models\Branch::query()->create([
        'name' => 'Indore', 'code' => 'IND', 'address' => 'x', 'city' => 'Indore', 'state' => 'MP',
        'postal_code' => '452001', 'phone' => '9999999999', 'email' => 'i@example.test',
    ]);

    $this->manager = $this->employee($this->client, ['first_name' => 'Tara', 'branch_id' => $this->branch->id], null, 'team_leader');
    $this->emp = $this->employee($this->client, ['reporting_to' => $this->manager->id, 'date_of_joining' => '2025-01-01', 'branch_id' => $this->branch->id], $this->shift);
    $this->hr = $this->employee($this->client, ['first_name' => 'Hema', 'branch_id' => $this->branch->id], null, 'hr_recruiter');

    $this->casual = LeaveType::create(['name' => 'Casual Leave', 'code' => 'CL', 'is_paid' => true]);
    $this->lwp = LeaveType::create(['name' => 'Leave Without Pay', 'code' => 'LWP', 'is_paid' => false]);

    $this->leavePolicy = LeavePolicy::create([
        'client_id' => $this->client->id, 'leave_type_id' => $this->casual->id, 'effective_from' => '2026-01-01',
        'accrual_frequency' => 'monthly', 'accrual_amount' => 1.5, 'annual_cap' => 12, 'credit_day' => 1,
        'allow_half_day' => true, 'min_notice_days' => 0, 'carry_forward_mode' => 'capped', 'carry_forward_cap' => 5,
        'approval_levels' => ['reporting_manager'],
    ]);
    LeavePolicy::create([
        'client_id' => $this->client->id, 'leave_type_id' => $this->lwp->id, 'effective_from' => '2026-01-01',
        'accrual_frequency' => 'none', 'allow_half_day' => true,
    ]);

    $this->svc = app(LeaveService::class);
});

afterEach(fn () => Carbon::setTestNow());

function creditCasual($test, float $days)
{
    LeaveLedgerEntry::create(['user_id' => $test->emp->id, 'leave_type_id' => $test->casual->id, 'entry_type' => 'opening', 'days' => $days, 'effective_date' => '2026-01-01']);
}

/* ───────────────────────── accrual & balances ───────────────────────── */

test('monthly accrual credits each month once and respects the annual cap', function () {
    $this->svc->accrue($this->emp, Carbon::parse('2026-03-15'));

    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(4.5); // Jan, Feb, Mar

    $this->svc->accrue($this->emp, Carbon::parse('2026-03-20'));
    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(4.5); // idempotent

    $this->svc->accrue($this->emp, Carbon::parse('2026-12-31'));
    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(12.0); // capped at 12 for the year
});

test('accrual starts at joining and waits for the eligibility period', function () {
    $new = $this->employee($this->client, ['date_of_joining' => '2026-02-10'], $this->shift);
    $this->leavePolicy->update(['eligible_after_days' => 30]);

    $this->svc->accrue($new, Carbon::parse('2026-03-02'));
    expect($this->svc->balance($new, $this->casual->id))->toBe(0.0);

    $this->svc->accrue($new, Carbon::parse('2026-04-02'));
    expect($this->svc->balance($new, $this->casual->id))->toBe(1.5); // April credit (day 1 is after eligibility)
});

test('year end applies the carry-forward cap and is idempotent', function () {
    creditCasual($this, 9);

    expect($this->svc->rollOver($this->emp, 2026))->toBe(1);
    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(5.0);

    expect($this->svc->rollOver($this->emp, 2026))->toBe(0);
    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(5.0);
});

/* ───────────────────────── applying ───────────────────────── */

test('weekly offs and holidays inside a request do not consume leave', function () {
    creditCasual($this, 10);
    Holiday::create(['client_id' => $this->client->id, 'date' => '2026-03-11', 'name' => 'Holi']);

    // Wed 11 (holiday) .. Mon 16, weekly off Sunday 15 -> Thu, Fri, Sat, Mon = 4 days
    $checked = $this->svc->validate($this->emp, $this->casual->id, Carbon::parse('2026-03-11'), Carbon::parse('2026-03-16'));

    expect($checked['days'])->toBe(4.0);
});

test('policy rules are enforced with clear messages', function () {
    creditCasual($this, 2);
    $v = fn (...$a) => fn () => $this->svc->validate($this->emp, $this->casual->id, ...$a);

    expect($v(Carbon::parse('2026-03-09'), Carbon::parse('2026-03-13')))->toThrow(LeaveException::class, 'Insufficient');
    expect($v(Carbon::parse('2026-03-10'), Carbon::parse('2026-03-09')))->toThrow(LeaveException::class, 'before the start');
    expect($v(Carbon::parse('2026-03-09'), Carbon::parse('2026-03-10'), true))->toThrow(LeaveException::class, 'single date');
    expect($v(Carbon::parse('2026-03-15'), Carbon::parse('2026-03-15')))->toThrow(LeaveException::class, 'weekly offs');

    $this->leavePolicy->update(['min_notice_days' => 3]);
    expect($v(Carbon::parse('2026-03-03'), Carbon::parse('2026-03-03')))->toThrow(LeaveException::class, 'notice');

    $this->leavePolicy->update(['min_notice_days' => 0, 'max_consecutive_days' => 1]);
    expect($v(Carbon::parse('2026-03-09'), Carbon::parse('2026-03-10')))->toThrow(LeaveException::class, 'consecutive');

    $this->leavePolicy->update(['max_consecutive_days' => null, 'allow_half_day' => false]);
    expect($v(Carbon::parse('2026-03-09'), Carbon::parse('2026-03-09'), true))->toThrow(LeaveException::class, 'half day');
});

test('probation and eligibility rules block leave that is not yet available', function () {
    $new = $this->employee($this->client, ['date_of_joining' => '2026-02-20'], $this->shift);
    $this->leavePolicy->update(['allow_during_probation' => false, 'probation_days' => 90]);
    creditCasual($this, 5);
    LeaveLedgerEntry::create(['user_id' => $new->id, 'leave_type_id' => $this->casual->id, 'entry_type' => 'opening', 'days' => 5, 'effective_date' => '2026-02-20']);

    expect(fn () => $this->svc->validate($new, $this->casual->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-09')))
        ->toThrow(LeaveException::class, 'probation');
});

test('overlapping requests and other clients leave types are rejected', function () {
    creditCasual($this, 10);
    $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-10'), 'Family');

    expect(fn () => $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-10'), Carbon::parse('2026-03-11'), 'Again'))
        ->toThrow(LeaveException::class, 'already have');

    $other = $this->client('OTH');
    $foreign = LeaveType::create(['client_id' => $other->id, 'name' => 'Foreign', 'code' => 'FOR', 'is_paid' => true]);

    expect(fn () => $this->svc->validate($this->emp, $foreign->id, Carbon::parse('2026-03-20'), Carbon::parse('2026-03-20')))
        ->toThrow(LeaveException::class, 'not available');
});

test('pending leave is held against the balance', function () {
    creditCasual($this, 2);
    $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-10'), 'Trip');

    expect($this->svc->balances($this->emp)->firstWhere('code', 'CL'))
        ->toMatchArray(['balance' => 2.0, 'pending' => 2.0, 'available' => 0.0]);

    expect(fn () => $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-12'), Carbon::parse('2026-03-12'), 'More'))
        ->toThrow(LeaveException::class, 'Insufficient');
});

/* ───────────────────────── approving ───────────────────────── */

test('the reporting manager approves, the ledger is debited and attendance shows leave', function () {
    creditCasual($this, 5);
    $app = $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-03'), Carbon::parse('2026-03-04'), 'Wedding');

    expect($this->svc->canDecide($this->manager, $app))->toBeTrue()
        ->and($this->svc->canDecide($this->emp, $app))->toBeFalse();

    $this->svc->approve($app, $this->manager, 'Enjoy');

    expect($app->fresh()->status)->toBe('approved')
        ->and($this->svc->balance($this->emp, $this->casual->id))->toBe(3.0);

    $record = AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-03')->first();
    expect($record->status)->toBe('leave')->and($record->payable_fraction)->toBe(1.0);

    expect($this->emp->notifications()->count())->toBe(1);
});

test('multi level approval needs every level and an employee cannot approve their own leave', function () {
    $this->leavePolicy->update(['approval_levels' => ['reporting_manager', 'hr']]);
    creditCasual($this, 5);

    $app = $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-09'), 'Appointment');
    expect($app->approvals)->toHaveCount(2);

    $this->svc->approve($app, $this->manager);
    expect($app->fresh()->status)->toBe('pending')->and($this->svc->balance($this->emp, $this->casual->id))->toBe(5.0);

    expect(fn () => $this->svc->approve($app->fresh(), $this->emp))->toThrow(LeaveException::class);

    $this->svc->approve($app->fresh(), $this->hr);
    expect($app->fresh()->status)->toBe('approved')->and($this->svc->balance($this->emp, $this->casual->id))->toBe(4.0);
});

test('someone outside the chain cannot approve and a rejection needs no balance change', function () {
    creditCasual($this, 5);
    $app = $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-09'), 'x');

    $stranger = $this->employee($this->client, [], null, 'employee');
    expect(fn () => $this->svc->approve($app, $stranger))->toThrow(LeaveException::class, 'not an approver');

    $this->svc->reject($app, $this->manager, 'Peak week');

    expect($app->fresh()->status)->toBe('rejected')->and($this->svc->balance($this->emp, $this->casual->id))->toBe(5.0);
    expect(fn () => $this->svc->approve($app->fresh(), $this->manager))->toThrow(LeaveException::class, 'already');
});

test('unpaid leave never touches a balance but pays nothing', function () {
    $app = $this->svc->apply($this->emp, $this->lwp->id, Carbon::parse('2026-03-03'), Carbon::parse('2026-03-03'), 'Personal');
    $this->svc->approve($app, $this->manager);

    expect(LeaveLedgerEntry::where('leave_type_id', $this->lwp->id)->count())->toBe(0);

    $record = AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-03')->first();
    expect($record->status)->toBe('leave')->and($record->payable_fraction)->toBe(0.0);
});

test('half day leave pays half and cancelling reverses the balance and attendance', function () {
    creditCasual($this, 5);
    $app = $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-05'), Carbon::parse('2026-03-05'), 'Dentist', true, 'second_half');
    $this->svc->approve($app, $this->manager);

    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(4.5);

    $this->svc->cancel($app->fresh(), $this->emp);

    expect($app->fresh()->status)->toBe('cancelled')->and($this->svc->balance($this->emp, $this->casual->id))->toBe(5.0);
    expect(AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-05')->first()->status)->toBe('pending'); // 5 March is still in the future
});

test('approved leave that has started cannot be cancelled by the employee', function () {
    creditCasual($this, 5);
    $app = $this->svc->apply($this->emp, $this->casual->id, Carbon::parse('2026-03-02'), Carbon::parse('2026-03-02'), 'Today');
    $this->svc->approve($app, $this->manager);

    expect(fn () => $this->svc->cancel($app->fresh(), $this->emp))->toThrow(LeaveException::class, 'started');

    $this->svc->cancel($app->fresh(), $this->hr); // HR still can
    expect($app->fresh()->status)->toBe('cancelled');
});

test('leave cannot be applied or approved on days payroll has locked', function () {
    creditCasual($this, 5);
    AttendanceRecord::create(['user_id' => $this->emp->id, 'attendance_date' => '2026-03-03', 'status' => 'absent', 'locked_at' => now()]);

    expect(fn () => $this->svc->validate($this->emp, $this->casual->id, Carbon::parse('2026-03-03'), Carbon::parse('2026-03-03')))
        ->toThrow(LeaveException::class, 'already approved');
});

test('balance adjustments are signed, reasoned and audited', function () {
    $this->svc->adjust($this->emp, $this->casual->id, 3, 'Carry over from old system', $this->hr);
    $this->svc->adjust($this->emp, $this->casual->id, -1, 'Correction', $this->hr);

    expect($this->svc->balance($this->emp, $this->casual->id))->toBe(2.0);
    expect(\App\Models\AuditLog::where('event', 'leave.balance_adjusted')->count())->toBe(2);
    expect(fn () => $this->svc->adjust($this->emp, $this->casual->id, 0, 'nothing', $this->hr))->toThrow(LeaveException::class);
});

/* ───────────────────────── who can see whom ───────────────────────── */

test('team leaders see their reportees, HR sees the branch, admins see everyone', function () {
    $branch = $this->branch;
    $inBranch = $this->employee($this->client, ['branch_id' => $branch->id], $this->shift);
    $outside = $this->employee($this->client, ['branch_id' => null], $this->shift);
    $deep = $this->employee($this->client, ['reporting_to' => $this->emp->id], $this->shift);
    $admin = $this->employee($this->client, [], null, 'admin');

    $leader = WorkforceScope::employees($this->manager, 'leaves')->pluck('id')->all();
    expect($leader)->toContain($this->emp->id, $deep->id, $this->manager->id)->not->toContain($outside->id);

    $hr = WorkforceScope::employees($this->hr, 'attendance')->pluck('id')->all();
    expect($hr)->toContain($inBranch->id, $this->hr->id, $this->emp->id)->not->toContain($outside->id);

    expect(WorkforceScope::employees($admin, 'payroll')->count())->toBe(\App\Models\User::count());
    expect(WorkforceScope::employees($this->emp, 'leaves')->pluck('id')->all())->toBe([$this->emp->id]);
});
