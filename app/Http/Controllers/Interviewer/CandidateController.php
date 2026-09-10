<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewSchedule;
use App\Models\User;
use App\Services\InterviewHistoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

        $candidates = Candidate::query()
            ->assignedToInterviewer($request->user())
            ->with([
                'branch',
                'currentRound',
                'currentInterviewer',
            ])
            ->latest('registration_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Interviewer/Candidates/Index', [
            'candidates' => $candidates,
        ]);
    }

    public function show(Candidate $candidate, InterviewHistoryService $historyService)
    {
        $this->authorize('viewAsInterviewer', $candidate);
        $user = auth()->user();

        abort_unless(
            CandidateRoundProgress::where('candidate_id', $candidate->id)
                ->where('interviewer_id', $user->id)
                ->exists(),
            403,
            'You are not assigned to this candidate.'
        );

        $candidate->load('branch', 'currentRound', 'formSubmission');

        $progressHistory = $historyService
            ->forCandidate(
                $candidate,
                $user
            );

        // Find the current assigned progress for this interviewer
        $myProgress = $candidate->roundProgress()
            ->where('interviewer_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('round')
            ->first();

        $availableInterviewers = [];

        if ($myProgress) {
            $availableInterviewers = User::query()
                ->active()
                ->canInterview()
                ->get(['id', 'name', 'employee_id'])
                ->filter(fn($u) => $u->canInterviewRound($myProgress->round))
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'employee_id' => $u->employee_id,
                ]);
        }

        $schedules = InterviewSchedule::where('candidate_id', $candidate->id)
            ->with('round', 'interviewer')
            ->orderBy('scheduled_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'round_id' => $s->round_id,
                'round_name' => $s->round->name,
                'interviewer_name' => $s->interviewer->full_name,
                'scheduled_at' => $s->formattedTime(),
                'scheduled_at_raw' => $s->scheduled_at->toISOString(),
                'gmeet_link' => $s->gmeet_link,
                'notes' => $s->notes,
                'status' => $s->status,
                'is_upcoming' => $s->isUpcoming(),
                'time_until' => $s->timeUntil(),
                'sent_at' => $s->sent_at?->format('d M Y H:i'),
            ]);

        return Inertia::render('Interviewer/Candidates/Show', [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'position' => $candidate->position_applied,
                'profile_label' => $candidate->getProfileCategoryLabel(),
                'profile_category' => $candidate->profile_category,
                'branch' => $candidate->branch->name,
                'current_status' => $candidate->current_status,
                'average_rating' => $candidate->getAverageRating(),
                'completed_rounds' => $candidate->completedRoundsCount(),
                'resume_path' => $candidate->latestSubmission?->resume_path,
                'resume_name' => $candidate->latestSubmission?->resume_original_name,
                'requires_salary_hr' => $candidate->requiresSalaryInHrRound(),
                'salary_post_ops' => $candidate->isSalaryPostOps(),
                'registered_at' => $candidate->registration_date->format('d M Y H:i'),
            ],
            'progress_history' => $progressHistory,
            'my_active_progress' => $myProgress ? [
                'progress_id' => $myProgress->id,
                'round_id' => $myProgress->round_id,
                'round_name' => $myProgress->round->name,
                'is_hr_round' => $myProgress->round->is_hr_round,
                'status' => $myProgress->status,
            ] : null,
            'available_interviewers' => $availableInterviewers,
            'schedules' => $schedules,
        ]);
    }

    public function history(Request $request)
    {
        $email = $request->get('email');
        $applications = Candidate::allApplicationsByEmail($email);

        return Inertia::render('Admin/Candidates/History', [
            'applications' => $applications,
            'email' => $email,
        ]);
    }
}