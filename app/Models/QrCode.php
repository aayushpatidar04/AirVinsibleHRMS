<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qr_codes';

    protected $fillable = [
        'uuid',
        'label',
        'branch_id',
        'form_id',
        'expiry_date',
        'is_active',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'is_active'   => 'boolean',
        'deleted_at'  => 'datetime',
    ];

    /* ─── Relations ─── */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'form_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CandidateFormSubmission::class, 'qr_code_id');
    }

    /* ─── Scopes ─── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()->where(function ($q) {
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
        });
    }

    /* ─── Helpers ─── */

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function getRegistrationUrl(): string
    {
        return route('candidate.register', $this->uuid);
    }
}