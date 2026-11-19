<?php

namespace App\Services\Workforce;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Decides which employees a manager may see for a given module.
 *
 *  - admin, or "<module>.view-all-branches" ........ everyone
 *  - "<module>.view-branch" ........................ employees of the manager's branch
 *  - "<module>.view-team" .......................... direct and indirect reportees
 *  - anyone ........................................ themselves
 *
 * The grants add up: a team leader who is also granted branch access sees
 * both. Modules are "attendance", "leaves" and "payroll".
 */
class WorkforceScope
{
    public static function employees(User $viewer, string $module): Builder
    {
        $query = User::query();

        if (self::seesEveryone($viewer, $module)) {
            return $query;
        }

        $ids = self::visibleIds($viewer, $module);

        return $query->whereIn('users.id', $ids);
    }

    public static function seesEveryone(User $viewer, string $module): bool
    {
        return $viewer->hasRole('admin') || $viewer->can("{$module}.view-all-branches");
    }

    /** @return array<int, int> */
    public static function visibleIds(User $viewer, string $module): array
    {
        $ids = [$viewer->id];

        if ($viewer->can("{$module}.view-branch") && $viewer->branch_id) {
            $ids = array_merge(
                $ids,
                User::query()->where('branch_id', $viewer->branch_id)->pluck('id')->all()
            );
        }

        if ($viewer->can("{$module}.view-team")) {
            $ids = array_merge($ids, self::reporteeIds($viewer->id));
        }

        return array_values(array_unique($ids));
    }

    public static function canSee(User $viewer, User $employee, string $module): bool
    {
        if ($viewer->id === $employee->id || self::seesEveryone($viewer, $module)) {
            return true;
        }

        return in_array($employee->id, self::visibleIds($viewer, $module), true);
    }

    /** Direct and indirect reportees (cycle-safe). */
    public static function reporteeIds(int $managerId): array
    {
        $found = [];
        $frontier = [$managerId];

        while ($frontier) {
            $next = User::query()->whereIn('reporting_to', $frontier)->pluck('id')->all();
            $next = array_values(array_diff($next, $found, [$managerId]));
            $found = array_merge($found, $next);
            $frontier = $next;
        }

        return $found;
    }
}
