<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'document_requirement_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'is_verified',
        'verified_by',
        'verified_at',
        'verification_notes',
        'uploaded_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'file_size' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_requirement_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function markVerified(int $userId, ?string $notes = null): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }
}
