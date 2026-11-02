<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $this->buildStats($request->user()),
            'assignmentRequests' => $this->assignmentRequests($request->user()),
        ]);
    }

    private function buildStats(User $user): array
    {
        $byStatus = [
            'new' => Candidate::where('current_status', 'new')->count(),
            'in_progress' => Candidate::where('current_status', 'in_progress')->count(),
            'all_rounds_cleared' => Candidate::where('current_status', 'all_rounds_cleared')->count(),
            'rejected' => Candidate::where('current_status', 'rejected')->count(),
        ];

        $total = array_sum($byStatus);
        $selected = Candidate::where('final_status', 'selected')->count();

        return [
            'total_candidates' => $total,
            'total_branches' => Branch::count(),
            'total_interviewers' => User::query()->active()->canInterview()->count(),
            'pending_interviews' => CandidateRoundProgress::where('status', 'pending')
                ->where('assignment_status', 'accepted')
                ->count(),
            'pending_assignments' => CandidateRoundProgress::query()
                ->where('interviewer_id', $user->id)
                ->where('assignment_status', 'pending')
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereHas('candidate')
                ->count(),
            'selected_candidates' => $selected,
            'selection_rate' => $total > 0 ? round($selected / $total * 100, 1) : 0,
            'candidates_by_status' => $byStatus,
            'candidates_by_profile' => [
                'advisory_executive' => Candidate::where('profile_category', 'advisory_executive')->count(),
                'leadership' => Candidate::where('profile_category', 'leadership')->count(),
                'other' => Candidate::where('profile_category', 'other')->count(),
            ],
            'recent_candidates' => Candidate::with('branch', 'currentRound')
                ->latest('registration_date')
                ->limit(8)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->full_name,
                    'position' => $c->position_applied,
                    'profile' => $c->getProfileCategoryLabel(),
                    'branch' => $c->branch->name,
                    'status' => $c->current_status,
                    'round' => $c->currentRound?->name,
                    'registered' => $c->registration_date->format('d M Y'),
                ]),
            'round_stats' => InterviewRound::active()->ordered()
                ->withCount('candidateProgress')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'count' => $r->candidate_progress_count,
                    'stats' => $r->getStats(),
                ]),
        ];
    }

    private function assignmentRequests(User $user): array
    {
        return CandidateRoundProgress::query()
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
            ->values()
            ->all();
    }
}
