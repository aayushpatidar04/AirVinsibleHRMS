<?php

namespace App\Services\Workforce;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\User;
use App\Notifications\WorkforceAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Payroll run lifecycle:
 *
 *   draft -> calculated -> pending_review -> pending_approval -> approved
 *         -> payslips_published -> closed
 *
 * Each step needs its own permission, approval locks the attendance it used
 * and generates (hidden) payslips, and nothing after approval can be edited:
 * a mistake goes back through "reopen" (before publishing) or into the next
 * period as an arrears adjustment.
 */
class PayrollRunService
{
    /** action => [from statuses, to status, permission, label] */
    public const ACTIONS = [
        'submit' => [[PayrollRun::CALCULATED], PayrollRun::PENDING_REVIEW, 'payroll.generate', 'Submit for review'],
        'review' => [[PayrollRun::PENDING_REVIEW], PayrollRun::PENDING_APPROVAL, 'payroll.review', 'Complete review'],
        'send_back' => [[PayrollRun::PENDING_REVIEW, PayrollRun::PENDING_APPROVAL], PayrollRun::CALCULATED, 'payroll.review', 'Send back'],
        'approve' => [[PayrollRun::PENDING_APPROVAL], PayrollRun::APPROVED, 'payroll.approve', 'Approve payroll'],
        'publish' => [[PayrollRun::APPROVED], PayrollRun::PUBLISHED, 'payroll.release', 'Publish payslips'],
        'close' => [[PayrollRun::PUBLISHED], PayrollRun::CLOSED, 'payroll.lock', 'Close payroll'],
        'reopen' => [[PayrollRun::APPROVED], PayrollRun::CALCULATED, 'payroll.lock', 'Reopen'],
    ];

    public function __construct(
        private readonly PayrollEngine $engine,
        private readonly PayslipService $payslips,
        private readonly AttendanceCalculator $calculator,
    ) {
    }

    /* ───────────────────────── creating & calculating ───────────────────────── */

    public function create(PayrollPeriod $period, User $by): PayrollRun
    {
        $open = PayrollRun::query()
            ->where('payroll_period_id', $period->id)
            ->where('status', '!=', PayrollRun::CLOSED)
            ->exists();

        if ($open) {
            throw new PayrollException('This period already has a payroll run that is not closed.');
        }

        $sequence = PayrollRun::query()->where('payroll_period_id', $period->id)->count() + 1;
        $code = strtoupper($period->client?->code ?? 'CL' . $period->client_id);

        $run = PayrollRun::create([
            'client_id' => $period->client_id,
            'payroll_period_id' => $period->id,
            'run_number' => sprintf('PR-%s-%s-%02d', $period->start_date->format('Ym'), $code, $sequence),
            'status' => PayrollRun::DRAFT,
            'created_by' => $by->id,
        ]);

        AuditLog::record('payroll.run_created', $run, [], ['period' => $period->name, 'client_id' => $period->client_id], $by->id);

        return $run;
    }

    /** Employees paid in a run: this client's active staff plus leavers who worked in the period. */
    public function employees(PayrollRun $run): Collection
    {
        $period = $run->period;

        $withAttendance = AttendanceRecord::query()
            ->whereDate('attendance_date', '>=', $period->start_date->toDateString())->whereDate('attendance_date', '<=', $period->end_date->toDateString())
            ->where('client_id', $run->client_id)
            ->where('payable_fraction', '>', 0)
            ->pluck('user_id');

        return User::query()
            ->where('client_id', $run->client_id)
            ->whereNotNull('employee_id')
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $period->end_date->toDateString()))
            ->where(fn ($q) => $q->where('employment_status', 'active')->orWhereIn('id', $withAttendance))
            ->orderBy('employee_id')
            ->get();
    }

    public function calculate(PayrollRun $run, User $by): PayrollRun
    {
        if (!$run->isEditable()) {
            throw new PayrollException('This payroll run is ' . strtolower($run->label()) . ' and can no longer be recalculated.');
        }

        $period = $run->period;
        $settings = PayrollSetting::forClient($run->client_id, $period->end_date);

        $held = $run->items()->where('status', 'held')->pluck('hold_reason', 'user_id');
        $adjustments = PayrollAdjustment::query()->active()->where('payroll_run_id', $run->id)->get()->groupBy('user_id');

        DB::transaction(function () use ($run, $by, $period, $settings, $held, $adjustments) {
            $run->items()->delete();

            foreach ($this->employees($run) as $employee) {
                $attributes = $this->engine->compute($employee, $period, $settings, $adjustments->get($employee->id));

                $item = new PayrollRunItem($attributes);
                $item->payroll_run_id = $run->id;

                if ($held->has($employee->id)) {
                    $item->status = 'held';
                    $item->hold_reason = $held[$employee->id];
                }

                $item->save();
            }

            $this->refreshTotals($run);

            $run->update([
                'status' => PayrollRun::CALCULATED,
                'version' => $run->version + 1,
                'calculated_by' => $by->id,
                'calculated_at' => now(),
                'submitted_by' => null,
                'submitted_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'settings_snapshot' => [
                    'payroll_settings' => $settings->only([
                        'effective_from', 'calculation_method', 'divisor_mode', 'fixed_divisor', 'rounding', 'precision',
                        'overtime_base', 'block_on_pending_attendance', 'block_on_pending_leave', 'separate_reviewer',
                        'pf_enabled', 'pf_wage_ceiling', 'esic_enabled', 'esic_gross_ceiling',
                    ]),
                    'attendance_policy_ids' => $run->items()->get()->pluck('attendance_summary.policy_id')->filter()->unique()->values()->all(),
                ],
            ]);
        });

        AuditLog::record('payroll.run_calculated', $run, [], ['version' => $run->version, 'employees' => $run->employee_count], $by->id);

        return $run->refresh();
    }

    /** Recomputes a single employee after an adjustment or hold change. */
    public function recalculateEmployee(PayrollRun $run, int $userId): void
    {
        if (!$run->isEditable()) {
            throw new PayrollException('This payroll run can no longer be changed.');
        }

        $employee = User::findOrFail($userId);
        $settings = PayrollSetting::forClient($run->client_id, $run->period->end_date);
        $adjustments = PayrollAdjustment::query()->active()->where('payroll_run_id', $run->id)->where('user_id', $userId)->get();

        $existing = $run->items()->where('user_id', $userId)->first();
        $attributes = $this->engine->compute($employee, $run->period, $settings, $adjustments);

        $item = $existing ?? new PayrollRunItem(['payroll_run_id' => $run->id]);
        $item->fill($attributes);
        $item->payroll_run_id = $run->id;
        $item->save();

        $this->refreshTotals($run);

        if ($run->status === PayrollRun::PENDING_REVIEW) {
            $run->update(['status' => PayrollRun::CALCULATED, 'submitted_by' => null, 'submitted_at' => null]);
        }
    }

    /* ───────────────────────── workflow ───────────────────────── */

    public function transition(PayrollRun $run, string $action, User $by, ?string $note = null): PayrollRun
    {
        if (!isset(self::ACTIONS[$action])) {
            throw new PayrollException('Unknown payroll action.');
        }

        [$from, $to, $permission] = self::ACTIONS[$action];

        if (!$by->can($permission)) {
            throw new PayrollException('You do not have permission to do this.');
        }

        if (!in_array($run->status, $from, true)) {
            throw new PayrollException('This run is ' . strtolower($run->label()) . '; "' . self::ACTIONS[$action][3] . '" is not available now.');
        }

        if (in_array($action, ['send_back', 'reopen'], true) && blank($note)) {
            throw new PayrollException('A reason is required.');
        }

        $this->guardSeparation($run, $action, $by);

        if (in_array($action, ['submit', 'review', 'approve'], true)) {
            $this->assertNoBlockingExceptions($run);
        }

        $before = $run->status;

        DB::transaction(function () use ($run, $action, $by, $note, $to) {
            match ($action) {
                'submit' => $run->update(['status' => $to, 'submitted_by' => $by->id, 'submitted_at' => now()]),
                'review' => $run->update(['status' => $to, 'reviewed_by' => $by->id, 'reviewed_at' => now()]),
                'send_back' => $run->update(['status' => $to, 'submitted_by' => null, 'submitted_at' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'notes' => $note]),
                'approve' => $this->approve($run, $by),
                'publish' => $this->publish($run, $by),
                'close' => $this->close($run, $by),
                'reopen' => $this->reopen($run, $by),
            };
        });

        AuditLog::record('payroll.' . $action, $run, ['status' => $before], ['status' => $run->refresh()->status, 'note' => $note], $by->id);

        $this->notify($run, $action, $by);

        return $run;
    }

    public function hold(PayrollRunItem $item, bool $hold, ?string $reason, User $by): PayrollRunItem
    {
        $run = $item->run;

        if (!$run->isEditable()) {
            throw new PayrollException('This payroll run can no longer be changed.');
        }

        if ($hold && blank($reason)) {
            throw new PayrollException('Enter a reason for holding this employee.');
        }

        $item->update(['status' => $hold ? 'held' : 'calculated', 'hold_reason' => $hold ? $reason : null]);
        $this->refreshTotals($run);

        AuditLog::record($hold ? 'payroll.employee_held' : 'payroll.employee_released', $item, [], ['reason' => $reason], $by->id);

        return $item->refresh();
    }

    public function addAdjustment(PayrollRun $run, User $employee, array $data, User $by): PayrollAdjustment
    {
        if (!$run->isEditable()) {
            throw new PayrollException('Adjustments can only be added before approval. Reopen the run or use the next period for arrears.');
        }

        if (!$run->items()->where('user_id', $employee->id)->exists()) {
            throw new PayrollException('This employee is not part of the run.');
        }

        $adjustment = PayrollAdjustment::create([
            'payroll_run_id' => $run->id,
            'user_id' => $employee->id,
            'type' => $data['type'],
            'label' => $data['label'],
            'amount' => abs((float) $data['amount']),
            'is_deduction' => $data['type'] === 'deduction',
            'reason' => $data['reason'],
            'created_by' => $by->id,
        ]);

        AuditLog::record('payroll.adjustment_added', $adjustment, [], $adjustment->only(['user_id', 'type', 'label', 'amount', 'reason']), $by->id);

        $this->recalculateEmployee($run, $employee->id);

        return $adjustment;
    }

    public function voidAdjustment(PayrollAdjustment $adjustment, User $by): void
    {
        $run = $adjustment->run;

        if (!$run->isEditable()) {
            throw new PayrollException('This payroll run can no longer be changed.');
        }

        $adjustment->update(['voided_at' => now(), 'voided_by' => $by->id]);

        AuditLog::record('payroll.adjustment_voided', $adjustment, [], ['by' => $by->id], $by->id);

        $this->recalculateEmployee($run, $adjustment->user_id);
    }

    /* ───────────────────────── step side effects ───────────────────────── */

    private function approve(PayrollRun $run, User $by): void
    {
        $period = $run->period;
        $userIds = $run->items()->where('status', '!=', 'held')->pluck('user_id');

        // Attendance used for this payroll is frozen until the run is reopened.
        AttendanceRecord::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('attendance_date', '>=', $period->start_date->toDateString())->whereDate('attendance_date', '<=', $period->end_date->toDateString())
            ->update(['locked_at' => now(), 'locked_run_id' => $run->id]);

        $run->update(['status' => PayrollRun::APPROVED, 'approved_by' => $by->id, 'approved_at' => now()]);

        $this->payslips->generate($run);
    }

    private function publish(PayrollRun $run, User $by): void
    {
        $now = now();

        Payslip::query()
            ->where('payroll_run_id', $run->id)
            ->whereNull('voided_at')
            ->update(['published_at' => $now, 'published_by' => $by->id]);

        $run->update(['status' => PayrollRun::PUBLISHED, 'published_by' => $by->id, 'published_at' => $now]);
    }

    private function close(PayrollRun $run, User $by): void
    {
        $run->update(['status' => PayrollRun::CLOSED, 'closed_by' => $by->id, 'closed_at' => now()]);
        $run->period->update(['status' => 'closed']);
    }

    private function reopen(PayrollRun $run, User $by): void
    {
        AttendanceRecord::query()->where('locked_run_id', $run->id)->update(['locked_at' => null, 'locked_run_id' => null]);

        // Unpublished payslips are voided, not deleted, so the audit trail keeps them.
        Payslip::query()->where('payroll_run_id', $run->id)->whereNull('voided_at')->update(['voided_at' => now()]);

        $run->update([
            'status' => PayrollRun::CALCULATED,
            'approved_by' => null, 'approved_at' => null,
            'reviewed_by' => null, 'reviewed_at' => null,
            'submitted_by' => null, 'submitted_at' => null,
        ]);
    }

    /* ───────────────────────── guards & totals ───────────────────────── */

    public function blockingCount(PayrollRun $run): int
    {
        return $run->items()->where('status', '!=', 'held')->get()->filter->hasBlockingExceptions()->count();
    }

    private function assertNoBlockingExceptions(PayrollRun $run): void
    {
        $count = $this->blockingCount($run);

        if ($count > 0) {
            throw new PayrollException($count . ' employee(s) have unresolved exceptions. Fix them and recalculate, or hold those employees.');
        }

        if ($run->items()->where('status', '!=', 'held')->doesntExist()) {
            throw new PayrollException('There are no employees in this run.');
        }
    }

    /** With separate reviewers enabled, the person who prepared a run cannot also review or approve it. */
    private function guardSeparation(PayrollRun $run, string $action, User $by): void
    {
        $settings = PayrollSetting::forClient($run->client_id, $run->period->end_date);

        if (!$settings->separate_reviewer || !in_array($action, ['review', 'approve'], true)) {
            return;
        }

        $preparers = array_filter([$run->calculated_by, $run->submitted_by]);

        if ($action === 'approve') {
            $preparers[] = $run->reviewed_by;
        }

        if (in_array($by->id, array_filter($preparers), true)) {
            throw new PayrollException('Separation of duties is on: someone else must ' . ($action === 'review' ? 'review' : 'approve') . ' this run.');
        }
    }

    public function refreshTotals(PayrollRun $run): void
    {
        $items = $run->items()->get()->reject->isHeld();

        $run->update([
            'employee_count' => $run->items()->count(),
            'exception_count' => $items->filter->hasBlockingExceptions()->count(),
            'total_gross' => round($items->sum('gross_earnings'), 2),
            'total_deductions' => round($items->sum('total_deductions'), 2),
            'total_net' => round($items->sum('net_pay'), 2),
            'total_employer_cost' => round($items->sum('gross_earnings') + $items->sum('employer_contributions'), 2),
        ]);
    }

    private function notify(PayrollRun $run, string $action, User $by): void
    {
        $label = $run->period->name . ' (' . $run->client->name . ')';

        $permission = match ($action) {
            'submit' => 'payroll.review',
            'review' => 'payroll.approve',
            'approve' => 'payroll.release',
            default => null,
        };

        if ($permission) {
            foreach (User::permission($permission)->where('id', '!=', $by->id)->get() as $user) {
                $user->notify(new WorkforceAlert('payroll', 'Payroll ' . $label . ' is waiting for your action.', route('workforce.payroll.runs.show', $run)));
            }
        }

        if ($action === 'publish') {
            $userIds = Payslip::query()->where('payroll_run_id', $run->id)->whereNull('voided_at')->pluck('user_id');

            foreach (User::whereIn('id', $userIds)->get() as $employee) {
                $employee->notify(new WorkforceAlert('payroll', 'Your payslip for ' . $run->period->name . ' is now available.', route('employee.payslips')));
            }
        }
    }
}
