<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    /**
     * Admin bypasses all CandidatePolicy checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('candidates.view');
    }

    public function view(User $user, Candidate $candidate): bool
    {
        if (!$user->can('candidates.view')) {
            return false;
        }

        if (
            $user->hasRole('hr')
            && $user->can('candidates.view-branch')
        ) {
            return $user->canAccessBranch($candidate->branch_id);
        }

        if (
            $user->isInterviewer()
            && $user->can('interviews.view-assigned')
        ) {
            return $candidate->current_interviewer_id === $user->id
                || $candidate->roundProgress()
                    ->where('interviewer_id', $user->id)
                    ->exists();
        }

        return false;
    }

    public function viewAnyAssigned(User $user): bool
    {
        return $user->can('interviews.view-assigned')
            || $user->canInterview();
    }

    public function viewAsInterviewer(User $user, Candidate $candidate): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $user->canInterview()) {
            return false;
        }

        return $candidate->roundProgress()
            ->where('interviewer_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('candidates.create');
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.update')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.delete')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function restore(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.restore')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function approve(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.approve')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function rejectApproval(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.reject-approval')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function finalDecision(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.final-decision')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function viewSalary(User $user, Candidate $candidate): bool
    {
        if (!$user->can('candidates.view-salary')) {
            return false;
        }

        return $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function updateSalary(User $user, Candidate $candidate): bool
    {
        return $user->can('candidates.update-salary')
            && $this->belongsToAccessibleBranch($user, $candidate);
    }

    public function viewDocuments(User $user, Candidate $candidate): bool
    {
        if (!$user->can('candidates.view-documents')) {
            return false;
        }

        return $this->view($user, $candidate);
    }

    private function belongsToAccessibleBranch(
        User $user,
        Candidate $candidate
    ): bool {
        return $user->canAccessBranch($candidate->branch_id);
    }
}