<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateRoundCustomQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'progress_id',
        'candidate_id',
        'interviewer_id',

        'question_text',
        'question_type',
        'score_category',
        'is_mandatory',
        'options',
        'order',

        'response_text',
        'rating_value',
        'yes_no_value',
        'selected_options',

        'interviewer_notes',
        'question_rating',
        'answered_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'options' => 'array',

        'rating_value' => 'integer',
        'yes_no_value' => 'boolean',
        'selected_options' => 'array',
        'question_rating' => 'decimal:2',

        'answered_at' => 'datetime',
    ];

    public function progress(): BelongsTo
    {
        return $this->belongsTo(
            CandidateRoundProgress::class,
            'progress_id'
        );
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(
            Candidate::class
        );
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function hasResponse(): bool
    {
        return filled($this->response_text)
            || ! is_null($this->rating_value)
            || ! is_null($this->yes_no_value)
            || ! empty($this->selected_options);
    }

    public function getScoreCategoryLabelAttribute(): ?string
    {
        if (!$this->score_category) {
            return null;
        }

        return Question::SCORE_CATEGORIES[$this->score_category]
            ?? ucwords(str_replace('_', ' ', $this->score_category));
    }
}