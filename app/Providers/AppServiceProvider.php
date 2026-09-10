<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateOffer;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\CandidateOfferPolicy;
use App\Policies\CandidatePolicy;
use App\Policies\CandidateRoundProgressPolicy;
use App\Policies\InterviewRoundPolicy;
use App\Policies\QuestionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        Gate::policy(CandidateOffer::class, CandidateOfferPolicy::class);

        /* ── Admin bypasses all gates ── */
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin')
                ? true
                : null;
        });

        RateLimiter::for(
            'offer-portal',
            function (Request $request) {
                return [
                    Limit::perMinute(30)
                        ->by($request->ip()),

                    Limit::perMinute(10)
                        ->by(
                            $request->ip()
                            . '|' .
                            (string) $request->route('token')
                        ),
                ];
            }
        );
    }
}