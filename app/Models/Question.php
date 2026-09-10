<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'round_id',
        'question_text',
        'question_type',
        'score_category',
        'is_mandatory',
        'is_custom',
        'order',
        'options',
        'created_by',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_custom'    => 'boolean',
        'options'      => 'json',
        'deleted_at'   => 'datetime',
    ];

    public const SCORE_CATEGORIES = [
        'technical' => 'Technical Skills',
        'communication' => 'Communication',
        'problem_solving' => 'Problem Solving',
        'confidence' => 'Confidence',
        'culture_fit' => 'Culture Fit',
        'experience' => 'Experience',
        'leadership' => 'Leadership',
    ];

    /* ─── Relations ─── */

    public function round(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class, 'round_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ─── Scopes ─── */

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getScoreCategoryLabelAttribute(): ?string
    {
        if (!$this->score_category) {
            return null;
        }

        return self::SCORE_CATEGORIES[$this->score_category]
            ?? ucwords(str_replace('_', ' ', $this->score_category));
    }
}