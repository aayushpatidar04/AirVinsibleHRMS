<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Policies\BranchPolicy;
use App\Policies\CandidatePolicy;
use App\Policies\CandidateRoundProgressPolicy;
use App\Policies\InterviewRoundPolicy;
use App\Policies\QuestionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /* ── Policy Registrations ── */
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Candidate::class, CandidatePolicy::class);
        Gate::policy(InterviewRound::class, InterviewRoundPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(CandidateRoundProgress::class, CandidateRoundProgressPolicy::class);

        /* ── Admin bypasses all gates ── */
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin'))
                return true;
        });
    }
}