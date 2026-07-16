<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'question_id',
        'progress_id',
        'response_text',
        'rating_value',
        'yes_no_value',
        'selected_options',
        'interviewer_notes',
        'question_rating',
    ];

    protected $casts = [
        'yes_no_value'     => 'boolean',
        'selected_options' => 'json',
        'question_rating'  => 'decimal:2',
    ];

    /* ─── Relations ─── */

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function progress(): BelongsTo
    {
        return $this->belongsTo(CandidateRoundProgress::class, 'progress_id');
    }
}