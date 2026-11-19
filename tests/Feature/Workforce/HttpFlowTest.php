<?php

use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\EmployeeSalaryStructure;
use App\Models\LeaveApplication;
use App\Models\LeaveLedgerEntry;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryStructureItem;
use App\Models\User;
use App\Services\Workforce\PayrollRunService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Workforce\BuildsWorkforce;

uses(BuildsWorkforce::class);

beforeEach(function () {
    $this->withoutVite(); // pages are checked by component name; the build is produced by `npm run build`
    $this->seedRoles();
    Carbon::setTestNow('2026-03-04 10:00'); // Wednesday

    $this->client = $this->client();
    $this->shift = $this->shift($this->client);
    $this->policy = $this->policy($this->client, ['geofence_required' => true]);
    $this->office($this->client, 22.7196, 75.8577, 10);

    $this->branch = Branch::query()->create([
        'name' => 'Indore', 'code' => 'IND', 'address' => 'x', 'city' => 'Indore', 'state' => 'MP',
        'postal_code' => '452001', 'phone' => '9999999999', 'email' => 'i@example.test',
    ]);

    $this->leader = $this->employee($this->client, ['branch_id' => $this->branch->id], null, 'team_leader');
    $this->emp = $this->employee($this->client, ['reporting_to' => $this->leader->id, 'branch_id' => $this->branch->id], $this->shift);
    $this->other = $this->employee($this->client, ['branch_id' => $this->branch->id], $this->shift);
    $this->hr = $this->employee($this->client, ['branch_id' => $this->branch->id, 'client_id' => null], null, 'hr_recruiter');
    $this->admin = $this->employee($this->client, ['client_id' => null], null, 'admin');
});

afterEach(fn () => Carbon::setTestNow());

/* ───────────────────────── who can open what ───────────────────────── */

test('an employee reaches their own pages and nothing managerial', function () {
    $this->actingAs($this->emp);

    foreach ([
        'employee.dashboard' => 'Employee/Dashboard',
        'employee.punch' => 'Employee/Punch',
        'employee.attendance' => 'Employee/Attendance',
        'employee.leave' => 'Employee/Leave',
        'employee.payslips' => 'Employee/Payslips',
    ] as $route => $component) {
        $this->get(route($route))->assertOk()->assertInertia(fn ($p) => $p->component($component));
    }

    foreach (['workforce.attendance.index', 'workforce.attendance.exceptions', 'workforce.attendance.corrections', 'workforce.leave.index',
        'workforce.leave.balances', 'workforce.payroll.index', 'workforce.payroll.structures', 'workforce.payroll.settings',
        'workforce.payroll.payslips', 'workforce.reports.index', 'workforce.setup.locations', 'workforce.attendance.import',
        'admin.attendance-policies.index', 'admin.leave-policies.index', 'admin.leave-types.index', 'admin.clients.index',
        'admin.shifts.index', 'admin.integrations.index'] as $route) {
        $this->get(route($route))->assertForbidden();
    }
});

test('a team leader sees the team pages but not payroll or setup', function () {
    $this->actingAs($this->leader);

    $this->get(route('workforce.attendance.index'))->assertOk()->assertInertia(fn ($p) => $p->component('Workforce/Attendance/Index'));
    $this->get(route('workforce.leave.index'))->assertOk()->assertInertia(fn ($p) => $p->component('Workforce/Leave/Index'));
    $this->get(route('workforce.attendance.corrections'))->assertOk();

    $this->get(route('workforce.payroll.index'))->assertForbidden();
    $this->get(route('workforce.payroll.structures'))->assertForbidden();
    $this->get(route('workforce.setup.locations'))->assertForbidden();
    $this->get(route('workforce.attendance.import'))->assertForbidden();
    $this->get(route('workforce.reports.index'))->assertOk(); // allowed (leave reports only, scoped)
    $this->get(route('workforce.reports.show', 'payroll_summary'))->assertForbidden();
});

test('HR runs payroll preparation but cannot configure admin setup', function () {
    $this->actingAs($this->hr);

    foreach (['workforce.payroll.index' => 'Workforce/Payroll/Index', 'workforce.payroll.structures' => 'Workforce/Payroll/Structures',
        'workforce.payroll.periods' => 'Workforce/Payroll/Periods', 'workforce.payroll.settings' => 'Workforce/Payroll/Settings',
        'workforce.attendance.import' => 'Workforce/Attendance/Import', 'workforce.leave.balances' => 'Workforce/Leave/Balances',
        'workforce.setup.holidays' => 'Workforce/Setup/Holidays'] as $route => $component) {
        $this->get(route($route))->assertOk()->assertInertia(fn ($p) => $p->component($component));
    }

    $this->get(route('workforce.setup.locations'))->assertForbidden();
    $this->get(route('admin.attendance-policies.index'))->assertForbidden();
});

test('an administrator reaches every screen', function () {
    $this->actingAs($this->admin);

    foreach ([
        'workforce.setup.locations' => 'Workforce/Setup/Locations',
        'admin.attendance-policies.index' => 'Admin/AttendancePolicies/Index',
        'admin.leave-policies.index' => 'Admin/LeavePolicies/Index',
        'admin.leave-types.index' => 'Admin/LeaveTypes/Index',
        'workforce.reports.index' => 'Workforce/Reports/Index',
        'workforce.payroll.payslips' => 'Workforce/Payroll/Payslips',
    ] as $route => $component) {
        $this->get(route($route))->assertOk()->assertInertia(fn ($p) => $p->component($component));
    }

    $this->get(route('admin.attendance-policies.create'))->assertOk()->assertInertia(fn ($p) => $p->component('Admin/AttendancePolicies/Form'));
    $this->get(route('admin.attendance-policies.edit', $this->policy))->assertOk();
    $this->get(route('admin.leave-policies.create'))->assertOk();
});

/* ───────────────────────── employee punching ───────────────────────── */

test('punching over HTTP enforces the geofence and records the day', function () {
    $this->actingAs($this->emp);

    $this->post(route('employee.punch.store'), ['type' => 'in', 'latitude' => 22.8, 'longitude' => 75.9, 'accuracy' => 5])
        ->assertSessionHasErrors('punch');
    expect(AttendancePunch::count())->toBe(0);

    $this->post(route('employee.punch.store'), ['type' => 'in'])->assertSessionHasErrors('punch'); // no location

    $this->post(route('employee.punch.store'), ['type' => 'in', 'latitude' => 22.7196, 'longitude' => 75.8577, 'accuracy' => 6])
        ->assertSessionHasNoErrors();

    expect(AttendancePunch::first())->source->toBe('portal')->geo_status->toBe('inside');

    $this->get(route('employee.punch'))->assertInertia(fn ($p) => $p->where('lastType', 'in')->where('actions', ['break_start', 'out']));

    // The employee cannot choose the source or the time.
    $this->post(route('employee.punch.store'), ['type' => 'out', 'source' => 'machine', 'punched_at' => '2020-01-01 00:00:00', 'latitude' => 22.7196, 'longitude' => 75.8577, 'accuracy' => 6]);
    $out = AttendancePunch::where('type', 'out')->first();
    expect($out->source)->toBe('portal')->and($out->punched_at->toDateString())->toBe('2026-03-04');
});

test('the month view fills in days and reports status', function () {
    $this->actingAs($this->emp);

    $this->get(route('employee.attendance', ['month' => '2026-03']))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Employee/Attendance')->has('days', 31)->where('month', '2026-03')->where('nextMonth', null));

    // Monday 2 March had no punches and is in the past.
    expect(AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-02')->first()->status)->toBe('absent');
});

test('an employee can request a correction and a manager can approve it, but not their own', function () {
    $punch = fn ($type, $time) => AttendancePunch::create([
        'user_id' => $this->emp->id, 'client_id' => $this->client->id, 'attendance_date' => '2026-03-03',
        'punched_at' => Carbon::parse("2026-03-03 $time"), 'type' => $type, 'source' => 'portal', 'geo_status' => 'inside',
    ]);
    $punch('in', '09:00');

    $this->actingAs($this->emp)
        ->post(route('employee.attendance.corrections.store'), ['date' => '2026-03-03', 'out_time' => '18:05', 'reason' => 'Forgot to punch out'])
        ->assertSessionHasNoErrors();

    $correction = \App\Models\AttendanceCorrection::first();

    // The employee cannot approve it themselves.
    $this->post(route('workforce.attendance.corrections.approve', $correction))->assertForbidden();

    $this->actingAs($this->other)->post(route('workforce.attendance.corrections.approve', $correction))->assertForbidden();

    $this->actingAs($this->leader)->post(route('workforce.attendance.corrections.approve', $correction), ['note' => 'ok'])->assertSessionHasNoErrors();

    expect($correction->fresh()->status)->toBe('approved')
        ->and(AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-03')->first()->status)->toBe('present');
});

/* ───────────────────────── leave ───────────────────────── */

function leaveSetup($test): LeaveType
{
    $type = LeaveType::create(['name' => 'Casual Leave', 'code' => 'CL', 'is_paid' => true]);
    LeavePolicy::create([
        'client_id' => $test->client->id, 'leave_type_id' => $type->id, 'effective_from' => '2026-01-01',
        'accrual_frequency' => 'monthly', 'accrual_amount' => 1, 'approval_levels' => ['reporting_manager'], 'allow_half_day' => true,
    ]);
    LeaveLedgerEntry::create(['user_id' => $test->emp->id, 'leave_type_id' => $type->id, 'entry_type' => 'opening', 'days' => 5, 'effective_date' => '2026-01-01']);

    return $type;
}

test('leave: preview, apply, approve by the manager, and nobody else can decide', function () {
    $type = leaveSetup($this);

    $this->actingAs($this->emp);

    $this->postJson(route('employee.leave.preview'), ['leave_type_id' => $type->id, 'start_date' => '2026-03-09', 'end_date' => '2026-03-10'])
        ->assertOk()->assertJson(['ok' => true, 'days' => 2.0]);

    $this->postJson(route('employee.leave.preview'), ['leave_type_id' => $type->id, 'start_date' => '2026-03-09', 'end_date' => '2026-03-20'])
        ->assertJson(['ok' => false]);

    $this->post(route('employee.leave.store'), ['leave_type_id' => $type->id, 'start_date' => '2026-03-09', 'end_date' => '2026-03-10', 'reason' => 'Family'])
        ->assertSessionHasNoErrors();

    $app = LeaveApplication::first();
    expect($app->status)->toBe('pending');

    $this->get(route('employee.leave'))->assertInertia(fn ($p) => $p->component('Employee/Leave')->has('applications', 1));

    $this->actingAs($this->other)->post(route('workforce.leave.approve', $app))->assertForbidden();
    $this->actingAs($this->emp)->post(route('workforce.leave.approve', $app))->assertForbidden();

    $this->actingAs($this->leader)->get(route('workforce.leave.index'))
        ->assertInertia(fn ($p) => $p->has('applications.data', 1)->where('applications.data.0.can_decide', true));

    $this->actingAs($this->leader)->post(route('workforce.leave.approve', $app), ['note' => 'ok'])->assertSessionHasNoErrors();

    expect($app->fresh()->status)->toBe('approved');
    expect((float) LeaveLedgerEntry::where('user_id', $this->emp->id)->sum('days'))->toBe(3.0);
});

test('the manager leave queue only shows people they can see', function () {
    $type = leaveSetup($this);
    LeaveLedgerEntry::create(['user_id' => $this->other->id, 'leave_type_id' => $type->id, 'entry_type' => 'opening', 'days' => 5, 'effective_date' => '2026-01-01']);

    app(\App\Services\Workforce\LeaveService::class)->apply($this->emp, $type->id, Carbon::parse('2026-03-09'), Carbon::parse('2026-03-09'), 'a');
    app(\App\Services\Workforce\LeaveService::class)->apply($this->other, $type->id, Carbon::parse('2026-03-10'), Carbon::parse('2026-03-10'), 'b');

    $this->actingAs($this->leader)->get(route('workforce.leave.index'))
        ->assertInertia(fn ($p) => $p->has('applications.data', 1)->where('applications.data.0.employee', $this->emp->full_name));

    $this->actingAs($this->hr)->get(route('workforce.leave.index'))->assertInertia(fn ($p) => $p->has('applications.data', 2));
});

/* ───────────────────────── attendance board scoping ───────────────────────── */

test('the daily board is narrowed to the viewer', function () {
    $outsider = $this->employee($this->client, ['branch_id' => null], $this->shift);

    $names = fn ($user) => collect($this->actingAs($user)->get(route('workforce.attendance.index', ['date' => '2026-03-03']))
        ->assertOk()->viewData('page')['props']['rows'])->pluck('user_id')->all();

    expect($names($this->leader))->toContain($this->emp->id)->not->toContain($this->other->id, $outsider->id);
    expect($names($this->hr))->toContain($this->emp->id, $this->other->id, $this->leader->id)->not->toContain($outsider->id);
    expect($names($this->admin))->toContain($outsider->id, $this->emp->id);
});

test('managers cannot open or edit an employee outside their scope', function () {
    $outsider = $this->employee($this->client, ['branch_id' => null], $this->shift);

    $this->actingAs($this->leader)->get(route('workforce.attendance.employee', $this->emp))->assertOk();
    $this->actingAs($this->leader)->get(route('workforce.attendance.employee', $outsider))->assertForbidden();
    $this->actingAs($this->hr)->get(route('workforce.attendance.employee', $outsider))->assertForbidden();

    $this->actingAs($this->hr)->post(route('workforce.attendance.manual-punch', $outsider), ['type' => 'in', 'date' => '2026-03-03', 'time' => '09:00', 'reason' => 'test reason'])->assertForbidden();

    $this->actingAs($this->hr)->post(route('workforce.attendance.manual-punch', $this->emp), ['type' => 'in', 'date' => '2026-03-03', 'time' => '09:00', 'reason' => 'Machine was down'])
        ->assertSessionHasNoErrors();
    expect(AttendancePunch::where('source', 'manual')->first())->created_by->toBe($this->hr->id);
});

test('machine punches can be imported through the upload form', function () {
    $csv = "employee_id,punched_at,direction\n{$this->emp->employee_id},2026-03-03 09:00:00,in\n{$this->emp->employee_id},2026-03-03 18:00:00,out\n";
    $file = UploadedFile::fake()->createWithContent('punches.csv', $csv);

    $this->actingAs($this->hr)->post(route('workforce.attendance.import.store'), ['file' => $file])->assertSessionHasNoErrors();

    expect(AttendancePunch::where('source', 'machine')->count())->toBe(2);
    $this->actingAs($this->leader)->post(route('workforce.attendance.import.store'), ['file' => UploadedFile::fake()->createWithContent('p.csv', $csv)])->assertForbidden();
});

/* ───────────────────────── payroll over HTTP ───────────────────────── */

function payrollReady($test): PayrollRun
{
    $test->policy->update(['geofence_required' => false]);
    $period = PayrollPeriod::create(['client_id' => $test->client->id, 'name' => 'Feb 2026', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28', 'attendance_cutoff_date' => '2026-03-02', 'salary_processing_date' => '2026-03-10']);

    $s = EmployeeSalaryStructure::create(['user_id' => $test->emp->id, 'client_id' => $test->client->id, 'effective_from' => '2026-01-01']);
    SalaryStructureItem::create(['structure_id' => $s->id, 'name' => 'Basic', 'code' => 'BASIC', 'type' => 'earning', 'calc_type' => 'fixed', 'amount' => 20000, 'is_basic' => true, 'prorate' => true]);

    for ($d = Carbon::parse('2026-02-01'); $d->lte(Carbon::parse('2026-02-28')); $d->addDay()) {
        if ($d->isSunday()) {
            continue;
        }
        foreach ([['09:00', 'in'], ['18:00', 'out']] as [$t, $type]) {
            AttendancePunch::create(['user_id' => $test->emp->id, 'client_id' => $test->client->id, 'attendance_date' => $d->toDateString(), 'punched_at' => Carbon::parse($d->toDateString() . " $t"), 'type' => $type, 'source' => 'portal', 'geo_status' => 'not_required']);
        }
    }

    // Everyone else on this client is out of scope for the test.
    User::where('client_id', $test->client->id)->whereNotIn('id', [$test->emp->id])->update(['employment_status' => 'inactive']);

    return app(PayrollRunService::class)->create($period, $test->hr);
}

test('payroll over HTTP: HR calculates and submits, only an admin approves and publishes', function () {
    $run = payrollReady($this);

    $this->actingAs($this->hr)->post(route('workforce.payroll.runs.calculate', $run))->assertSessionHasNoErrors();
    expect($run->fresh()->total_net)->toBe(20000.0 - 2400.0 /* PF not configured: only basic */ + 2400.0);

    $this->actingAs($this->hr)->get(route('workforce.payroll.runs.show', $run))
        ->assertOk()->assertInertia(fn ($p) => $p->component('Workforce/Payroll/Show')->where('run.status', 'calculated')->has('items', 1)
            ->where('actions.0.key', 'submit'));

    $this->actingAs($this->hr)->post(route('workforce.payroll.runs.transition', ['run' => $run, 'action' => 'submit']))->assertSessionHasNoErrors();
    $this->actingAs($this->hr)->post(route('workforce.payroll.runs.transition', ['run' => $run, 'action' => 'review']))->assertSessionHasNoErrors();

    // HR is told no (permission), nothing changes.
    $this->actingAs($this->hr)->post(route('workforce.payroll.runs.transition', ['run' => $run, 'action' => 'approve']))->assertSessionHasErrors('run');
    expect($run->fresh()->status)->toBe('pending_approval');

    $this->actingAs($this->admin)->post(route('workforce.payroll.runs.transition', ['run' => $run, 'action' => 'approve']))->assertSessionHasNoErrors();

    // Employees see nothing yet.
    $this->actingAs($this->emp)->get(route('employee.payslips'))->assertInertia(fn ($p) => $p->has('payslips', 0));

    $payslip = Payslip::first();
    $this->actingAs($this->emp)->get(route('employee.payslips.download', $payslip))->assertNotFound();

    $this->actingAs($this->admin)->post(route('workforce.payroll.runs.transition', ['run' => $run, 'action' => 'publish']))->assertSessionHasNoErrors();

    $this->actingAs($this->emp)->get(route('employee.payslips'))->assertInertia(fn ($p) => $p->has('payslips', 1)->where('payslips.0.net', 20000));

    $response = $this->actingAs($this->emp)->get(route('employee.payslips.download', $payslip))->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');

    // Someone else's payslip is a 404, never the file.
    $this->actingAs($this->other)->get(route('employee.payslips.download', $payslip))->assertNotFound();
});

test('salary data is closed to people without payroll permissions', function () {
    $run = payrollReady($this);
    app(PayrollRunService::class)->calculate($run, $this->hr);

    foreach ([$this->emp, $this->leader, $this->other] as $user) {
        $this->actingAs($user)->get(route('workforce.payroll.runs.show', $run))->assertForbidden();
        $this->actingAs($user)->get(route('workforce.payroll.structures.edit', $this->emp->id === $user->id ? $this->other : $this->emp))->assertForbidden();
        $this->actingAs($user)->post(route('workforce.payroll.runs.calculate', $run))->assertForbidden();
    }
});

test('salary structure versions and import from an accepted offer', function () {
    $this->actingAs($this->hr);

    $payload = fn ($basic, $from) => [
        'effective_from' => $from,
        'items' => [
            ['name' => 'Basic', 'type' => 'earning', 'calc_type' => 'fixed', 'amount' => $basic, 'is_basic' => true, 'prorate' => true],
            ['name' => 'PF employee Contribution', 'type' => 'deduction', 'calc_type' => 'percent_of_basic', 'percentage' => 12, 'prorate' => true, 'statutory_code' => 'pf_employee'],
        ],
    ];

    $this->post(route('workforce.payroll.structures.store', $this->emp), $payload(10000, '2026-01-01'))->assertSessionHasNoErrors();
    $this->post(route('workforce.payroll.structures.store', $this->emp), $payload(12000, '2026-04-01'))->assertSessionHasNoErrors();

    $versions = EmployeeSalaryStructure::where('user_id', $this->emp->id)->orderBy('effective_from')->get();
    expect($versions)->toHaveCount(2)->and($versions[0]->effective_to->toDateString())->toBe('2026-03-31')->and($versions[1]->effective_to)->toBeNull();

    // A version cannot be slipped in before an existing one.
    $this->post(route('workforce.payroll.structures.store', $this->emp), $payload(9000, '2026-02-01'))->assertSessionHasErrors('effective_from');

    // No earning at all is refused.
    $this->post(route('workforce.payroll.structures.store', $this->emp), ['effective_from' => '2026-06-01', 'items' => [['name' => 'PF', 'type' => 'deduction', 'calc_type' => 'fixed', 'amount' => 5]]])->assertSessionHasErrors('items');

    $this->get(route('workforce.payroll.structures.edit', $this->emp))->assertOk()->assertInertia(fn ($p) => $p->component('Workforce/Payroll/StructureEdit')->has('history', 2));
});

/* ───────────────────────── reports ───────────────────────── */

test('reports render and export, are scoped, and neutralise spreadsheet formulas', function () {
    $this->emp->update(['first_name' => '=HYPERLINK("http://evil")', 'last_name' => 'Tester']);
    AttendancePunch::create(['user_id' => $this->emp->id, 'client_id' => $this->client->id, 'attendance_date' => '2026-03-03', 'punched_at' => Carbon::parse('2026-03-03 09:00'), 'type' => 'in', 'source' => 'portal', 'geo_status' => 'inside']);
    AttendancePunch::create(['user_id' => $this->emp->id, 'client_id' => $this->client->id, 'attendance_date' => '2026-03-03', 'punched_at' => Carbon::parse('2026-03-03 18:00'), 'type' => 'out', 'source' => 'portal', 'geo_status' => 'inside']);

    $this->actingAs($this->hr);

    $this->get(route('workforce.reports.show', ['report' => 'daily_attendance', 'from' => '2026-03-03', 'to' => '2026-03-03']))
        ->assertOk()->assertInertia(fn ($p) => $p->component('Workforce/Reports/Show')->where('report.key', 'daily_attendance')->has('rows'));

    $csv = $this->get(route('workforce.reports.show', ['report' => 'daily_attendance', 'from' => '2026-03-03', 'to' => '2026-03-03', 'export' => 'csv']))->assertOk();
    $body = $csv->streamedContent();

    expect($body)->toContain('Employee ID')->toContain("'=HYPERLINK")->not->toContain(',=HYPERLINK');

    $payrollReports = ['payroll_summary', 'payroll_statement', 'earnings_deductions', 'payslip_status', 'approval_history', 'payroll_cost'];

    foreach (array_keys(\App\Services\Workforce\ReportService::REPORTS) as $key) {
        $this->actingAs($this->admin)->get(route('workforce.reports.show', $key))->assertOk();

        // Salary reports are closed to anyone without payroll export rights, such as a team leader.
        if (in_array($key, $payrollReports, true)) {
            $this->actingAs($this->leader)->get(route('workforce.reports.show', $key))->assertForbidden();
            $this->actingAs($this->emp)->get(route('workforce.reports.show', $key))->assertForbidden();
        }
    }

    $this->actingAs($this->emp)->get(route('workforce.reports.show', 'daily_attendance'))->assertForbidden();
});

/* ───────────────────────── admin setup ───────────────────────── */

test('admins can save an attendance policy and overlapping ones are refused', function () {
    $this->actingAs($this->admin);

    $payload = fn (array $o = []) => array_merge([
        'client_id' => $this->client->id, 'name' => 'Night rules', 'effective_from' => '2027-01-01',
        'full_day_minutes' => 540, 'half_day_minutes' => 270, 'missing_punch_treatment' => 'review',
        'weekly_offs' => [6, 7], 'source_priority' => ['portal'],
    ], $o);

    // The existing policy is open-ended, so a later one is refused until it is given an end date.
    $this->post(route('admin.attendance-policies.store'), $payload())->assertSessionHasErrors('effective_from');

    $this->policy->update(['effective_to' => '2026-12-31']);
    $this->post(route('admin.attendance-policies.store'), $payload())->assertSessionHasNoErrors();
    expect(\App\Models\AttendancePolicy::where('name', 'Night rules')->first())->weekly_offs->toBe([6, 7]);

    $this->post(route('admin.attendance-policies.store'), $payload(['name' => 'Clash', 'effective_from' => '2026-06-01']))->assertSessionHasErrors('effective_from');

    $this->post(route('admin.attendance-policies.store'), $payload(['half_day_minutes' => 600]))->assertSessionHasErrors('half_day_minutes');

    expect(\App\Models\AuditLog::where('event', 'attendance_policy.created')->count())->toBe(1);
});

test('office locations and holidays are managed by an admin and audited', function () {
    $this->actingAs($this->admin);

    $this->post(route('workforce.setup.locations.store'), ['name' => 'Annexe', 'latitude' => 22.1, 'longitude' => 75.1, 'radius_meters' => 25, 'max_accuracy_meters' => 40, 'client_id' => $this->client->id])
        ->assertSessionHasNoErrors();
    $this->post(route('workforce.setup.locations.store'), ['name' => 'Bad', 'latitude' => 122, 'longitude' => 75, 'radius_meters' => 25, 'max_accuracy_meters' => 40])
        ->assertSessionHasErrors('latitude');

    $this->post(route('workforce.setup.holidays.store'), ['date' => '2026-08-15', 'name' => 'Independence Day'])->assertSessionHasNoErrors();
    $this->post(route('workforce.setup.holidays.store'), ['date' => '2026-08-15', 'name' => 'Duplicate'])->assertSessionHasErrors('date');

    expect(\App\Models\AuditLog::whereIn('event', ['office_location.created', 'holiday.created'])->count())->toBe(2);
});

test('the existing client, shift and integration screens still open for an admin', function () {
    $this->actingAs($this->admin);

    $this->get(route('admin.clients.index'))->assertOk();
    $this->get(route('admin.clients.show', $this->client))->assertOk();
    $this->get(route('admin.clients.edit', $this->client))->assertOk();
    $this->get(route('admin.shifts.index'))->assertOk();
    $this->get(route('admin.shifts.create'))->assertOk();
    $this->get(route('admin.integrations.index'))->assertOk();

    // Everyone else is shut out of the same screens.
    foreach ([$this->hr, $this->emp, $this->leader] as $user) {
        $this->actingAs($user)->get(route('admin.clients.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.integrations.index'))->assertForbidden();
    }
});

test('the nightly commands run and are safe to repeat', function () {
    $this->policy->update(['geofence_required' => false]);

    $this->artisan('attendance:process-daily', ['--date' => '2026-03-03'])->assertSuccessful();
    expect(AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-03')->first()->status)->toBe('absent');

    // A half-recorded day alerts the employee and their manager exactly once.
    AttendancePunch::create(['user_id' => $this->emp->id, 'client_id' => $this->client->id, 'attendance_date' => '2026-03-02', 'punched_at' => Carbon::parse('2026-03-02 09:00'), 'type' => 'in', 'source' => 'portal', 'geo_status' => 'inside']);
    $this->artisan('attendance:process-daily', ['--date' => '2026-03-02'])->assertSuccessful();
    $this->artisan('attendance:process-daily', ['--date' => '2026-03-02'])->assertSuccessful();

    expect($this->emp->notifications()->count())->toBe(1)->and($this->leader->notifications()->count())->toBe(1);

    $type = LeaveType::create(['name' => 'Casual', 'code' => 'CL', 'is_paid' => true]);
    LeavePolicy::create(['client_id' => $this->client->id, 'leave_type_id' => $type->id, 'effective_from' => '2026-01-01', 'accrual_frequency' => 'monthly', 'accrual_amount' => 1.5]);

    $this->artisan('leave:accrue', ['--date' => '2026-03-15'])->assertSuccessful();
    $this->artisan('leave:accrue', ['--date' => '2026-03-15'])->assertSuccessful();

    expect((float) LeaveLedgerEntry::where('user_id', $this->emp->id)->sum('days'))->toBe(4.5);
});
