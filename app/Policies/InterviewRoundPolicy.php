<?php

namespace App\Policies;

use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\User;

class InterviewRoundPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin'))
            return true;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, InterviewRound $round): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, InterviewRound $round): bool
    {
        return false;
    }
    public function delete(User $user, InterviewRound $round): bool
    {
        return false;
    }
}