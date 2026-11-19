<?php

namespace App\Services\Workforce;

use App\Models\AttendancePolicy;
use App\Models\EmployeeShift;
use App\Models\Holiday;
use App\Models\LeavePolicy;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Finds the effective-dated rules that apply to an employee on a date.
 * Rules of one client never apply to another client's employees.
 */
class PolicyResolver
{
    public function attendancePolicy(User $user, CarbonInterface $date): ?AttendancePolicy
    {
        if (!$user->client_id) {
            return null;
        }

        return AttendancePolicy::query()
            ->where('client_id', $user->client_id)
            ->active()
            ->effectiveOn($date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function shift(User $user, CarbonInterface $date): ?Shift
    {
        $assignment = EmployeeShift::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString()))
            ->orderByDesc('effective_from')
            ->with('shift')
            ->first();

        return $assignment?->shift;
    }

    /** Weekly off by attendance policy, falling back to the shift's working days. */
    public function isWeeklyOff(User $user, CarbonInterface $date, ?AttendancePolicy $policy = null, ?Shift $shift = null): bool
    {
        $policy ??= $this->attendancePolicy($user, $date);

        if ($policy && !empty($policy->weekly_offs)) {
            return $policy->isWeeklyOff($date);
        }

        $shift ??= $this->shift($user, $date);

        if ($shift && !empty($shift->working_days)) {
            return !in_array($date->dayOfWeekIso, array_map('intval', $shift->working_days), true);
        }

        return false;
    }

    public function holiday(User $user, CarbonInterface $date): ?Holiday
    {
        return Holiday::query()
            ->forClient($user->client_id)
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    /** @return Collection<int, Holiday> keyed by Y-m-d */
    public function holidaysBetween(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Holiday::query()
            ->forClient($user->client_id)
            ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
            ->get()
            ->keyBy(fn (Holiday $h) => $h->date->toDateString());
    }

    public function leavePolicy(User $user, int $leaveTypeId, CarbonInterface $date): ?LeavePolicy
    {
        return LeavePolicy::query()
            ->where('leave_type_id', $leaveTypeId)
            ->active()
            ->effectiveOn($date)
            ->where(function ($q) use ($user) {
                $q->whereNull('client_id');
                if ($user->client_id) {
                    $q->orWhere('client_id', $user->client_id);
                }
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('employment_type')->orWhere('employment_type', $user->employment_type);
            })
            // Most specific wins: client over company-wide, then employment type over generic.
            ->orderByRaw('client_id is null')
            ->orderByRaw('employment_type is null')
            ->orderByDesc('effective_from')
            ->first();
    }
}
