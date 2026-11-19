<?php

namespace App\Services\Workforce;

use App\Models\AttendanceActivitySession;
use App\Models\AttendancePolicy;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\LeaveApplication;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Turns raw punches, activity sessions, the assigned shift, the effective
 * attendance policy, holidays and approved leave into one derived record
 * per employee per day.
 *
 * The raw punches are never touched. The derived record can be recomputed
 * at any time until payroll locks it; after that it is returned unchanged,
 * so a later policy edit cannot silently re-price approved payroll.
 */
class AttendanceCalculator
{
    /** Flags that need a person to look at the day before payroll. */
    public const BLOCKING_FLAGS = [
        'missing_in', 'missing_out', 'missing_portal', 'missing_machine', 'source_conflict',
        'geo_exception', 'worked_on_leave', 'short_session', 'open_break',
    ];

    public function __construct(private readonly PolicyResolver $resolver)
    {
    }

    public function calculate(User $user, CarbonInterface $date, ?CarbonInterface $now = null): AttendanceRecord
    {
        $date = Carbon::instance($date)->startOfDay();
        $now = $now ? Carbon::instance($now) : now();

        $existing = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        if ($existing?->isLocked()) {
            return $existing;
        }

        $policy = $this->resolver->attendancePolicy($user, $date);
        $shift = $this->resolver->shift($user, $date);
        $holiday = $this->resolver->holiday($user, $date);
        $leave = $this->approvedLeave($user, $date);

        $punches = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->valid()
            ->orderBy('punched_at')
            ->get();

        $flags = [];
        [$ins, $outs, $breaks] = $this->selectPunches($punches, $policy, $flags);

        $firstIn = $ins->first()?->punched_at;
        $lastOut = $outs->filter(fn ($p) => !$firstIn || $p->punched_at->gte($firstIn))->last()?->punched_at;

        [$shiftStart, $shiftEnd] = $this->shiftWindow($shift, $date);
        $scheduled = $shiftStart ? (int) $shiftStart->diffInMinutes($shiftEnd) : 0;

        $span = ($firstIn && $lastOut) ? (int) $firstIn->diffInMinutes($lastOut) : 0;
        $breakMinutes = $this->breakMinutes($breaks, $lastOut, $flags);
        $worked = $this->netWorked($span, $breakMinutes, $policy, $flags);

        $sessions = $this->sessionTotals($user, $date);
        if ($policy && $policy->min_session_minutes > 0 && $firstIn && $lastOut
            && $sessions['session'] < $policy->min_session_minutes) {
            $flags[] = 'short_session';
        }

        $graceMinutes = $policy?->grace_minutes ?? $shift?->grace_period_minutes ?? 0;
        $lateRaw = ($shiftStart && $firstIn) ? max(0, (int) $shiftStart->diffInMinutes($firstIn, false)) : 0;
        $isLate = $lateRaw > $graceMinutes;

        $earlyRaw = ($shiftEnd && $lastOut) ? max(0, (int) $lastOut->diffInMinutes($shiftEnd, false)) : 0;
        $isEarly = $earlyRaw > ($policy?->early_departure_minutes ?? 0);

        $weeklyOff = $this->isWeeklyOff($policy, $shift, $date, $user);
        $overtime = $this->overtimeMinutes($policy, $worked, $weeklyOff || (bool) $holiday);

        [$status, $fraction, $leavePaid] = $this->decideStatus(
            $date, $now, $punches->isNotEmpty(), $firstIn, $lastOut, $worked, $shiftEnd,
            $policy, $leave, (bool) $holiday, $weeklyOff, $flags
        );

        $flags = array_values(array_unique($flags));

        $record = $existing ?? new AttendanceRecord(['user_id' => $user->id, 'attendance_date' => $date->toDateString()]);

        // A manual status override (with a reason) always wins over the computed day.
        if ($record->status_override) {
            $status = $record->status_override;
            $fraction = $record->override_fraction ?? $this->defaultFraction($status);
        }

        $review = $this->reviewStatus($record, $flags);

        $record->fill([
            'client_id' => $user->client_id,
            'shift_id' => $shift?->id,
            'policy_id' => $policy?->id,
            'policy_snapshot' => $policy?->snapshot(),
            'shift_snapshot' => $shift?->only(['id', 'name', 'start_time', 'end_time', 'is_overnight', 'grace_period_minutes']),
            'first_in' => $firstIn,
            'last_out' => $lastOut,
            'scheduled_minutes' => $scheduled,
            'span_minutes' => $span,
            'break_minutes' => $breakMinutes,
            'worked_minutes' => $worked,
            'session_minutes' => $sessions['session'],
            'active_minutes' => $sessions['active'],
            'idle_minutes' => $sessions['idle'],
            'late_minutes' => $isLate ? $lateRaw : 0,
            'early_minutes' => $isEarly ? $earlyRaw : 0,
            'is_late' => $isLate,
            'is_early' => $isEarly,
            'overtime_minutes' => $overtime,
            'overtime_approved' => $record->overtime_approved
                || ($overtime > 0 && $policy && !$policy->overtime_requires_approval),
            'status' => $status,
            'payable_fraction' => $fraction,
            'leave_application_id' => $leave?->id,
            'leave_is_paid' => $leavePaid,
            'flags' => $flags,
            'review_status' => $review,
            'computed_at' => $now,
        ]);

        if ($review !== 'reviewed') {
            $record->reviewed_by = null;
            $record->reviewed_at = null;
        }

        $record->save();

        return $record;
    }

    /** Recalculates every day in a range, skipping days payroll has locked. */
    public function calculateRange(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $records = collect();
        $day = Carbon::instance($from)->startOfDay();
        $end = Carbon::instance($to)->startOfDay();

        while ($day->lte($end)) {
            $records->push($this->calculate($user, $day->copy()));
            $day->addDay();
        }

        return $records;
    }

    /* ───────────────────────── punch selection ───────────────────────── */

    /**
     * @return array{0: Collection, 1: Collection, 2: Collection} ins, outs, break punches
     */
    private function selectPunches(Collection $punches, ?AttendancePolicy $policy, array &$flags): array
    {
        $breaks = $punches->whereIn('type', [AttendancePunch::TYPE_BREAK_START, AttendancePunch::TYPE_BREAK_END])->values();
        $inOut = $punches->whereIn('type', [AttendancePunch::TYPE_IN, AttendancePunch::TYPE_OUT]);

        // Approved corrections are written as manual punches. A manual "in" replaces
        // the source-derived "in" and a manual "out" replaces the "out"; the original
        // punches stay on file.
        $manual = $inOut->where('source', AttendancePunch::SOURCE_MANUAL);
        $manualIn = $manual->where('type', 'in');
        $manualOut = $manual->where('type', 'out');
        $bySource = $inOut->toBase()->groupBy('source');

        $portalIn = $inOut->where('source', AttendancePunch::SOURCE_PORTAL)->where('type', 'in');
        $machineIn = $inOut->where('source', AttendancePunch::SOURCE_MACHINE)->where('type', 'in');

        $corrected = $manualIn->isNotEmpty();

        if ($policy?->require_portal_punch && $portalIn->isEmpty() && !$corrected && $inOut->isNotEmpty()) {
            $flags[] = 'missing_portal';
        }
        if ($policy?->require_machine_punch && $machineIn->isEmpty() && !$corrected && $inOut->isNotEmpty()) {
            $flags[] = 'missing_machine';
        }

        if ($portalIn->isNotEmpty() && $machineIn->isNotEmpty() && !$corrected) {
            $tolerance = $policy?->source_tolerance_minutes ?? 15;
            if ($portalIn->first()->punched_at->diffInMinutes($machineIn->first()->punched_at) > $tolerance) {
                $flags[] = 'source_conflict';
            }
        }

        if (!$corrected && $inOut->contains(fn ($p) => $p->source === AttendancePunch::SOURCE_PORTAL
            && in_array($p->geo_status, ['outside', 'low_accuracy', 'unavailable', 'no_location_configured'], true))) {
            $flags[] = 'geo_exception';
        }

        $base = $this->chooseBySource($bySource->except(AttendancePunch::SOURCE_MANUAL), $policy);

        $ins = $manualIn->isNotEmpty() ? $manualIn : $base->where('type', 'in');
        $outs = $manualOut->isNotEmpty() ? $manualOut : $base->where('type', 'out');

        return [
            $ins->sortBy('punched_at')->values(),
            $outs->sortBy('punched_at')->values(),
            $breaks,
        ];
    }

    private function chooseBySource(Collection $bySource, ?AttendancePolicy $policy): Collection
    {
        $priority = $policy?->source_priority ?: ['machine', 'portal', 'agent'];

        foreach ($priority as $source) {
            $set = $bySource->get($source);
            if ($set && $set->contains('type', 'in')) {
                return $set;
            }
        }

        // No "in" anywhere: fall back to whatever exists so a lone out is still reported.
        return $bySource->flatten(1);
    }

    private function breakMinutes(Collection $breaks, ?CarbonInterface $lastOut, array &$flags): int
    {
        $total = 0;
        $open = null;

        foreach ($breaks->sortBy('punched_at') as $punch) {
            if ($punch->type === AttendancePunch::TYPE_BREAK_START) {
                $open ??= $punch->punched_at;
            } elseif ($open) {
                $total += (int) $open->diffInMinutes($punch->punched_at);
                $open = null;
            }
        }

        if ($open) {
            if ($lastOut && $lastOut->gt($open)) {
                $total += (int) $open->diffInMinutes($lastOut);
            }
            $flags[] = 'open_break';
        }

        return $total;
    }

    private function netWorked(int $span, int $breakMinutes, ?AttendancePolicy $policy, array &$flags): int
    {
        if ($span === 0) {
            return 0;
        }

        if (!$policy) {
            return max(0, $span - $breakMinutes);
        }

        if ($policy->max_break_minutes > 0 && $breakMinutes > $policy->max_break_minutes) {
            $flags[] = 'break_exceeded';
        }

        $unpaid = $policy->deduct_excess_break ? max(0, $breakMinutes - $policy->paid_break_minutes) : 0;

        return max(0, $span - $unpaid);
    }

    private function sessionTotals(User $user, CarbonInterface $date): array
    {
        $sessions = AttendanceActivitySession::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->get();

        return [
            'session' => (int) $sessions->sum(fn ($s) => $s->ended_at ? $s->started_at->diffInMinutes($s->ended_at) : 0),
            'active' => (int) round($sessions->sum('active_seconds') / 60),
            'idle' => (int) round($sessions->sum('idle_seconds') / 60),
        ];
    }

    /* ───────────────────────── schedule & status ───────────────────────── */

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    private function shiftWindow(?Shift $shift, CarbonInterface $date): array
    {
        if (!$shift) {
            return [null, null];
        }

        $start = Carbon::parse($date->toDateString() . ' ' . $shift->start_time->format('H:i:s'));
        $end = Carbon::parse($date->toDateString() . ' ' . $shift->end_time->format('H:i:s'));

        if ($shift->is_overnight || $end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    private function isWeeklyOff(?AttendancePolicy $policy, ?Shift $shift, CarbonInterface $date, User $user): bool
    {
        return $this->resolver->isWeeklyOff($user, $date, $policy, $shift);
    }

    private function overtimeMinutes(?AttendancePolicy $policy, int $worked, bool $nonWorkingDay): int
    {
        if (!$policy || !$policy->overtime_eligible || $worked <= 0) {
            return 0;
        }

        $extra = $nonWorkingDay ? $worked : max(0, $worked - $policy->full_day_minutes);

        return $extra >= $policy->overtime_min_minutes ? $extra : 0;
    }

    private function approvedLeave(User $user, CarbonInterface $date): ?LeaveApplication
    {
        return LeaveApplication::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveApplication::APPROVED)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->with('leaveType')
            ->first();
    }

    /**
     * @return array{0:string,1:float,2:?bool} status, payable fraction, leave paid?
     */
    private function decideStatus(
        Carbon $date, Carbon $now, bool $hasPunches, ?CarbonInterface $firstIn, ?CarbonInterface $lastOut,
        int $worked, ?CarbonInterface $shiftEnd, ?AttendancePolicy $policy, ?LeaveApplication $leave,
        bool $holiday, bool $weeklyOff, array &$flags
    ): array {
        $today = $now->copy()->startOfDay();

        $complete = $firstIn && $lastOut;
        $half = $policy?->half_day_minutes ?? 240;
        $full = $policy?->full_day_minutes ?? 480;

        if ($leave) {
            $paid = (bool) $leave->leaveType?->is_paid;

            if ($leave->is_half_day) {
                $second = $complete && $worked >= $half ? 0.5 : 0.0;

                return [$complete ? AttendanceRecord::STATUS_HALF_DAY : AttendanceRecord::STATUS_LEAVE, ($paid ? 0.5 : 0.0) + $second, $paid];
            }

            if ($hasPunches) {
                $flags[] = 'worked_on_leave';
            }

            return [AttendanceRecord::STATUS_LEAVE, $paid ? 1.0 : 0.0, $paid];
        }

        if ($date->gt($today)) {
            return [$holiday ? AttendanceRecord::STATUS_HOLIDAY : ($weeklyOff ? AttendanceRecord::STATUS_WEEKLY_OFF : AttendanceRecord::STATUS_PENDING), $holiday || $weeklyOff ? 1.0 : 0.0, null];
        }

        if ($holiday) {
            return [AttendanceRecord::STATUS_HOLIDAY, 1.0, null];
        }

        if ($weeklyOff) {
            return [AttendanceRecord::STATUS_WEEKLY_OFF, 1.0, null];
        }

        if (!$hasPunches) {
            $stillOpen = $date->eq($today) && (!$shiftEnd || $now->lt($shiftEnd));

            return $stillOpen
                ? [AttendanceRecord::STATUS_PENDING, 0.0, null]
                : [AttendanceRecord::STATUS_ABSENT, 0.0, null];
        }

        if (!$complete) {
            // A day still in progress is not an exception yet.
            $graceEnd = $shiftEnd ? $shiftEnd->copy()->addHours(2) : $date->copy()->endOfDay();
            if ($date->eq($today) && $now->lt($graceEnd) && $firstIn && !$lastOut) {
                return [AttendanceRecord::STATUS_PENDING, 0.0, null];
            }

            $flags[] = $firstIn ? 'missing_out' : 'missing_in';

            return match ($policy?->missing_punch_treatment ?? 'review') {
                'half_day' => [AttendanceRecord::STATUS_INCOMPLETE, 0.5, null],
                'absent' => [AttendanceRecord::STATUS_INCOMPLETE, 0.0, null],
                default => [AttendanceRecord::STATUS_INCOMPLETE, 0.0, null],
            };
        }

        if ($worked >= $full) {
            return [AttendanceRecord::STATUS_PRESENT, 1.0, null];
        }

        if ($worked >= $half) {
            return [AttendanceRecord::STATUS_HALF_DAY, 0.5, null];
        }

        $flags[] = 'short_hours';

        return [AttendanceRecord::STATUS_ABSENT, 0.0, null];
    }

    private function defaultFraction(string $status): float
    {
        return match ($status) {
            AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_WEEKLY_OFF, AttendanceRecord::STATUS_HOLIDAY => 1.0,
            AttendanceRecord::STATUS_HALF_DAY => 0.5,
            default => 0.0,
        };
    }

    private function reviewStatus(AttendanceRecord $record, array $flags): string
    {
        $blocking = array_values(array_intersect($flags, self::BLOCKING_FLAGS));

        if (!$blocking) {
            return 'ok';
        }

        $previous = $record->flags ?? [];
        sort($previous);
        $current = $flags;
        sort($current);

        // A person already reviewed exactly this set of exceptions: leave it reviewed.
        if ($record->exists && $record->review_status === 'reviewed' && $previous === $current) {
            return 'reviewed';
        }

        return 'needs_review';
    }
}
