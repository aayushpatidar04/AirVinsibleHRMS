<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'round_id',
        'interviewer_id',
        'scheduled_at',
        'gmeet_link',
        'notes',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function round()
    {
        return $this->belongsTo(InterviewRound::class, 'round_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function isUpcoming()
    {
        return $this->scheduled_at->isFuture() && $this->status === 'scheduled';
    }

    public function isPast()
    {
        return $this->scheduled_at->isPast();
    }

    public function formattedTime()
    {
        return $this->scheduled_at->format('d M Y, h:i A');
    }

    public function timeUntil()
    {
        return $this->scheduled_at->diffForHumans();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (
            $user->hasRole('admin')
            || $user->can('interviews.view-all-branches')
        ) {
            return $query;
        }

        if (
            $user->hasRole('hr')
            && $user->can('interviews.view-branch')
        ) {
            return $query->whereHas(
                'candidate',
                fn(Builder $candidateQuery) =>
                $candidateQuery->where('branch_id', $user->branch_id)
            );
        }

        if (
            $user->isInterviewer()
            && $user->can('interviews.view-assigned')
        ) {
            return $query->where('interviewer_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}