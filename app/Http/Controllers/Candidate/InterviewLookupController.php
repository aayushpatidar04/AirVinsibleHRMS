<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewSchedule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public "where do I go?" page for candidates and reception.
 *
 * A candidate (or the receptionist) enters the candidate's email and gets
 * the current interview: round, interviewer and time.
 *
 * This endpoint is public, so it deliberately discloses very little:
 * first name + last initial, position, branch, round, interviewer name and
 * designation, and the time. No phone, feedback, ratings, links or history.
 * "Unknown email" and "no active interview" return the same response so the
 * page can't be used to check whether an email is in the system.
 */
class InterviewLookupController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Candidate/InterviewLookup', [
            'result' => null,
            'searched' => false,
        ]);
    }

    public function search(Request $request): Response
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        return Inertia::render('Candidate/InterviewLookup', [
            'result' => $this->find(mb_strtolower(trim($data['email']))),
            'searched' => true,
        ]);
    }

    private function find(string $email): ?array
    {
        $progress = CandidateRoundProgress::query()
            ->whereIn('status', ['in_progress', 'pending'])
            ->whereHas('candidate', fn ($q) => $q
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('current_status', '!=', 'rejected'))
            ->with([
                'candidate:id,first_name,last_name,position_applied,branch_id,registration_date,current_round_id',
                'candidate.branch:id,name',
                'round:id,name',
                'interviewer:id,first_name,last_name,designation',
            ])
            ->get()
            // Interview already running first, then the newest application,
            // then the round the candidate is currently on.
            ->sortBy([
                fn ($a, $b) => ($a->status === 'in_progress' ? 0 : 1) <=> ($b->status === 'in_progress' ? 0 : 1),
                fn ($a, $b) => $b->candidate->registration_date <=> $a->candidate->registration_date,
                fn ($a, $b) => ($a->round_id === $a->candidate->current_round_id ? 0 : 1)
                    <=> ($b->round_id === $b->candidate->current_round_id ? 0 : 1),
            ])
            ->first();

        if (!$progress) {
            return null;
        }

        $schedule = $this->relevantSchedule($progress);
        $candidate = $progress->candidate;

        return [
            'candidate' => trim($candidate->first_name . ' ' . mb_substr((string) $candidate->last_name, 0, 1)) . '.',
            'position' => $candidate->position_applied,
            'branch' => $candidate->branch?->name,
            'round' => $progress->round?->name,
            'interviewer' => $progress->interviewer ? [
                'name' => $progress->interviewer->full_name,
                'designation' => $progress->interviewer->designation,
            ] : null,
            'state' => $this->state($progress, $schedule),
            'scheduled_label' => $schedule?->scheduled_at->format('d M Y, h:i A'),
            'scheduled_time' => $schedule?->scheduled_at->format('h:i A'),
        ];
    }

    /**
     * The schedule for this candidate and round: today's or the next upcoming
     * one, otherwise the most recent past one.
     */
    private function relevantSchedule(CandidateRoundProgress $progress): ?InterviewSchedule
    {
        $schedules = InterviewSchedule::query()
            ->where('candidate_id', $progress->candidate_id)
            ->where('round_id', $progress->round_id)
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->get();

        return $schedules->first(fn ($s) => $s->scheduled_at->gte(today()))
            ?? $schedules->last();
    }

    private function state(CandidateRoundProgress $progress, ?InterviewSchedule $schedule): string
    {
        if ($progress->status === 'in_progress') {
            return 'in_progress';
        }

        if (!$schedule) {
            return 'unscheduled';
        }

        if ($schedule->scheduled_at->isToday()) {
            return 'today';
        }

        return $schedule->scheduled_at->isFuture() ? 'upcoming' : 'missed';
    }
}
