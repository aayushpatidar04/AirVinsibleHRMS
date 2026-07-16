<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $candidateIds = CandidateRoundProgress::where('interviewer_id', $user->id)
            ->pluck('candidate_id')
            ->unique();

        $candidates = Candidate::whereIn('id', $candidateIds)
            ->with('branch', 'currentRound')
            ->when(
                $request->search,
                fn($q, $s) =>
                $q->where('first_name', 'like', "%$s%")
                    ->orWhere('last_name', 'like', "%$s%")
                    ->orWhere('email', 'like', "%$s%")
            )
            ->when($request->status, fn($q, $s) => $q->where('current_status', $s))
            ->latest('registration_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Interviewer/Candidates/Index', [
            'candidates' => $candidates,
            'filters' => $request->only(['search', 'status']),
            'statusOptions' => Candidate::CURRENT_STATUSES,
        ]);
    }

    public function show(Candidate $candidate)
    {
        $user = auth()->user();

        abort_unless(
            CandidateRoundProgress::where('candidate_id', $candidate->id)
                ->where('interviewer_id', $user->id)
                ->exists(),
            403,
            'You are not assigned to this candidate.'
        );

        $candidate->load('branch', 'currentRound', 'formSubmission');

        $progressHistory = $candidate->roundProgress()
            ->with('round', 'interviewer', 'responses.question')
            ->orderBy('created_at')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'round_id' => $p->round_id,
                'round_name' => $p->round->name,
                'is_hr_round' => $p->round->is_hr_round,
                'is_ops_round' => $p->round->is_ops_round,
                'interviewer' => $p->interviewer->full_name,
                'is_mine' => $p->interviewer_id === $user->id,
                'status' => $p->status,
                'started_at' => $p->start_date?->format('d M Y H:i'),
                'ended_at' => $p->end_date?->format('d M Y H:i'),
                'duration' => $p->getDurationLabel(),
                'rating' => $p->overall_rating,
                'rating_stars' => $p->getRatingStars(),
                'feedback' => $p->overall_feedback,
                'rejection_reason' => $p->rejection_reason,
                'salary_offer_min' => $p->salary_offer_min,
                'salary_offer_max' => $p->salary_offer_max,
                'offered_designation' => $p->offered_designation,
                'salary_offer_status' => $p->salary_offer_status,
                'responses' => $p->responses->map(fn($r) => [
                    'question' => $r->question?->question_text,
                    'type' => $r->question?->question_type,
                    'is_mandatory' => $r->question?->is_mandatory,
                    'response_text' => $r->response_text,
                    'rating_value' => $r->rating_value,
                    'yes_no_value' => $r->yes_no_value,
                    'interviewer_notes' => $r->interviewer_notes,
                    'question_rating' => $r->question_rating,
                ]),
            ]);

        // Find the current assigned progress for this interviewer
        $myProgress = $candidate->roundProgress()
            ->where('interviewer_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('round')
            ->first();



        $availableInterviewers = [];

        if ($myProgress) {
            $availableInterviewers = User::role('interviewer')
                ->active()
                // ->fromBranch($candidate->branch_id ?? 0)
                ->get(['id', 'name', 'employee_id'])
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'employee_id' => $u->employee_id,
                    'can_take_round' => $u->canInterviewRound($myProgress->round),
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