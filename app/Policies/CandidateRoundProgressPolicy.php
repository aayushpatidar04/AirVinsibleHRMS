<?php

namespace App\Policies;

use App\Models\CandidateRoundProgress;
use App\Models\User;

class CandidateRoundProgressPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function view(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        if (
            $user->hasRole('hr')
            && $user->can('interviews.view-branch')
        ) {
            return $user->canAccessBranch(
                $progress->candidate->branch_id
            );
        }

        return $user->can('interviews.view-assigned')
            && $progress->interviewer_id === $user->id;
    }

    public function start(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        return $user->can('interviews.start')
            && $progress->interviewer_id === $user->id
            && $progress->status === 'pending';
    }

    public function saveResponse(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        return $user->can('interviews.save-response')
            && $progress->interviewer_id === $user->id
            && in_array(
                $progress->status,
                ['pending', 'in_progress'],
                true
            );
    }

    public function addCustomQuestion(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        return $user->can('interviews.add-custom-question')
            && $progress->interviewer_id === $user->id
            && in_array(
                $progress->status,
                ['pending', 'in_progress'],
                true
            );
    }

    public function complete(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        return $user->can('interviews.complete')
            && $progress->interviewer_id === $user->id
            && $progress->status === 'in_progress';
    }

    public function reject(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        return $user->can('interviews.reject-candidate')
            && $progress->interviewer_id === $user->id
            && in_array(
                $progress->status,
                ['pending', 'in_progress'],
                true
            );
    }

    public function reassign(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        if (!$user->can('interviews.reassign-interviewer')) {
            return false;
        }

        return true;
    }

    public function moveNextRound(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        if (!$user->can('interviews.move-next-round')) {
            return false;
        }

        return $user->canAccessBranch(
            $progress->candidate->branch_id
        );
    }

    public function discussSalary(
        User $user,
        CandidateRoundProgress $progress
    ): bool {
        if (!$user->can('interviews.discuss-salary')) {
            return false;
        }

        /*
         * Assigned interviewers can discuss salary only during
         * their own assigned round.
         */
        if ($progress->interviewer_id === $user->id) {
            return true;
        }

        /*
         * HR can access salary discussions for its own branch.
         */
        return $user->hasRole('hr')
            && $user->canAccessBranch(
                $progress->candidate->branch_id
            );
    }
}