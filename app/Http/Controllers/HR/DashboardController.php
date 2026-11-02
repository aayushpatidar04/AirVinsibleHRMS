<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('dashboard.hr.view'),
            403
        );

        $candidateQuery = Candidate::query()
            ->visibleTo($user);

        $scheduleQuery = InterviewSchedule::query()
            ->visibleTo($user);
        $assignmentRequests = CandidateRoundProgress::query()
            ->where('interviewer_id', $user->id)
            ->where('assignment_status', 'pending')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('candidate')
            ->with([
                'candidate:id,first_name,last_name,position_applied,branch_id',
                'candidate.branch:id,name',
                'round:id,name',
            ])
            ->latest()
            ->get()
            ->map(fn (CandidateRoundProgress $progress) => [
                'id' => $progress->id,
                'candidate_id' => $progress->candidate_id,
                'candidate_name' => $progress->candidate->full_name,
                'position' => $progress->candidate->position_applied,
                'branch_name' => $progress->candidate->branch?->name,
                'round_id' => $progress->round_id,
                'round_name' => $progress->round->name,
            ])
            ->values();

        return Inertia::render('Hr/Dashboard', [
            'stats' => [
                'total_candidates' => (clone $candidateQuery)->count(),

                'new_candidates' => (clone $candidateQuery)
                    ->where('current_status', 'new')
                    ->count(),

                'pending_approvals' => (clone $candidateQuery)
                    ->pendingApproval()
                    ->count(),

                'awaiting_final_decision' => (clone $candidateQuery)
                    ->where('current_status', 'all_rounds_cleared')
                    ->where('final_status', 'pending')
                    ->count(),

                'selected_candidates' => (clone $candidateQuery)
                    ->where('final_status', 'selected')
                    ->count(),

                'today_interviews' => (clone $scheduleQuery)
                    ->whereDate('scheduled_at', today())
                    ->where('status', 'scheduled')
                    ->count(),

                'upcoming_interviews' => (clone $scheduleQuery)
                    ->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled')
                    ->count(),

                'pending_assignments' => $assignmentRequests->count(),
            ],

            'assignmentRequests' => $assignmentRequests,

            'upcomingInterviews' => $scheduleQuery
                ->with([
                    'candidate:id,first_name,last_name,branch_id',
                    'round:id,name',
                    'interviewer:id,first_name,last_name,branch_id',
                    'interviewer.branch:id,name',
                ])
                ->where('scheduled_at', '>=', now())
                ->where('status', 'scheduled')
                ->orderBy('scheduled_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
