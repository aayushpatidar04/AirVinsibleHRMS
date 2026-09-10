<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Candidate;
use Illuminate\Support\Collection;

class CandidateActivityService
{
    private const VISIBLE_EVENTS = [
        'candidate.registered',
        'candidate.approval.updated',
        'candidate.status.updated',
        'candidate.selected',
        'candidate.rejected',
        'candidate.held',

        'interview.assigned',
        'interview.reassigned',
        'interview.scheduled',
        'interview.rescheduled',
        'interview.started',
        'interview.completed',
        'interview.rejected',

        'interview.custom_question.added',
        'candidate.round.promoted',
        'candidate.salary.updated',
        'candidate.notification.sent',
    ];

    public function forCandidate(
        Candidate $candidate,
        int $limit = 100
    ): Collection {
        return AuditLog::query()
            ->with('user:id,first_name,last_name,employee_id')
            ->where(
                'model_type',
                $candidate->getMorphClass()
            )
            ->where('model_id', $candidate->id)
            ->whereIn('event', self::VISIBLE_EVENTS)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn(AuditLog $log) => $this->mapLog($log))
            ->values();
    }

    private function mapLog(AuditLog $log): array
    {
        $values = $log->new_values ?? [];

        return [
            'id' => $log->id,
            'event' => $log->event,
            'title' => $this->title($log),
            'description' => $this->description($log),
            'actor' => $log->user?->full_name ?? 'System',
            'actor_employee_id' =>
                $log->user?->employee_id,

            'created_at' =>
                $log->created_at?->format(
                    'd M Y, h:i A'
                ),

            'created_at_raw' =>
                $log->created_at?->toISOString(),

            'icon' => $this->icon($log->event),
            'tone' => $this->tone($log->event),

            'metadata' => [
                'round_name' =>
                    $values['round_name'] ?? null,

                'interviewer_name' =>
                    $values['interviewer_name'] ?? null,

                'rating' =>
                    $values['rating'] ?? null,

                'scheduled_at' =>
                    $values['scheduled_at'] ?? null,
            ],
        ];
    }

    private function title(AuditLog $log): string
    {
        return match ($log->event) {
            'candidate.registered' =>
            'Candidate registered',

            'candidate.approval.updated' =>
            'Approval status updated',

            'candidate.selected' =>
            'Candidate selected',

            'candidate.rejected' =>
            'Candidate rejected',

            'candidate.held' =>
            'Candidate placed on hold',

            'interview.assigned' =>
            'Interview round assigned',

            'interview.reassigned' =>
            'Interviewer reassigned',

            'interview.scheduled' =>
            'Interview scheduled',

            'interview.rescheduled' =>
            'Interview rescheduled',

            'interview.started' =>
            'Interview started',

            'interview.completed' =>
            'Interview completed',

            'interview.rejected' =>
            'Candidate rejected in interview',

            'interview.custom_question.added' =>
            'Custom question added',

            'candidate.round.promoted' =>
            'Candidate moved to next round',

            'candidate.salary.updated' =>
            'Salary details updated',

            'candidate.notification.sent' =>
            'Notification sent',

            default =>
            ucfirst(str_replace(
                ['.', '_'],
                ' ',
                $log->event
            )),
        };
    }

    private function description(AuditLog $log): ?string
    {
        $values = $log->new_values ?? [];

        return match ($log->event) {
            'interview.reassigned' =>
            isset($values['round_name'])
            ? "{$values['round_name']} assigned to "
            . ($values['interviewer_name']
                ?? 'another interviewer')
            : null,

            'interview.scheduled',
            'interview.rescheduled' =>
            isset($values['round_name'])
            ? "{$values['round_name']} schedule updated"
            : null,

            'interview.started' =>
            isset($values['round_name'])
            ? "{$values['round_name']} started"
            : null,

            'interview.completed' =>
            isset($values['round_name'])
            ? "{$values['round_name']} completed"
            : null,

            'candidate.round.promoted' =>
            isset($values['round_name'])
            ? "Moved to {$values['round_name']}"
            : null,

            default => null,
        };
    }

    private function icon(string $event): string
    {
        return match ($event) {
            'candidate.registered' => 'user-plus',

            'candidate.selected' => 'badge-check',

            'candidate.rejected',
            'interview.rejected' => 'circle-x',

            'candidate.held' => 'pause-circle',

            'interview.assigned',
            'interview.reassigned' => 'user-round-cog',

            'interview.scheduled',
            'interview.rescheduled' => 'calendar-clock',

            'interview.started' => 'play',

            'interview.completed' => 'circle-check',

            'interview.custom_question.added' =>
            'message-circle-question',

            'candidate.round.promoted' =>
            'arrow-right-circle',

            'candidate.salary.updated' =>
            'indian-rupee',

            default => 'activity',
        };
    }

    private function tone(string $event): string
    {
        return match ($event) {
            'candidate.selected',
            'interview.completed' => 'success',

            'candidate.rejected',
            'interview.rejected' => 'danger',

            'candidate.held' => 'warning',

            'interview.scheduled',
            'interview.rescheduled' => 'info',

            default => 'neutral',
        };
    }
}