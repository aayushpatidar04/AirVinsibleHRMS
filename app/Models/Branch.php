<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all users (interviewers) assigned to this branch
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    /**
     * Get all candidates registered at this branch
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'branch_id');
    }

    /**
     * Get all QR codes for this branch
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'branch_id');
    }

    /**
     * Get all registration forms for this branch
     */
    public function registrationForms(): HasMany
    {
        return $this->hasMany(RegistrationForm::class, 'branch_id');
    }

    /**
     * Scope: Get active branches only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get branch info with counts
     */
    public function getStats(): array
    {
        return [
            'total_candidates'   => $this->candidates()->count(),
            'active_candidates'  => $this->candidates()
                ->whereIn('current_status', ['new', 'in_progress', 'round_completed'])
                ->count(),
            'selected'           => $this->candidates()->where('final_status', 'selected')->count(),
            'total_interviewers' => $this->users()->role('interviewer')->count(),
        ];
    }
}