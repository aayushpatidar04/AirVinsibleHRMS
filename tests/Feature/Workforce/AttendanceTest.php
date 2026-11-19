<?php

use App\Models\AttendanceCorrection;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\Workforce\AttendanceCalculator;
use App\Services\Workforce\CorrectionService;
use App\Services\Workforce\GeofenceService;
use App\Services\Workforce\MachinePunchImporter;
use App\Services\Workforce\PunchRejectedException;
use App\Services\Workforce\PunchService;
use Carbon\Carbon;
use Tests\Feature\Workforce\BuildsWorkforce;

uses(BuildsWorkforce::class);

beforeEach(function () {
    $this->seedRoles();
    $this->client = $this->client();
    $this->shift = $this->shift($this->client);
    $this->policy = $this->policy($this->client);
    $this->emp = $this->employee($this->client, [], $this->shift);
});

afterEach(fn () => Carbon::setTestNow());

function punchAt($test, string $when, string $type, string $source = 'portal', array $extra = []): AttendancePunch
{
    $at = Carbon::parse($when);

    return AttendancePunch::query()->create(array_merge([
        'user_id' => $test->emp->id,
        'client_id' => $test->client->id,
        'attendance_date' => $at->toDateString(),
        'punched_at' => $at,
        'type' => $type,
        'source' => $source,
        'geo_status' => 'not_required',
    ], $extra));
}

function dayRecord($test, string $date): AttendanceRecord
{
    return app(AttendanceCalculator::class)->calculate($test->emp->fresh(), Carbon::parse($date), Carbon::parse('2026-03-20 12:00'));
}

/* ───────────────────────── geofence ───────────────────────── */

test('haversine distance is accurate over short ranges', function () {
    // ~11.1 m per 0.0001 degree of latitude
    $d = GeofenceService::distanceMeters(22.7196, 75.8577, 22.7197, 75.8577);

    expect($d)->toBeGreaterThan(10.5)->toBeLessThan(11.7);
});

test('geofence allows inside, blocks outside, low accuracy and missing location', function () {
    $this->office($this->client, 22.7196, 75.8577, 10);
    $svc = app(GeofenceService::class);

    expect($svc->evaluate($this->emp, 22.71961, 75.8577, 8, true))
        ->toMatchArray(['status' => 'inside', 'allowed' => true]);

    expect($svc->evaluate($this->emp, 22.7200, 75.8577, 8, true))
        ->toMatchArray(['status' => 'outside', 'allowed' => false]);

    expect($svc->evaluate($this->emp, 22.71961, 75.8577, 200, true))
        ->toMatchArray(['status' => 'low_accuracy', 'allowed' => false]);

    expect($svc->evaluate($this->emp, null, null, null, true))
        ->toMatchArray(['status' => 'unavailable', 'allowed' => false]);

    expect($svc->evaluate($this->emp, null, null, null, false))
        ->toMatchArray(['status' => 'not_required', 'allowed' => true]);
});

test('geofence only considers locations that belong to the employee', function () {
    $other = $this->client('OTH');
    $this->office($other, 22.7196, 75.8577, 10); // another client's office

    expect(app(GeofenceService::class)->evaluate($this->emp, 22.7196, 75.8577, 5, true))
        ->toMatchArray(['status' => 'no_location_configured', 'allowed' => false]);
});

/* ───────────────────────── punching ───────────────────────── */

test('a portal punch needs the geofence when the policy requires it', function () {
    $this->policy->update(['geofence_required' => true]);
    $this->office($this->client);
    Carbon::setTestNow('2026-03-02 09:00');

    expect(fn () => app(PunchService::class)->punch($this->emp, 'in', ['latitude' => 22.8, 'longitude' => 75.9, 'accuracy' => 5]))
        ->toThrow(PunchRejectedException::class);

    expect(AttendancePunch::count())->toBe(0);

    $punch = app(PunchService::class)->punch($this->emp, 'in', ['latitude' => 22.7196, 'longitude' => 75.8577, 'accuracy' => 5]);

    expect($punch->geo_status)->toBe('inside')->and($punch->source)->toBe('portal');
});

test('punch order is enforced and the server sets the time', function () {
    Carbon::setTestNow('2026-03-02 09:00');
    $svc = app(PunchService::class);

    expect(fn () => $svc->punch($this->emp, 'out'))->toThrow(PunchRejectedException::class, 'not punched in');

    $svc->punch($this->emp, 'in');
    expect(fn () => $svc->punch($this->emp, 'in'))->toThrow(PunchRejectedException::class, 'already punched in');

    Carbon::setTestNow('2026-03-02 13:00');
    $svc->punch($this->emp, 'break_start');
    expect(fn () => $svc->punch($this->emp, 'out'))->toThrow(PunchRejectedException::class, 'End your break');

    Carbon::setTestNow('2026-03-02 13:30');
    $svc->punch($this->emp, 'break_end');

    Carbon::setTestNow('2026-03-02 18:00');
    $out = $svc->punch($this->emp, 'out');

    expect($out->punched_at->format('H:i'))->toBe('18:00');

    $record = AttendanceRecord::first();
    expect($record->status)->toBe('present')->and($record->break_minutes)->toBe(30)->and($record->worked_minutes)->toBe(540);
});

test('an overnight shift keeps the out punch on the day the shift started', function () {
    $night = $this->shift($this->client, '22:00', '06:00', true);
    $emp = $this->employee($this->client, [], $night);
    $svc = app(PunchService::class);

    Carbon::setTestNow('2026-03-02 21:55');
    $svc->punch($emp, 'in');

    Carbon::setTestNow('2026-03-03 06:05');
    $out = $svc->punch($emp, 'out');

    expect($out->attendance_date->toDateString())->toBe('2026-03-02');

    $record = AttendanceRecord::where('user_id', $emp->id)->first();
    expect($record->attendance_date->toDateString())->toBe('2026-03-02')
        ->and($record->span_minutes)->toBe(490)
        ->and($record->scheduled_minutes)->toBe(480);
});

/* ───────────────────────── daily calculation ───────────────────────── */

test('a full day inside grace is present and on time', function () {
    punchAt($this, '2026-03-03 09:08', 'in');
    punchAt($this, '2026-03-03 18:05', 'out');

    $r = dayRecord($this, '2026-03-03');

    expect($r->status)->toBe('present')->and($r->payable_fraction)->toBe(1.0)
        ->and($r->is_late)->toBeFalse()->and($r->late_minutes)->toBe(0)
        ->and($r->policy_id)->toBe($this->policy->id)
        ->and($r->policy_snapshot['full_day_minutes'])->toBe(480);
});

test('arriving after grace is late and leaving early is flagged', function () {
    punchAt($this, '2026-03-03 09:25', 'in');
    punchAt($this, '2026-03-03 17:30', 'out');

    $r = dayRecord($this, '2026-03-03');

    expect($r->is_late)->toBeTrue()->and($r->late_minutes)->toBe(25)
        ->and($r->is_early)->toBeTrue()->and($r->early_minutes)->toBe(30);
});

test('short days become half day or absent by policy thresholds', function () {
    punchAt($this, '2026-03-03 09:00', 'in');
    punchAt($this, '2026-03-03 13:30', 'out'); // 4h30m, break 0 -> half day
    expect(dayRecord($this, '2026-03-03'))->status->toBe('half_day')->payable_fraction->toBe(0.5);

    punchAt($this, '2026-03-04 09:00', 'in');
    punchAt($this, '2026-03-04 11:00', 'out'); // 2h -> below half
    $r = dayRecord($this, '2026-03-04');
    expect($r->status)->toBe('absent')->and($r->hasFlag('short_hours'))->toBeTrue();
});

test('break time beyond the paid allowance is deducted from worked time', function () {
    punchAt($this, '2026-03-03 09:00', 'in');
    punchAt($this, '2026-03-03 13:00', 'break_start');
    punchAt($this, '2026-03-03 14:15', 'break_end'); // 75 min, paid allowance 30
    punchAt($this, '2026-03-03 19:00', 'out');       // span 600

    $r = dayRecord($this, '2026-03-03');

    expect($r->break_minutes)->toBe(75)->and($r->worked_minutes)->toBe(600 - 45)
        ->and($r->hasFlag('break_exceeded'))->toBeTrue();
});

test('overtime counts only when the policy allows and approval is respected', function () {
    $this->policy->update(['overtime_eligible' => true, 'overtime_min_minutes' => 30, 'overtime_requires_approval' => true]);

    punchAt($this, '2026-03-03 09:00', 'in');
    punchAt($this, '2026-03-03 20:30', 'out'); // 11h30 span, 30m paid break not taken -> worked 690

    $r = dayRecord($this, '2026-03-03');
    expect($r->overtime_minutes)->toBe(690 - 480)->and($r->overtime_approved)->toBeFalse();

    $this->policy->update(['overtime_requires_approval' => false]);
    expect(dayRecord($this, '2026-03-03')->overtime_approved)->toBeTrue();
});

test('days without punches are absent, weekly off, holiday or pending as appropriate', function () {
    Holiday::create(['client_id' => $this->client->id, 'date' => '2026-03-04', 'name' => 'Local holiday']);

    expect(dayRecord($this, '2026-03-03'))->status->toBe('absent')->payable_fraction->toBe(0.0);
    expect(dayRecord($this, '2026-03-04'))->status->toBe('holiday')->payable_fraction->toBe(1.0);
    expect(dayRecord($this, '2026-03-08'))->status->toBe('weekly_off')->payable_fraction->toBe(1.0); // Sunday
    expect(dayRecord($this, '2026-03-25'))->status->toBe('pending');                                  // future
});

test('a missing punch-out is an exception that needs review, not a silent absence', function () {
    punchAt($this, '2026-03-03 09:00', 'in');

    $r = dayRecord($this, '2026-03-03');

    expect($r->status)->toBe('incomplete')->and($r->hasFlag('missing_out'))->toBeTrue()
        ->and($r->review_status)->toBe('needs_review')->and($r->payable_fraction)->toBe(0.0);

    $this->policy->update(['missing_punch_treatment' => 'half_day']);
    expect(dayRecord($this, '2026-03-03')->payable_fraction)->toBe(0.5);
});

test('today is not an exception while the employee is still working', function () {
    Carbon::setTestNow('2026-03-03 12:00');
    punchAt($this, '2026-03-03 09:00', 'in');

    $r = app(AttendanceCalculator::class)->calculate($this->emp, Carbon::parse('2026-03-03'));

    expect($r->status)->toBe('pending')->and($r->review_status)->toBe('ok');
});

test('machine and portal punches that disagree are flagged and the policy priority wins', function () {
    punchAt($this, '2026-03-03 09:00', 'in', 'portal');
    punchAt($this, '2026-03-03 09:45', 'in', 'machine');
    punchAt($this, '2026-03-03 18:00', 'out', 'machine');

    $r = dayRecord($this, '2026-03-03');

    expect($r->hasFlag('source_conflict'))->toBeTrue()
        ->and($r->first_in->format('H:i'))->toBe('09:45') // machine has priority
        ->and($r->review_status)->toBe('needs_review');
});

test('a policy can require both portal and machine punches', function () {
    $this->policy->update(['require_machine_punch' => true]);
    punchAt($this, '2026-03-03 09:00', 'in', 'portal');
    punchAt($this, '2026-03-03 18:00', 'out', 'portal');

    expect(dayRecord($this, '2026-03-03'))->hasFlag('missing_machine')->toBeTrue();
});

test('approved leave marks the day as leave and unpaid leave pays nothing', function () {
    $paid = LeaveType::create(['name' => 'Casual', 'code' => 'CL', 'is_paid' => true]);
    $unpaid = LeaveType::create(['name' => 'LWP', 'code' => 'LWP', 'is_paid' => false]);

    foreach ([['2026-03-03', $paid], ['2026-03-04', $unpaid]] as [$date, $type]) {
        LeaveApplication::create([
            'user_id' => $this->emp->id, 'client_id' => $this->client->id, 'leave_type_id' => $type->id,
            'start_date' => $date, 'end_date' => $date, 'total_days' => 1, 'reason' => 'x', 'status' => 'approved',
        ]);
    }

    expect(dayRecord($this, '2026-03-03'))->status->toBe('leave')->payable_fraction->toBe(1.0)->leave_is_paid->toBeTrue();
    expect(dayRecord($this, '2026-03-04'))->status->toBe('leave')->payable_fraction->toBe(0.0)->leave_is_paid->toBeFalse();
});

test('another client policy never changes this employee', function () {
    $other = $this->client('OTH');
    $this->policy($other, ['full_day_minutes' => 600, 'half_day_minutes' => 300, 'weekly_offs' => [6, 7]]);

    punchAt($this, '2026-03-03 09:00', 'in');
    punchAt($this, '2026-03-03 18:00', 'out');

    expect(dayRecord($this, '2026-03-03'))->status->toBe('present')->policy_id->toBe($this->policy->id);
});

test('a locked day is never recalculated and later policy edits do not touch it', function () {
    punchAt($this, '2026-03-03 09:00', 'in');
    punchAt($this, '2026-03-03 18:00', 'out');

    $record = dayRecord($this, '2026-03-03');
    $record->update(['locked_at' => now(), 'locked_run_id' => 1]);

    $this->policy->update(['full_day_minutes' => 700]);
    $again = dayRecord($this, '2026-03-03');

    expect($again->status)->toBe('present')->and($again->policy_snapshot['full_day_minutes'])->toBe(480);
});

test('the policy that was effective on the day is the one applied', function () {
    $this->policy->update(['effective_to' => '2026-03-10']);
    $newer = $this->policy($this->client, ['name' => 'New', 'effective_from' => '2026-03-11', 'full_day_minutes' => 540, 'half_day_minutes' => 270]);

    punchAt($this, '2026-03-09 09:00', 'in');
    punchAt($this, '2026-03-09 18:00', 'out');
    punchAt($this, '2026-03-12 09:00', 'in');
    punchAt($this, '2026-03-12 18:00', 'out');

    expect(dayRecord($this, '2026-03-09'))->policy_id->toBe($this->policy->id)->status->toBe('present');
    // 9h span but 30 min of paid break is not taken: worked 540 -> exactly the new full day
    expect(dayRecord($this, '2026-03-12'))->policy_id->toBe($newer->id)->status->toBe('present');
});

/* ───────────────────────── corrections ───────────────────────── */

test('an approved correction adds manual punches and keeps the originals', function () {
    $manager = $this->employee($this->client, ['first_name' => 'Mona'], null, 'hr_recruiter');
    punchAt($this, '2026-03-03 09:00', 'in');

    $before = dayRecord($this, '2026-03-03');
    expect($before->status)->toBe('incomplete');

    Carbon::setTestNow('2026-03-05 10:00');
    $svc = app(CorrectionService::class);
    $request = $svc->request($this->emp, Carbon::parse('2026-03-03'), null, Carbon::parse('2026-03-03 18:10'), 'Forgot to punch out', $this->emp);

    expect($request->status)->toBe('pending')->and($request->original_snapshot['punches'])->toHaveCount(1);

    $svc->approve($request, $manager, 'ok');

    $record = AttendanceRecord::where('user_id', $this->emp->id)->whereDate('attendance_date', '2026-03-03')->first();

    expect($record->status)->toBe('present')
        ->and($record->last_out->format('H:i'))->toBe('18:10')
        ->and($record->review_status)->toBe('ok')
        ->and(AttendancePunch::where('user_id', $this->emp->id)->where('source', 'portal')->count())->toBe(1)
        ->and(AttendancePunch::where('source', 'manual')->where('correction_id', $request->id)->count())->toBe(1);

    expect(fn () => $svc->approve($request->fresh(), $manager))->toThrow(InvalidArgumentException::class);
    expect(\App\Models\AuditLog::where('event', 'attendance_correction.approved')->count())->toBe(1);
});

test('corrections are refused for locked days, future dates and duplicates', function () {
    Carbon::setTestNow('2026-03-10 10:00');
    $svc = app(CorrectionService::class);

    expect(fn () => $svc->request($this->emp, Carbon::parse('2026-03-12'), Carbon::parse('2026-03-12 09:00'), null, 'x', $this->emp))
        ->toThrow(InvalidArgumentException::class, 'future');

    $record = dayRecord($this, '2026-03-03');
    $record->update(['locked_at' => now()]);

    expect(fn () => $svc->request($this->emp, Carbon::parse('2026-03-03'), Carbon::parse('2026-03-03 09:00'), null, 'x', $this->emp))
        ->toThrow(InvalidArgumentException::class, 'locked');

    $svc->request($this->emp, Carbon::parse('2026-03-04'), Carbon::parse('2026-03-04 09:00'), Carbon::parse('2026-03-04 18:00'), 'x', $this->emp);
    expect(fn () => $svc->request($this->emp, Carbon::parse('2026-03-04'), Carbon::parse('2026-03-04 09:00'), null, 'again', $this->emp))
        ->toThrow(InvalidArgumentException::class, 'pending');
});

test('voiding a punch keeps the row and recalculates the day', function () {
    $in = punchAt($this, '2026-03-03 09:00', 'in');
    punchAt($this, '2026-03-03 18:00', 'out');
    $hr = $this->employee($this->client, [], null, 'hr_recruiter');

    expect(dayRecord($this, '2026-03-03')->status)->toBe('present');

    app(PunchService::class)->void($in, $hr, 'Wrong device');

    expect(AttendancePunch::find($in->id)->voided_at)->not->toBeNull();
    expect(AttendanceRecord::where('user_id', $this->emp->id)->first()->status)->toBe('incomplete');
});

/* ───────────────────────── machine import ───────────────────────── */

test('machine import creates punches once, reports unmatched ids and never duplicates', function () {
    $csv = tempnam(sys_get_temp_dir(), 'punch');
    file_put_contents($csv, implode("\n", [
        'Employee ID,Punched At,Direction,Device ID',
        $this->emp->employee_id . ',2026-03-03 09:02:00,in,D1',
        $this->emp->employee_id . ',2026-03-03 18:01:00,out,D1',
        'GHOST99,2026-03-03 09:00:00,in,D1',
        $this->emp->employee_id . ',not a date,in,D1',
    ]));

    $importer = app(MachinePunchImporter::class);
    $batch = $importer->import($csv, 'punches.csv', null);

    expect($batch->imported_rows)->toBe(2)->and($batch->unmatched_rows)->toBe(1)->and($batch->error_rows)->toBe(1)
        ->and($batch->details['unmatched_employee_ids'])->toBe(['GHOST99']);

    $again = $importer->import($csv, 'punches.csv', null);
    expect($again->imported_rows)->toBe(0)->and($again->duplicate_rows)->toBe(2);

    expect(AttendancePunch::where('source', 'machine')->count())->toBe(2);
    expect(AttendanceRecord::where('user_id', $this->emp->id)->first())->status->toBe('present');
});

test('machine import infers in/out when the device sends no direction', function () {
    $csv = tempnam(sys_get_temp_dir(), 'punch');
    file_put_contents($csv, implode("\n", [
        'employee_id,date,time',
        $this->emp->employee_id . ',2026-03-03,09:00:00',
        $this->emp->employee_id . ',2026-03-03,18:00:00',
    ]));

    app(MachinePunchImporter::class)->import($csv, 'x.csv', null);

    expect(AttendancePunch::orderBy('punched_at')->pluck('type')->all())->toBe(['in', 'out']);
});
