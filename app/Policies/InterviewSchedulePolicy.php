<?php

namespace App\Policies;

use App\Models\InterviewSchedule;
use App\Models\User;

class InterviewSchedulePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('interviews.view');
    }

    public function view(User $user, InterviewSchedule $schedule): bool
    {
        if (!$user->can('interviews.view')) {
            return false;
        }

        if (
            $user->hasRole('hr')
            && $user->can('interviews.view-branch')
        ) {
            return $user->canAccessBranch(
                $schedule->candidate->branch_id
            );
        }

        if (
            $user->isInterviewer()
            && $user->can('interviews.view-assigned')
        ) {
            return $schedule->interviewer_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('interviews.schedule');
    }

    public function update(User $user, InterviewSchedule $schedule): bool
    {
        return $user->can('interviews.reschedule')
            && $this->belongsToAccessibleBranch($user, $schedule);
    }

    public function cancel(User $user, InterviewSchedule $schedule): bool
    {
        return $user->can('interviews.cancel')
            && $this->belongsToAccessibleBranch($user, $schedule);
    }

    public function sendEmail(
        User $user,
        InterviewSchedule $schedule
    ): bool {
        return $user->can('interviews.send-email')
            && $this->belongsToAccessibleBranch($user, $schedule);
    }

    public function markNoShow(
        User $user,
        InterviewSchedule $schedule
    ): bool {
        return $user->can('interviews.mark-no-show')
            && $this->belongsToAccessibleBranch($user, $schedule);
    }

    private function belongsToAccessibleBranch(
        User $user,
        InterviewSchedule $schedule
    ): bool {
        return $user->canAccessBranch(
            $schedule->candidate->branch_id
        );
    }
}