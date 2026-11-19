<?php

namespace App\Services\Workforce;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use App\Models\LeaveLedgerEntry;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\WorkforceAlert;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Leave rules, balances and approvals.
 *
 * Balances are never stored as a number that can drift: they are the sum of
 * the leave ledger (accruals, debits, reversals, adjustments, lapses), so
 * every change is traceable to a reason and a person.
 */
class LeaveService
{
    public function __construct(
        private readonly PolicyResolver $resolver,
        private readonly AttendanceCalculator $calculator,
    ) {
    }

    /* ───────────────────────── balances ───────────────────────── */

    public function balance(User $user, int $leaveTypeId, ?CarbonInterface $asOf = null): float
    {
        return round((float) LeaveLedgerEntry::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->when($asOf, fn ($q) => $q->whereDate('effective_date', '<=', $asOf->toDateString()))
            ->sum('days'), 2);
    }

    public function pendingDays(User $user, int $leaveTypeId): float
    {
        return round((float) LeaveApplication::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', LeaveApplication::PENDING)
            ->sum('total_days'), 2);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function balances(User $user, ?CarbonInterface $on = null): Collection
    {
        $on = $on ?? today();

        return LeaveType::query()->active()->forClient($user->client_id)->orderBy('name')->get()
            ->map(function (LeaveType $type) use ($user, $on) {
                $policy = $this->resolver->leavePolicy($user, $type->id, $on);
                $balance = $this->balance($user, $type->id);
                $pending = $this->pendingDays($user, $type->id);

                return [
                    'leave_type_id' => $type->id,
                    'name' => $type->name,
                    'code' => $type->code,
                    'is_paid' => $type->is_paid,
                    'has_policy' => (bool) $policy,
                    'balance' => $balance,
                    'pending' => $pending,
                    'available' => round($balance - $pending, 2),
                    'allow_half_day' => $policy?->allow_half_day ?? false,
                ];
            })
            ->filter(fn ($row) => $row['has_policy'])
            ->values();
    }

    /* ───────────────────────── applying ───────────────────────── */

    /**
     * Counts the days a request consumes, skipping weekly offs and holidays
     * unless the policy says they count.
     */
    public function countDays(User $user, LeavePolicy $policy, CarbonInterface $start, CarbonInterface $end, bool $halfDay): float
    {
        if ($halfDay) {
            return 0.5;
        }

        $holidays = $this->resolver->holidaysBetween($user, $start, $end);
        $days = 0.0;
        $day = Carbon::instance($start)->startOfDay();
        $last = Carbon::instance($end)->startOfDay();

        while ($day->lte($last)) {
            $skip = (!$policy->count_weekly_offs && $this->resolver->isWeeklyOff($user, $day))
                || (!$policy->count_holidays && $holidays->has($day->toDateString()));

            if (!$skip) {
                $days += 1;
            }

            $day->addDay();
        }

        return $days;
    }

    /**
     * Checks a request against the policy and returns what it would consume.
     *
     * @return array{policy: LeavePolicy, type: LeaveType, days: float}
     */
    public function validate(
        User $user,
        int $leaveTypeId,
        CarbonInterface $start,
        CarbonInterface $end,
        bool $halfDay = false,
        ?int $ignoreApplicationId = null,
    ): array {
        $start = Carbon::instance($start)->startOfDay();
        $end = Carbon::instance($end)->startOfDay();

        $type = LeaveType::query()->active()->forClient($user->client_id)->find($leaveTypeId);

        if (!$type) {
            throw new LeaveException('This leave type is not available to you.');
        }

        $policy = $this->resolver->leavePolicy($user, $type->id, $start);

        if (!$policy) {
            throw new LeaveException('No leave policy applies to you for ' . $type->name . ' on those dates.');
        }

        if ($end->lt($start)) {
            throw new LeaveException('The end date cannot be before the start date.');
        }

        if ($halfDay && (!$policy->allow_half_day || !$start->eq($end))) {
            throw new LeaveException($policy->allow_half_day
                ? 'A half-day leave must be for a single date.'
                : $type->name . ' cannot be taken as a half day.');
        }

        if ($user->date_of_joining) {
            $joined = Carbon::instance($user->date_of_joining)->startOfDay();

            if ($start->lt($joined)) {
                throw new LeaveException('You cannot apply for leave before your joining date.');
            }

            if ($policy->eligible_after_days > 0 && $start->lt($joined->copy()->addDays($policy->eligible_after_days))) {
                throw new LeaveException('You become eligible for ' . $type->name . ' after ' . $policy->eligible_after_days . ' days of service.');
            }

            if (!$policy->allow_during_probation && $policy->probation_days > 0
                && $start->lt($joined->copy()->addDays($policy->probation_days))) {
                throw new LeaveException($type->name . ' is not available during probation.');
            }
        }

        if ($policy->min_notice_days > 0 && $start->lt(today()->addDays($policy->min_notice_days))) {
            throw new LeaveException($type->name . ' needs at least ' . $policy->min_notice_days . ' day(s) notice.');
        }

        $days = $this->countDays($user, $policy, $start, $end, $halfDay);

        if ($days <= 0) {
            throw new LeaveException('Those dates are weekly offs or holidays, so no leave is needed.');
        }

        if ($policy->max_consecutive_days !== null && $days > $policy->max_consecutive_days) {
            throw new LeaveException('You can take at most ' . $this->trim($policy->max_consecutive_days) . ' consecutive day(s) of ' . $type->name . '.');
        }

        $overlap = LeaveApplication::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [LeaveApplication::PENDING, LeaveApplication::APPROVED])
            ->overlapping($start->toDateString(), $end->toDateString())
            ->when($ignoreApplicationId, fn ($q) => $q->where('id', '!=', $ignoreApplicationId))
            ->exists();

        if ($overlap) {
            throw new LeaveException('You already have a leave request covering some of these dates.');
        }

        if ($this->hasLockedDays($user, $start, $end)) {
            throw new LeaveException('Payroll for part of this period is already approved. Please contact HR.');
        }

        // Unpaid types never need a balance; paid types must stay within it.
        if ($type->is_paid && $policy->accrual_frequency !== 'none') {
            $available = $this->balance($user, $type->id) - $this->pendingDays($user, $type->id);
            $floor = $policy->allow_negative ? -1 * $policy->max_negative_days : 0;

            if ($available - $days < $floor - 0.001) {
                throw new LeaveException(sprintf(
                    'Insufficient %s balance: %s day(s) available, %s requested.',
                    $type->name,
                    $this->trim(max(0, $available)),
                    $this->trim($days)
                ));
            }
        }

        return ['policy' => $policy, 'type' => $type, 'days' => $days];
    }

    public function apply(
        User $user,
        int $leaveTypeId,
        CarbonInterface $start,
        CarbonInterface $end,
        string $reason,
        bool $halfDay = false,
        ?string $session = null,
    ): LeaveApplication {
        $checked = $this->validate($user, $leaveTypeId, $start, $end, $halfDay);

        $application = DB::transaction(function () use ($user, $checked, $start, $end, $reason, $halfDay, $session) {
            $application = LeaveApplication::create([
                'user_id' => $user->id,
                'client_id' => $user->client_id,
                'leave_type_id' => $checked['type']->id,
                'policy_id' => $checked['policy']->id,
                'start_date' => Carbon::instance($start)->toDateString(),
                'end_date' => Carbon::instance($end)->toDateString(),
                'is_half_day' => $halfDay,
                'half_day_session' => $halfDay ? ($session ?: 'first_half') : null,
                'total_days' => $checked['days'],
                'reason' => $reason,
                'status' => LeaveApplication::PENDING,
                'current_level' => 1,
            ]);

            foreach ($checked['policy']->levels() as $index => $role) {
                LeaveApproval::create([
                    'leave_application_id' => $application->id,
                    'level' => $index + 1,
                    'approver_role' => $role,
                ]);
            }

            AuditLog::record('leave.applied', $application, [], $application->only(['leave_type_id', 'start_date', 'end_date', 'total_days']), $user->id);

            return $application;
        });

        $this->notifyCurrentApprovers($application->fresh(['user', 'approvals', 'leaveType']));

        return $application;
    }

    /* ───────────────────────── deciding ───────────────────────── */

    public function canDecide(User $by, LeaveApplication $application): bool
    {
        if (!$application->isPending() || $by->id === $application->user_id) {
            return false;
        }

        $approval = $this->currentApproval($application);

        if (!$approval) {
            return false;
        }

        if ($by->hasRole('admin')) {
            return true;
        }

        $employee = $application->user;

        if ($approval->approver_role === 'reporting_manager' && $employee->reporting_to === $by->id) {
            return true;
        }

        // HR (or anyone granted leave approval) within their scope may act at any level.
        return $by->can('leaves.approve') && WorkforceScope::canSee($by, $employee, 'leaves');
    }

    public function approve(LeaveApplication $application, User $by, ?string $note = null): LeaveApplication
    {
        $this->guardDecision($application, $by);

        DB::transaction(function () use ($application, $by, $note) {
            $approval = $this->currentApproval($application);
            $approval->update(['status' => 'approved', 'acted_by' => $by->id, 'acted_at' => now(), 'note' => $note]);

            $hasNext = $application->approvals()->where('status', 'pending')->exists();

            if ($hasNext) {
                $application->update(['current_level' => $approval->level + 1]);

                return;
            }

            if ($this->hasLockedDays($application->user, $application->start_date, $application->end_date)) {
                throw new LeaveException('Payroll for part of this period is already approved. Reopen it before approving this leave.');
            }

            $application->update(['status' => LeaveApplication::APPROVED, 'decided_at' => now()]);
            $this->debit($application, $by);
        });

        $application->refresh();

        if ($application->status === LeaveApplication::APPROVED) {
            $this->recalculateAttendance($application);
            $application->user->notify(new WorkforceAlert('leave', $this->summary($application) . ' was approved.', route('employee.leave')));
        } else {
            $this->notifyCurrentApprovers($application->fresh(['user', 'approvals', 'leaveType']));
        }

        AuditLog::record('leave.approved', $application, [], ['by' => $by->id, 'status' => $application->status], $by->id);

        return $application;
    }

    public function reject(LeaveApplication $application, User $by, string $note): LeaveApplication
    {
        $this->guardDecision($application, $by);

        DB::transaction(function () use ($application, $by, $note) {
            $this->currentApproval($application)
                ->update(['status' => 'rejected', 'acted_by' => $by->id, 'acted_at' => now(), 'note' => $note]);

            $application->update(['status' => LeaveApplication::REJECTED, 'decided_at' => now()]);
        });

        AuditLog::record('leave.rejected', $application, [], ['by' => $by->id, 'note' => $note], $by->id);

        $application->user->notify(new WorkforceAlert('leave', $this->summary($application) . ' was rejected: ' . $note, route('employee.leave')));

        return $application->refresh();
    }

    /** Employees may withdraw a pending request or an approved leave that has not started. */
    public function cancel(LeaveApplication $application, User $by): LeaveApplication
    {
        $isOwner = $by->id === $application->user_id;
        $isManager = !$isOwner && ($by->hasRole('admin') || ($by->can('leaves.approve') && WorkforceScope::canSee($by, $application->user, 'leaves')));

        if (!$isOwner && !$isManager) {
            throw new LeaveException('You cannot cancel this leave request.');
        }

        if (in_array($application->status, [LeaveApplication::REJECTED, LeaveApplication::CANCELLED], true)) {
            throw new LeaveException('This leave request is already ' . $application->status . '.');
        }

        $wasApproved = $application->status === LeaveApplication::APPROVED;

        if ($wasApproved) {
            if ($isOwner && $application->start_date->lte(today())) {
                throw new LeaveException('Approved leave that has started cannot be cancelled. Ask HR to cancel it.');
            }

            if ($this->hasLockedDays($application->user, $application->start_date, $application->end_date)) {
                throw new LeaveException('Payroll for this period is already approved, so the leave cannot be cancelled.');
            }
        }

        DB::transaction(function () use ($application, $by, $wasApproved) {
            $application->update(['status' => LeaveApplication::CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $by->id]);

            if ($wasApproved) {
                LeaveLedgerEntry::create([
                    'user_id' => $application->user_id,
                    'leave_type_id' => $application->leave_type_id,
                    'entry_type' => 'reversal',
                    'days' => $application->total_days,
                    'effective_date' => $application->start_date,
                    'leave_application_id' => $application->id,
                    'remarks' => 'Leave cancelled',
                    'created_by' => $by->id,
                ]);
            }
        });

        AuditLog::record('leave.cancelled', $application, [], ['by' => $by->id], $by->id);

        if ($wasApproved) {
            $this->recalculateAttendance($application);
        }

        return $application->refresh();
    }

    /** Manual balance correction by HR; always carries a reason. */
    public function adjust(User $user, int $leaveTypeId, float $days, string $reason, User $by): LeaveLedgerEntry
    {
        if (abs($days) < 0.01) {
            throw new LeaveException('Enter a non-zero number of days.');
        }

        $entry = LeaveLedgerEntry::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveTypeId,
            'entry_type' => 'adjustment',
            'days' => $days,
            'effective_date' => today(),
            'remarks' => $reason,
            'created_by' => $by->id,
        ]);

        AuditLog::record('leave.balance_adjusted', $entry, [], ['user_id' => $user->id, 'days' => $days, 'reason' => $reason], $by->id);

        return $entry;
    }

    /* ───────────────────────── accrual & year end ───────────────────────── */

    /**
     * Credits whatever is due up to $asOf. Safe to run daily: each credit has
     * a period key, so it can only ever be posted once.
     */
    public function accrue(User $user, ?CarbonInterface $asOf = null): int
    {
        $asOf = Carbon::instance($asOf ?? today())->startOfDay();
        $credited = 0;

        if (!$user->date_of_joining) {
            return 0;
        }

        $joined = Carbon::instance($user->date_of_joining)->startOfDay();

        $types = LeaveType::query()->active()->forClient($user->client_id)->get();

        foreach ($types as $type) {
            $policy = $this->resolver->leavePolicy($user, $type->id, $asOf);

            if (!$policy || $policy->accrual_frequency === 'none' || $policy->accrual_amount <= 0) {
                continue;
            }

            $cursor = $joined->copy()->startOfMonth();
            $floor = Carbon::instance($policy->effective_from)->startOfMonth();
            if ($cursor->lt($floor)) {
                $cursor = $floor;
            }

            while ($cursor->lte($asOf)) {
                $isYearly = in_array($policy->accrual_frequency, ['yearly', 'upfront'], true);

                if ($isYearly && $cursor->month !== 1 && !$cursor->isSameMonth($joined)) {
                    $cursor->addMonth();
                    continue;
                }

                $creditDate = $isYearly
                    ? ($cursor->isSameMonth($joined) ? $joined->copy() : $cursor->copy())
                    : $cursor->copy()->day(min($policy->credit_day ?: 1, $cursor->daysInMonth));

                $key = $isYearly ? (string) $cursor->year : $cursor->format('Y-m');

                $eligibleFrom = $joined->copy()->addDays($policy->eligible_after_days);

                if ($creditDate->lte($asOf) && $creditDate->gte($joined) && $creditDate->gte($eligibleFrom)
                    && $this->credit($user, $type, $policy, $key, $creditDate)) {
                    $credited++;
                }

                $cursor->addMonth();
            }
        }

        return $credited;
    }

    /** Applies the carry-forward rule to the balance left at the end of $year. */
    public function rollOver(User $user, int $year): int
    {
        $created = 0;
        $yearEnd = Carbon::create($year, 12, 31);

        $types = LeaveType::query()->active()->forClient($user->client_id)->get();

        foreach ($types as $type) {
            $policy = $this->resolver->leavePolicy($user, $type->id, $yearEnd);

            if (!$policy || $policy->accrual_frequency === 'none') {
                continue;
            }

            $balance = $this->balance($user, $type->id, $yearEnd);

            if ($balance <= 0) {
                continue;
            }

            $keep = match ($policy->carry_forward_mode) {
                'all' => $balance,
                'capped' => min($balance, (float) ($policy->carry_forward_cap ?? 0)),
                default => 0.0,
            };

            $lapse = round($balance - $keep, 2);

            if ($lapse <= 0) {
                continue;
            }

            $entry = LeaveLedgerEntry::query()->firstOrCreate(
                ['user_id' => $user->id, 'leave_type_id' => $type->id, 'entry_type' => 'lapse', 'period_key' => (string) $year],
                [
                    'days' => -$lapse,
                    'effective_date' => $yearEnd->toDateString(),
                    'remarks' => 'Year-end lapse (carry forward: ' . $policy->carry_forward_mode . ')',
                ]
            );

            $created += $entry->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    private function credit(User $user, LeaveType $type, LeavePolicy $policy, string $key, Carbon $date): bool
    {
        $amount = $policy->accrual_amount;

        if ($policy->annual_cap !== null) {
            $year = $date->year;
            $already = (float) LeaveLedgerEntry::query()
                ->where('user_id', $user->id)
                ->where('leave_type_id', $type->id)
                ->where('entry_type', 'accrual')
                ->whereYear('effective_date', $year)
                ->sum('days');

            $amount = min($amount, max(0, $policy->annual_cap - $already));
        }

        if ($amount <= 0) {
            return false;
        }

        $entry = LeaveLedgerEntry::query()->firstOrCreate(
            ['user_id' => $user->id, 'leave_type_id' => $type->id, 'entry_type' => 'accrual', 'period_key' => $key],
            ['days' => $amount, 'effective_date' => $date->toDateString(), 'remarks' => 'Accrual ' . $key]
        );

        return $entry->wasRecentlyCreated;
    }

    /* ───────────────────────── internals ───────────────────────── */

    private function guardDecision(LeaveApplication $application, User $by): void
    {
        if (!$application->isPending()) {
            throw new LeaveException('This request has already been ' . $application->status . '.');
        }

        if ($by->id === $application->user_id) {
            throw new LeaveException('You cannot decide your own leave request.');
        }

        if (!$this->canDecide($by, $application)) {
            throw new LeaveException('You are not an approver for this request.');
        }
    }

    private function currentApproval(LeaveApplication $application): ?LeaveApproval
    {
        return $application->approvals()->where('status', 'pending')->orderBy('level')->first();
    }

    private function debit(LeaveApplication $application, User $by): void
    {
        $type = $application->leaveType ?? LeaveType::find($application->leave_type_id);

        // Unpaid leave is recorded on attendance and goes to payroll as LOP; it has no balance to reduce.
        if (!$type->is_paid || $application->policy?->accrual_frequency === 'none') {
            return;
        }

        LeaveLedgerEntry::create([
            'user_id' => $application->user_id,
            'leave_type_id' => $application->leave_type_id,
            'entry_type' => 'debit',
            'days' => -1 * $application->total_days,
            'effective_date' => $application->start_date,
            'leave_application_id' => $application->id,
            'remarks' => 'Approved leave',
            'created_by' => $by->id,
        ]);
    }

    private function recalculateAttendance(LeaveApplication $application): void
    {
        $this->calculator->calculateRange($application->user, $application->start_date, $application->end_date);
    }

    private function hasLockedDays(User $user, CarbonInterface $start, CarbonInterface $end): bool
    {
        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', Carbon::instance($start)->toDateString())->whereDate('attendance_date', '<=', Carbon::instance($end)->toDateString())
            ->whereNotNull('locked_at')
            ->exists();
    }

    private function notifyCurrentApprovers(LeaveApplication $application): void
    {
        $approval = $this->currentApproval($application);

        if (!$approval) {
            return;
        }

        $employee = $application->user;
        $approvers = collect();

        if ($approval->approver_role === 'reporting_manager' && $employee->manager) {
            $approvers->push($employee->manager);
        }

        if ($approvers->isEmpty()) {
            $approvers = User::permission('leaves.approve')
                ->where('id', '!=', $employee->id)
                ->get()
                ->filter(fn (User $u) => WorkforceScope::canSee($u, $employee, 'leaves'));
        }

        foreach ($approvers->unique('id') as $approver) {
            $approver->notify(new WorkforceAlert(
                'leave',
                $employee->full_name . ' requested ' . $this->summary($application) . '.',
                route('workforce.leave.index')
            ));
        }
    }

    private function summary(LeaveApplication $application): string
    {
        $type = $application->leaveType?->name ?? 'leave';
        $dates = $application->start_date->equalTo($application->end_date)
            ? $application->start_date->format('d M')
            : $application->start_date->format('d M') . ' – ' . $application->end_date->format('d M');

        return $this->trim($application->total_days) . ' day(s) of ' . $type . ' (' . $dates . ')';
    }

    private function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
