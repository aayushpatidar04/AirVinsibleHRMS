<?php

namespace App\Policies;

use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) return true;
        return null;
    }
 
    /** Interviewers can create custom questions during their interviews */
    public function create(User $user): bool { return $user->hasRole('interviewer'); }
    public function update(User $user, Question $question): bool { return false; }
    public function delete(User $user, Question $question): bool { return false; }
}