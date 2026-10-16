<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Interview dashboard.
 *
 * Definitions used on this page:
 *  - Scheduled : interview_schedules.status = 'scheduled'
 *  - Ongoing   : candidate_round_progress.status = 'in_progress'
 *  - Pending   : candidate_round_progress.status = 'pending'
 *
 * Every query goes through the existing visibleTo() scopes, so admins see
 * all branches, HR sees their own branch and interviewers see only their
 * assigned interviews. The branch report inherits the same restriction.
 */
class InterviewDashboardController extends Controller
{
    private const LIST_LIMIT = 200;

    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'round_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $canSeeAllBranches = $this->canSeeAllBranches($user);

        // Branch filter is only meaningful for users who can see every branch.
        if (!$canSeeAllBranches) {
            unset($filters['branch_id']);
        }

        return Inertia::render('Recruitment/Interviews/Dashboard', [
            'filters' => [
                'branch_id' => $filters['branch_id'] ?? null,
                'round_id' => $filters['round_id'] ?? null,
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
            ],
            'branches' => $canSeeAllBranches
                ? Branch::orderBy('name')->get(['id', 'name'])
                : [],
            'rounds' => InterviewRound::active()->ordered()->get(['id', 'name']),
            'canSeeAllBranches' => $canSeeAllBranches,
            'stats' => $this->stats($user, $filters),
            'scheduled' => $this->scheduledList($user, $filters),
            'ongoing' => $this->progressList($user, $filters, 'in_progress'),
            'pending' => $this->progressList($user, $filters, 'pending'),
            'branchReport' => $this->branchReport($user, $filters, $canSeeAllBranches),
        ]);
    }

    /* ───────────── Query builders ───────────── */

    private function scheduleQuery(User $user, array $filters): Builder
    {
        return InterviewSchedule::query()
            ->visibleTo($user)
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id) =>
                $q->whereHas('candidate', fn (Builder $c) => $c->where('branch_id', $id)))
            ->when($filters['round_id'] ?? null, fn (Builder $q, $id) =>
                $q->where('round_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $q, $d) =>
                $q->whereDate('scheduled_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) =>
                $q->whereDate('scheduled_at', '<=', $d));
    }

    private function progressQuery(User $user, array $filters): Builder
    {
        return CandidateRoundProgress::query()
            ->visibleTo($user)
            ->whereHas('candidate') // skip soft-deleted candidates
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id) =>
                $q->whereHas('candidate', fn (Builder $c) => $c->where('branch_id', $id)))
            ->when($filters['round_id'] ?? null, fn (Builder $q, $id) =>
                $q->where('round_id', $id));
    }

    /* ───────────── Summary cards ───────────── */

    private function stats(User $user, array $filters): array
    {
        $schedules = fn () => $this->scheduleQuery($user, $filters)
            ->whereHas('candidate')
            ->where('status', 'scheduled');

        $progress = fn (string $status) => $this->progressQuery($user, $filters)
            ->where('status', $status);

        return [
            'scheduled' => $schedules()->count(),
            'scheduled_today' => $schedules()->whereDate('scheduled_at', today())->count(),
            'overdue' => $schedules()->where('scheduled_at', '<', now())->count(),
            'ongoing' => $progress('in_progress')->count(),
            'pending' => $progress('pending')->count(),
            'completed_today' => $this->progressQuery($user, $filters)
                ->where('status', 'completed')
                ->whereDate('end_date', today())
                ->count(),
        ];
    }

    /* ───────────── Lists ───────────── */

    private function scheduledList(User $user, array $filters): array
    {
        return $this->scheduleQuery($user, $filters)
            ->whereHas('candidate')
            ->where('status', 'scheduled')
            ->with([
                'candidate:id,first_name,last_name,position_applied,branch_id',
                'candidate.branch:id,name',
                'round:id,name',
                'interviewer:id,first_name,last_name',
            ])
            ->orderBy('scheduled_at')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (InterviewSchedule $s) => [
                'id' => $s->id,
                'candidate_id' => $s->candidate_id,
                'candidate' => $s->candidate->full_name,
                'position' => $s->candidate->position_applied,
                'branch' => $s->candidate->branch?->name,
                'round' => $s->round?->name,
                'interviewer' => $s->interviewer?->full_name,
                'scheduled_at' => $s->scheduled_at->toIso8601String(),
                'scheduled_label' => $s->formattedTime(),
                'is_overdue' => $s->scheduled_at->isPast(),
                'is_today' => $s->scheduled_at->isToday(),
                'gmeet_link' => $s->gmeet_link,
                'email_sent' => $s->sent_at !== null,
            ])
            ->values()
            ->all();
    }

    private function progressList(User $user, array $filters, string $status): array
    {
        $query = $this->progressQuery($user, $filters)
            ->where('status', $status)
            ->with([
                'candidate:id,first_name,last_name,position_applied,branch_id',
                'candidate.branch:id,name',
                'round:id,name',
                'interviewer:id,first_name,last_name',
            ]);

        $status === 'in_progress'
            ? $query->orderBy('start_date')
            : $query->orderBy('created_at');

        return $query->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (CandidateRoundProgress $p) => [
                'id' => $p->id,
                'candidate_id' => $p->candidate_id,
                'round_id' => $p->round_id,
                'candidate' => $p->candidate->full_name,
                'position' => $p->candidate->position_applied,
                'branch' => $p->candidate->branch?->name,
                'round' => $p->round?->name,
                'interviewer' => $p->interviewer?->full_name,
                'started_at' => $p->start_date?->format('d M Y, h:i A'),
                'running_minutes' => $p->start_date
                    ? (int) $p->start_date->diffInMinutes(now())
                    : null,
                'waiting_since' => $p->created_at?->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    /* ───────────── Branch-wise report ───────────── */

    private function branchReport(User $user, array $filters, bool $allBranches): array
    {
        // Scheduled interviews per branch.
        $scheduleRows = $this->scheduleQuery($user, $filters)
            ->whereHas('candidate')
            ->join('candidates', 'candidates.id', '=', 'interview_schedules.candidate_id')
            ->selectRaw('candidates.branch_id as branch_id')
            ->selectRaw("SUM(CASE WHEN interview_schedules.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled")
            ->selectRaw("SUM(CASE WHEN interview_schedules.status = 'scheduled' AND interview_schedules.scheduled_at < ? THEN 1 ELSE 0 END) as overdue", [now()])
            ->selectRaw("SUM(CASE WHEN interview_schedules.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->selectRaw("SUM(CASE WHEN interview_schedules.status = 'no_show' THEN 1 ELSE 0 END) as no_show")
            ->groupBy('candidates.branch_id')
            ->get()
            ->keyBy('branch_id');

        // Round progress per branch.
        $progressRows = $this->progressQuery($user, $filters)
            ->join('candidates', 'candidates.id', '=', 'candidate_round_progress.candidate_id')
            ->selectRaw('candidates.branch_id as branch_id')
            ->selectRaw("SUM(CASE WHEN candidate_round_progress.status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN candidate_round_progress.status = 'in_progress' THEN 1 ELSE 0 END) as ongoing")
            ->selectRaw("SUM(CASE WHEN candidate_round_progress.status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN candidate_round_progress.status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->selectRaw('AVG(candidate_round_progress.overall_rating) as avg_rating')
            ->selectRaw('AVG(candidate_round_progress.duration_minutes) as avg_duration')
            ->groupBy('candidates.branch_id')
            ->get()
            ->keyBy('branch_id');

        // Admins see every branch (including those with no activity);
        // everyone else only sees branches that have visible activity.
        $branchIds = $allBranches && empty($filters['branch_id'])
            ? Branch::pluck('id')
            : collect($scheduleRows->keys())->merge($progressRows->keys())->unique();

        $branches = Branch::whereIn('id', $branchIds)->orderBy('name')->get(['id', 'name', 'code']);

        $rows = $branches->map(function (Branch $branch) use ($scheduleRows, $progressRows) {
            $s = $scheduleRows->get($branch->id);
            $p = $progressRows->get($branch->id);

            $completed = (int) ($p->completed ?? 0);
            $rejected = (int) ($p->rejected ?? 0);
            $finished = $completed + $rejected;

            return [
                'branch_id' => $branch->id,
                'branch' => $branch->name,
                'code' => $branch->code,
                'scheduled' => (int) ($s->scheduled ?? 0),
                'overdue' => (int) ($s->overdue ?? 0),
                'cancelled' => (int) ($s->cancelled ?? 0),
                'no_show' => (int) ($s->no_show ?? 0),
                'pending' => (int) ($p->pending ?? 0),
                'ongoing' => (int) ($p->ongoing ?? 0),
                'completed' => $completed,
                'rejected' => $rejected,
                'pass_rate' => $finished > 0 ? round($completed / $finished * 100, 1) : null,
                'avg_rating' => isset($p->avg_rating) ? round((float) $p->avg_rating, 2) : null,
                'avg_duration' => isset($p->avg_duration) ? (int) round((float) $p->avg_duration) : null,
            ];
        })->values();

        return [
            'rows' => $rows->all(),
            'totals' => $this->totals($rows),
        ];
    }

    private function totals(Collection $rows): array
    {
        return collect(['scheduled', 'overdue', 'cancelled', 'no_show', 'pending', 'ongoing', 'completed', 'rejected'])
            ->mapWithKeys(fn ($key) => [$key => (int) $rows->sum($key)])
            ->all();
    }

    private function canSeeAllBranches(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('interviews.view-all-branches');
    }
}
