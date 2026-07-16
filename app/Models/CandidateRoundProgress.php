<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateRoundProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidate_round_progress';

    protected $fillable = [
        'candidate_id',
        'round_id',
        'interviewer_id',
        'status',
        'start_date',
        'end_date',
        'duration_minutes',
        'overall_feedback',
        'overall_rating',
        'rejection_reason',
        'salary_offer_amount',
        'salary_offer_min',
        'salary_offer_max',
        'offered_designation',
        'salary_offer_status',
        'next_round_id',
        'next_interviewer_id',
    ];

    protected $casts = [
        'start_date'          => 'datetime',
        'end_date'            => 'datetime',
        'salary_offer_amount' => 'decimal:2',
        'salary_offer_min'    => 'decimal:2',
        'salary_offer_max'    => 'decimal:2',
        'overall_rating'      => 'decimal:2',
        'deleted_at'          => 'datetime',
    ];

    /* ─── Relations ─── */

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class, 'round_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function nextRound(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class, 'next_round_id');
    }

    public function nextInterviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_interviewer_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'progress_id');
    }

    /* ─── Scopes ─── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /* ─── Actions ─── */

    public function startInterview(): bool
    {
        return $this->update([
            'status'     => 'in_progress',
            'start_date' => now(),
        ]);
    }

    public function completeRound(array $data): bool
    {
        $endDate  = now();
        $duration = $this->start_date
            ? (int) $this->start_date->diffInMinutes($endDate)
            : null;

        return $this->update(array_merge($data, [
            'status'           => 'completed',
            'end_date'         => $endDate,
            'duration_minutes' => $duration,
        ]));
    }

    public function rejectCandidate(string $reason): bool
    {
        $endDate = now();

        return $this->update([
            'status'           => 'rejected',
            'end_date'         => $endDate,
            'rejection_reason' => $reason,
            'duration_minutes' => $this->start_date
                ? (int) $this->start_date->diffInMinutes($endDate)
                : null,
        ]);
    }

    /* ─── Validation Helpers ─── */

    public function allMandatoryQuestionsAnswered(): bool
    {
        $mandatoryIds = $this->round->mandatoryQuestions()->pluck('id');
        $answeredIds  = $this->responses()->whereIn('question_id', $mandatoryIds)->pluck('question_id');

        return $mandatoryIds->diff($answeredIds)->isEmpty();
    }

    public function unansweredMandatoryQuestions()
    {
        $answeredIds = $this->responses()->pluck('question_id');

        return $this->round->mandatoryQuestions()
            ->whereNotIn('id', $answeredIds)
            ->get();
    }

    /* ─── Display Helpers ─── */

    public function getDurationLabel(): string
    {
        if (! $this->duration_minutes) {
            return '—';
        }

        $hours   = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    public function getRatingStars(): string
    {
        if (! $this->overall_rating) {
            return '—';
        }

        $full  = str_repeat('★', (int) $this->overall_rating);
        $empty = str_repeat('☆', 5 - (int) $this->overall_rating);

        return $full . $empty;
    }
}