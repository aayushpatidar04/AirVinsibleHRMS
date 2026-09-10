<?php

namespace App\Models;

use App\Models\Candidate;
use App\Models\CandidateRoundProgress;
use App\Models\InterviewRound;
use App\Models\QrCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_id',
        'password',
        'designation',
        'department',
        'date_of_joining',
        'date_of_birth',
        'employment_type',
        'employment_status',
        'branch_id',
        'reporting_to',
        'avatar',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'is_active',
        'can_interview',
        'deleted_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_joining' => 'date',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'can_interview' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Boot method to auto-update `name`
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->name = trim("{$user->first_name} {$user->last_name}");
        });

        static::updating(function ($user) {
            $user->name = trim("{$user->first_name} {$user->last_name}");
        });
    }

    /* ─── Accessors ─── */

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim("{$this->first_name} {$this->last_name}")
        );
    }

    protected function displayRole(): Attribute
    {
        return Attribute::make(
            get: function () {
                $roles = $this->getRoleNames()->toArray();
                return implode(', ', array_map('ucfirst', $roles));
            }
        );
    }

    /* ─── Relations ─── */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function reportees(): HasMany
    {
        return $this->hasMany(User::class, 'reporting_to');
    }

    public function interviewProgress(): HasMany
    {
        return $this->hasMany(CandidateRoundProgress::class, 'interviewer_id');
    }

    public function assignedCandidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'current_interviewer_id');
    }

    public function allowedRounds(): BelongsToMany
    {
        return $this->belongsToMany(InterviewRound::class, 'interview_round_user', 'user_id', 'interview_round_id')
            ->withTimestamps();
    }

    public function canInterviewRound(InterviewRound $round): bool
    {
        return $this->allowedRounds()->where('interview_rounds.id', $round->id)->exists();
    }

    public function createdQrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'created_by');
    }

    /* ─── Scopes ─── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('employment_status', 'active');
    }

    public function scopeCanInterview($query)
    {
        return $query->where('can_interview', true)->where('is_active', true);
    }

    public function scopeFromBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDepartment($query, string $dept)
    {
        return $query->where('department', $dept);
    }

    public function scopeForRound($query, InterviewRound $round)
    {
        return $query->whereHas('allowedRounds', function ($query) use ($round) {
            $query->where('interview_rounds.id', $round->id);
        });
    }

    public function candidateRoundProgress()
    {
        return $this->hasMany(CandidateRoundProgress::class, 'interviewer_id');
    }

    /* ─── Role Helpers ─── */

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isInterviewer(): bool
    {
        // An employee is considered an interviewer if they have the role
        // OR if the can_interview flag is set
        return $this->hasRole('interviewer') || $this->can_interview;
    }

    public function isHR(): bool
    {
        return $this->hasRole('hr');
    }

    /**
     * Promote this employee to interviewer role
     */
    public function makeInterviewer(): void
    {
        /*
         * Admin and HR retain their primary role.
         * Their interview eligibility is controlled through can_interview
         * and allowedRounds.
         */
        if (!$this->hasAnyRole(['admin', 'hr'])) {
            $this->syncRoles(['interviewer']);
        }

        $this->update([
            'can_interview' => true,
        ]);
    }

    /**
     * Remove interviewer role from employee
     */
    public function removeInterviewerRole(): void
    {
        if ($this->hasRole('interviewer')) {
            $this->removeRole('interviewer');
        }

        $this->allowedRounds()->detach();

        $this->update([
            'can_interview' => false,
        ]);
    }

    /**
     * Get all roles as a readable string
     */
    public function getRolesLabel(): string
    {
        return $this->getRoleNames()
            ->map(fn($r) => ucfirst($r))
            ->implode(', ') ?: 'Employee';
    }

    public function pendingInterviewsCount(): int
    {
        return $this->interviewProgress()->where('status', 'pending')->count();
    }

    public static function findExited(string $email, ?string $phone = null): ?self
    {
        return self::withTrashed()
            ->where('email', $email)
            ->when($phone, fn($q) => $q->orWhere('phone', $phone))
            ->whereNotNull('deleted_at') // Only exited employees
            ->latest('deleted_at') // Most recently exited
            ->first();
    }

    public function isSameDesignation(string $positionApplied): bool
    {
        return strcasecmp($this->designation, $positionApplied) === 0;
    }

    public function primaryRole(): string
    {
        return match (true) {
            $this->hasRole('admin') => 'admin',
            $this->hasRole('hr') => 'hr',
            $this->hasRole('interviewer') || $this->can_interview => 'interviewer',
            default => 'employee',
        };
    }

    public function canAccessBranch(?int $branchId): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if (!$branchId || !$this->branch_id) {
            return false;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function generatedCandidateOffers(): HasMany
    {
        return $this->hasMany(
            CandidateOffer::class,
            'generated_by'
        );
    }
}