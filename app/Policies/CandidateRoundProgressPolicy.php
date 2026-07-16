<?php

namespace App\Policies;

use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\User;

class CandidateRoundProgressPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin'))
            return true;
        return null;
    }

    /** Interviewer can only view/update their own progress records */
    public function view(User $user, CandidateRoundProgress $progress): bool
    {
        return $progress->interviewer_id === $user->id;
    }

    public function update(User $user, CandidateRoundProgress $progress): bool
    {
        return $progress->interviewer_id === $user->id
            && in_array($progress->status, ['pending', 'in_progress']);
    }
}