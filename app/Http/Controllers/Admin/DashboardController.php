<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $this->buildStats(),
        ]);
    }

    private function buildStats(): array
    {
        $byStatus = [
            'new'                => Candidate::where('current_status', 'new')->count(),
            'in_progress'        => Candidate::where('current_status', 'in_progress')->count(),
            'round_completed'    => Candidate::where('current_status', 'round_completed')->count(),
            'all_rounds_cleared' => Candidate::where('current_status', 'all_rounds_cleared')->count(),
            'rejected'           => Candidate::where('current_status', 'rejected')->count(),
        ];

        $total    = array_sum($byStatus);
        $selected = Candidate::where('final_status', 'selected')->count();

        return [
            'total_candidates'   => $total,
            'total_branches'     => Branch::count(),
            'total_interviewers' => User::role('interviewer')->count(),
            'pending_interviews' => CandidateRoundProgress::where('status', 'pending')->count(),
            'selected_candidates'=> $selected,
            'selection_rate'     => $total > 0 ? round($selected / $total * 100, 1) : 0,
            'candidates_by_status' => $byStatus,
            'candidates_by_profile' => [
                'advisory_executive' => Candidate::where('profile_category', 'advisory_executive')->count(),
                'leadership'         => Candidate::where('profile_category', 'leadership')->count(),
                'other'              => Candidate::where('profile_category', 'other')->count(),
            ],
            'recent_candidates' => Candidate::with('branch', 'currentRound')
                ->latest('registration_date')
                ->limit(8)
                ->get()
                ->map(fn ($c) => [
                    'id'         => $c->id,
                    'name'       => $c->full_name,
                    'position'   => $c->position_applied,
                    'profile'    => $c->getProfileCategoryLabel(),
                    'branch'     => $c->branch->name,
                    'status'     => $c->current_status,
                    'round'      => $c->currentRound?->name,
                    'registered' => $c->registration_date->format('d M Y'),
                ]),
            'round_stats' => InterviewRound::active()->ordered()
                ->withCount('candidateProgress')
                ->get()
                ->map(fn ($r) => [
                    'id'    => $r->id,
                    'name'  => $r->name,
                    'count' => $r->candidate_progress_count,
                    'stats' => $r->getStats(),
                ]),
        ];
    }
}