<?php

namespace App\Http\Controllers\Interviewer;

use App\Http\Controllers\Controller;
use App\Models\CandidateRoundProgress;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $allProgress = CandidateRoundProgress::where('interviewer_id', $user->id);

        $pending    = (clone $allProgress)->pending()->with('candidate', 'round')->latest()->get();
        $inProgress = (clone $allProgress)->inProgress()->with('candidate', 'round')->latest()->get();

        return Inertia::render('Interviewer/Dashboard', [
            'stats' => [
                'pending'         => $pending->count(),
                'in_progress'     => $inProgress->count(),
                'completed_today' => (clone $allProgress)->completed()
                    ->whereDate('end_date', today())->count(),
                'total_completed' => (clone $allProgress)->completed()->count(),
                'total_rejected'  => (clone $allProgress)->rejected()->count(),
            ],
            'pending_interviews'  => $this->mapProgress($pending),
            'active_interviews'   => $this->mapProgress($inProgress),
        ]);
    }

    private function mapProgress($items): array
    {
        return $items->map(fn ($p) => [
            'id'             => $p->id,
            'candidate_id'   => $p->candidate->id,
            'candidate_name' => $p->candidate->full_name,
            'position'       => $p->candidate->position_applied,
            'profile'        => $p->candidate->getProfileCategoryLabel(),
            'requires_salary'=> $p->candidate->requiresSalaryInHrRound(),
            'salary_post_ops'=> $p->candidate->isSalaryPostOps(),
            'round_id'       => $p->round_id,
            'round_name'     => $p->round->name,
            'is_hr_round'    => $p->round->is_hr_round,
            'is_ops_round'   => $p->round->is_ops_round,
            'status'         => $p->status,
            'started_at'     => $p->start_date?->format('d M Y H:i'),
        ])->values()->all();
    }
}