<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Profiles where salary discussion is mandatory in the OPS round.
     * Advisors & Executives must receive salary discussion/offering in the OPS round.
     */
    const OPS_ROUND_SALARY_PROFILES = ['advisory_executive'];

    /**
     * Profiles where salary discussion is mandatory in the HR round.
     * All other profiles must receive salary discussion/offering in the HR round.
     */
    const HR_ROUND_SALARY_PROFILES = ['leadership', 'other'];

    const PROFILE_LABELS = [
        'advisory_executive' => 'Advisor / Executive',
        'leadership' => 'TL / QA / AM / OM',
        'other' => 'Other',
    ];

    const CURRENT_STATUSES = [
        'new' => 'New',
        'in_progress' => 'In Progress',
        'round_completed' => 'Round Completed',
        'all_rounds_cleared' => 'All Rounds Cleared',
        'rejected' => 'Rejected',
    ];

    const FINAL_STATUSES = [
        'pending' => 'Decision Pending',
        'selected' => 'Selected',
        'not_selected' => 'Not Selected',
    ];

    public const APPLICANT_TYPES = [
        'Fresher' => 'Fresher',
        'Experienced' => 'Experienced',
        'Rejoining' => 'Rejoining',
    ];

    public const APPROVAL_STATUSES = [
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'position_applied',
        'profile_category',
        'branch_id',
        'current_round_id',
        'current_interviewer_id',
        'current_status',
        'final_status',
        'hiring_notes',
        'final_salary_offered',
        'final_ctc_offered',
        'final_in_hand_offered',
        'final_pf_allowed',
        'final_designation',
        'registration_date',
        'approval_status',
        'old_employee_id',
        'approval_notes',
        'approved_at',
        'approved_by',
        'applicant_type',
        'process_name',
        'rejection_reason',
        'rejection_remarks',
        'hold_reason',
        'hold_remarks',
        'salary_annexure',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'final_salary_offered' => 'decimal:2',
        'final_ctc_offered' => 'decimal:2',
        'final_in_hand_offered' => 'decimal:2',
        'final_pf_allowed' => 'boolean',
        'deleted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['full_name', 'latest_submission'];

    /* ─── Accessors ─── */

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim("{$this->first_name} {$this->last_name}")
        );
    }

    /* ─── Relations ─── */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currentRound(): BelongsTo
    {
        return $this->belongsTo(InterviewRound::class, 'current_round_id');
    }

    public function currentInterviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_interviewer_id');
    }

    public function roundProgress(): HasMany
    {
        return $this->hasMany(CandidateRoundProgress::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    

    public function formSubmission(): HasOne
    {
        return $this->hasOne(CandidateFormSubmission::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CandidateFormSubmission::class, 'candidate_id')
            ->orderByDesc('submitted_at');
    }

    public function latestSubmission(): HasOne
    {
        return $this->hasOne(CandidateFormSubmission::class, 'candidate_id')
            ->latestOfMany('submitted_at');
    }

    public function getLatestSubmissionAttribute(): ?CandidateFormSubmission
    {
        return $this->submissions()->first();
    }

    /* ─── Scopes ─── */

    public function scopeByStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeSelected($query)
    {
        return $query->where('final_status', 'selected');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('current_status', ['new', 'in_progress', 'round_completed']);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('registration_date', '>=', now()->subDays($days));
    }

    public function scopeRejoining($query)
    {
        return $query->where('applicant_type', 'Rejoining');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('applicant_type', 'Rejoining')
            ->where('approval_status', 'pending');
    }

    /* ─── Business Logic ─── */

    /**
     * Is salary offer mandatory in HR round for this candidate?
     * YES for leadership and other profiles.
     */
    public function requiresSalaryInHrRound(): bool
    {
        return in_array($this->profile_category, self::HR_ROUND_SALARY_PROFILES);
    }

    /**
     * Is salary offer mandatory in OPS round for this candidate?
     * YES for Advisor / Executive profiles.
     */
    public function requiresSalaryInOpsRound(): bool
    {
        return in_array($this->profile_category, self::OPS_ROUND_SALARY_PROFILES);
    }

    public function isSalaryPostOps(): bool
    {
        return $this->requiresSalaryInOpsRound();
    }

    public function getProfileCategoryLabel(): string
    {
        return self::PROFILE_LABELS[$this->profile_category] ?? 'Other';
    }

    public function getApplicantTypeLabel(): string
    {
        $type = $this->applicant_type;
        if ($type === null) {
            return 'Unknown';
        }

        return self::APPLICANT_TYPES[$type] ?? self::APPLICANT_TYPES[ucfirst(strtolower($type))] ?? $type;
    }

    public function getApprovalStatusLabel(): ?string
    {
        return $this->approval_status ? self::APPROVAL_STATUSES[$this->approval_status] : null;
    }

    public function getAverageRating(): ?float
    {
        $avg = $this->roundProgress()
            ->where('status', 'completed')
            ->avg('overall_rating');

        return $avg ? round((float) $avg, 2) : null;
    }

    public function completedRoundsCount(): int
    {
        return $this->roundProgress()->where('status', 'completed')->count();
    }

    public function getLatestSalaryOffer(): ?string
    {
        $progress = $this->roundProgress()
            ->where(function ($query) {
                $query->whereNotNull('salary_offer_max')
                    ->orWhereNotNull('salary_offer_min')
                    ->orWhereNotNull('salary_offer_amount');
            })
            ->latest('created_at')
            ->first();

        if (!$progress) {
            return null;
        }

        if (!is_null($progress->salary_offer_min) && !is_null($progress->salary_offer_max)) {
            return sprintf(
                '₹%s - ₹%s',
                number_format($progress->salary_offer_min, 0, '.', ','),
                number_format($progress->salary_offer_max, 0, '.', ',')
            );
        }

        if (!is_null($progress->salary_offer_min)) {
            return 'From ₹' . number_format($progress->salary_offer_min, 0, '.', ',');
        }

        if (!is_null($progress->salary_offer_max)) {
            return 'Up to ₹' . number_format($progress->salary_offer_max, 0, '.', ',');
        }

        return '₹' . number_format($progress->salary_offer_amount, 0, '.', ',');
    }

    public static function hasActiveApplication(string $email, string $phone, int $cooldownDays = 30): bool
    {
        return self::where('email', $email)
            ->where('phone', $phone)
            ->where('registration_date', '>=', now()->subDays($cooldownDays))
            ->where('final_status', '!=', 'selected') // Selected candidates can reapply? Adjust as needed
            ->exists();
    }

    /**
     * Get latest application for email+phone combo
     */
    public static function latestByContact(string $email, string $phone): ?self
    {
        return self::where('email', $email)
            ->where('phone', $phone)
            ->latest('registration_date')
            ->first();
    }

    /**
     * Get all applications grouped by email (for reporting)
     */
    public static function allApplicationsByEmail(?string $email = null)
    {
        $query = self::query()
            ->with(['submissions', 'roundProgress', 'branch']);

        if ($email) {
            $query->where('email', $email);
        }

        return $query->get()
            ->groupBy('email')
            ->map(fn($group) => $group->sortByDesc('registration_date')->values());
    }

    public function oldEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'old_employee_id')->withTrashed();
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isRejoining(): bool
    {
        return strcasecmp($this->applicant_type ?? '', 'Rejoining') === 0;
    }

    public function isFresher(): bool
    {
        return strcasecmp($this->applicant_type ?? '', 'Fresher') === 0;
    }

    public function isExperienced(): bool
    {
        return strcasecmp($this->applicant_type ?? '', 'Experienced') === 0;
    }

    public function requiresApproval(): bool
    {
        return $this->isRejoining() && $this->old_employee_id !== null;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isApprovalRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function canStartInterviews(): bool
    {
        if ($this->isRejoining()) {
            return $this->isApproved();
        }
        return true;
    }
}