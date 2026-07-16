<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\User;

class CandidatePolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin'))
            return true;
        return null;
    }

    /** Interviewer can view a candidate only if they are assigned to them */
    public function view(User $user, Candidate $candidate): bool
    {
        return CandidateRoundProgress::where('candidate_id', $candidate->id)
            ->where('interviewer_id', $user->id)
            ->exists();
    }

    /** Interviewer can update only their own round's progress */
    public function conductInterview(User $user, Candidate $candidate): bool
    {
        return CandidateRoundProgress::where('candidate_id', $candidate->id)
            ->where('interviewer_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('interviewer');
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, Candidate $candidate): bool
    {
        return false;
    }
    public function delete(User $user, Candidate $candidate): bool
    {
        return false;
    }
}