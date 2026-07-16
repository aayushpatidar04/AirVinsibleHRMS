<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InterviewRound extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sequence_number',
        'description',
        'is_mandatory',
        'is_hr_round',
        'is_ops_round',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_hr_round'  => 'boolean',
        'is_ops_round' => 'boolean',
        'is_active'    => 'boolean',
        'deleted_at'   => 'datetime',
    ];

    /* ─── Boot ─── */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($round) {
            if (empty($round->slug)) {
                $round->slug = Str::slug($round->name);
            }
        });
    }

    /* ─── Relations ─── */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'round_id')->orderBy('order');
    }

    public function mandatoryQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'round_id')
            ->where('is_mandatory', true)
            ->orderBy('order');
    }

    public function candidateProgress(): HasMany
    {
        return $this->hasMany(CandidateRoundProgress::class, 'round_id');
    }

    public function allowedInterviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'interview_round_user', 'interview_round_id', 'user_id')
            ->withTimestamps();
    }

    /* ─── Scopes ─── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_number');
    }

    public function scopeHrRound($query)
    {
        return $query->where('is_hr_round', true);
    }

    public function scopeOpsRound($query)
    {
        return $query->where('is_ops_round', true);
    }

    /* ─── Helpers ─── */

    public function getNextRound(): ?self
    {
        return self::where('sequence_number', '>', $this->sequence_number)
            ->orderBy('sequence_number')
            ->first();
    }

    public function getStats(): array
    {
        $p = $this->candidateProgress();

        return [
            'total'      => (clone $p)->count(),
            'pending'    => (clone $p)->where('status', 'pending')->count(),
            'in_progress'=> (clone $p)->where('status', 'in_progress')->count(),
            'completed'  => (clone $p)->where('status', 'completed')->count(),
            'rejected'   => (clone $p)->where('status', 'rejected')->count(),
            'avg_rating' => round((float)((clone $p)->where('status','completed')->avg('overall_rating') ?? 0), 2),
        ];
    }
}