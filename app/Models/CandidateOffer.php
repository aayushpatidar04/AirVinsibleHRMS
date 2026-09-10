<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CandidateOffer extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Offer Status Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SENT = 'sent';

    public const STATUS_VIEWED = 'viewed';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Employment Type Constants
    |--------------------------------------------------------------------------
    */

    public const EMPLOYMENT_FULL_TIME = 'full_time';

    public const EMPLOYMENT_PART_TIME = 'part_time';

    public const EMPLOYMENT_CONTRACT = 'contract';

    public const EMPLOYMENT_INTERNSHIP = 'internship';

    public const EMPLOYMENT_CONSULTANT = 'consultant';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'candidate_id',
        'offer_number',
        'version',
        'status',
        'public_token',

        'process_name',
        'designation',
        'department',
        'branch_id',
        'reporting_manager_id',
        'employment_type',
        'probation_months',
        'joining_date',
        'reporting_time',
        'work_location',

        'salary_ctc',
        'salary_in_hand',
        'salary_basic',
        'salary_hra',
        'salary_special_allowance',
        'salary_pf',
        'salary_bonus',
        'salary_variable',
        'salary_other_allowances',
        'pf_allowed',
        'salary_structure_json',

        'salary_annexure',
        'offer_terms',
        'internal_remarks',
        'offer_valid_till',

        'pdf_path',
        'pdf_generated_at',

        'generated_by',
        'approved_by',
        'approved_at',

        'sent_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'expired_at',
        'cancelled_at',
        'decline_reason',

        'sent_to_email',
        'email_subject',
        'email_message',
        'email_cc',
        'send_count',
        'sent_by',

        'portal_token_hash',
        'portal_token_created_at',
        'portal_token_expires_at',
        'portal_token_revoked_at',
        'viewed_ip',
        'viewed_user_agent',
        'responded_at',
        'response_ip',
        'response_user_agent',
        'candidate_consent',
        'candidate_response_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'version' => 'integer',

        'branch_id' => 'integer',
        'reporting_manager_id' => 'integer',
        'probation_months' => 'integer',

        'joining_date' => 'date',
        'offer_valid_till' => 'date',

        'salary_ctc' => 'decimal:2',
        'salary_in_hand' => 'decimal:2',
        'salary_basic' => 'decimal:2',
        'salary_hra' => 'decimal:2',
        'salary_special_allowance' => 'decimal:2',
        'salary_pf' => 'decimal:2',
        'salary_bonus' => 'decimal:2',
        'salary_variable' => 'decimal:2',
        'salary_other_allowances' => 'decimal:2',

        'pf_allowed' => 'boolean',

        'salary_structure_json' => 'array',

        'pdf_generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'responded_at' => 'datetime',
        'declined_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'email_cc' => 'array',
        'send_count' => 'integer',

        'portal_token_created_at' => 'datetime',
        'portal_token_expires_at' => 'datetime',
        'portal_token_revoked_at' => 'datetime',

        'candidate_consent' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'status_label',
        'employment_type_label',
        'can_edit',
        'can_submit_for_approval',
        'can_approve',
        'can_send',
        'can_cancel',
        'is_closed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Booting
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (CandidateOffer $offer) {
            if (!$offer->status) {
                $offer->status = self::STATUS_DRAFT;
            }

            if (!$offer->version) {
                $offer->version = 1;
            }

            if (!$offer->offer_number) {
                $offer->offer_number = self::generateOfferNumber();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reporting_manager_id'
        );
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'generated_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public static function approvalStatusOptions(): array
    {
        return [
            'not_submitted' => 'Not Submitted',
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_DRAFT
        );
    }

    public function scopePendingApproval(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_PENDING_APPROVAL
        );
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_APPROVED
        );
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ]);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ]);
    }

    public function scopeForCandidate(
        Builder $query,
        int $candidateId
    ): Builder {
        return $query->where(
            'candidate_id',
            $candidateId
        );
    }

    public function scopeLatestVersion(
        Builder $query
    ): Builder {
        return $query
            ->orderByDesc('version')
            ->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status]
            ?? Str::headline($this->status);
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return self::employmentTypeOptions()[
            $this->employment_type
        ] ?? Str::headline($this->employment_type);
    }

    public function getCanEditAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_APPROVAL,
        ], true);
    }

    public function getCanSubmitForApprovalAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getCanApproveAttribute(): bool
    {
        return $this->status ===
            self::STATUS_PENDING_APPROVAL;
    }

    public function getCanSendAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getCanCancelAttribute(): bool
    {
        return !in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL =>
                'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_SENT => 'Sent',
            self::STATUS_VIEWED => 'Viewed',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function employmentTypeOptions(): array
    {
        return [
            self::EMPLOYMENT_FULL_TIME =>
                'Full Time',

            self::EMPLOYMENT_PART_TIME =>
                'Part Time',

            self::EMPLOYMENT_CONTRACT =>
                'Contract',

            self::EMPLOYMENT_INTERNSHIP =>
                'Internship',

            self::EMPLOYMENT_CONSULTANT =>
                'Consultant',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Public Token
    |--------------------------------------------------------------------------
    */

    public function ensurePublicToken(): string
    {
        if (!$this->public_token) {
            $this->forceFill([
                'public_token' => (string) Str::uuid(),
            ])->save();
        }

        return $this->public_token;
    }

    public function regeneratePublicToken(): string
    {
        $token = (string) Str::uuid();

        $this->forceFill([
            'public_token' => $token,
        ])->save();

        return $token;
    }

    /*
    |--------------------------------------------------------------------------
    | Offer Lifecycle Actions
    |--------------------------------------------------------------------------
    */

    public function markPendingApproval(): void
    {
        if (!$this->can_submit_for_approval) {
            return;
        }

        $this->forceFill([
            'status' =>
                self::STATUS_PENDING_APPROVAL,
        ])->save();
    }

    public function markApproved(
        ?int $approvedBy = null
    ): void {
        $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ])->save();
    }

    public function markSent(): void
    {
        $this->ensurePublicToken();

        $this->forceFill([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ])->save();
    }

    public function markViewed(): void
    {
        if (
            !in_array($this->status, [
                self::STATUS_SENT,
                self::STATUS_VIEWED,
            ], true)
        ) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_VIEWED,
            'viewed_at' =>
                $this->viewed_at ?? now(),
        ])->save();
    }

    public function markAccepted(): void
    {
        $this->forceFill([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'declined_at' => null,
            'decline_reason' => null,
        ])->save();
    }

    public function markDeclined(
        ?string $reason = null
    ): void {
        $this->forceFill([
            'status' => self::STATUS_DECLINED,
            'declined_at' => now(),
            'decline_reason' => $reason,
            'accepted_at' => null,
        ])->save();
    }

    public function markExpired(): void
    {
        $this->forceFill([
            'status' => self::STATUS_EXPIRED,
            'expired_at' => now(),
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Revision Helpers
    |--------------------------------------------------------------------------
    */

    public function createRevision(
        ?int $generatedBy = null
    ): CandidateOffer {
        return DB::transaction(function () use ($generatedBy) {
            $latestVersion = self::query()
                ->where(
                    'candidate_id',
                    $this->candidate_id
                )
                ->lockForUpdate()
                ->max('version');

            $revision = $this->replicate([
                'offer_number',
                'public_token',
                'status',
                'pdf_path',
                'pdf_generated_at',
                'approved_by',
                'approved_at',
                'sent_at',
                'viewed_at',
                'accepted_at',
                'declined_at',
                'expired_at',
                'cancelled_at',
                'decline_reason',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $revision->fill([
                'offer_number' =>
                    self::generateOfferNumber(),

                'version' =>
                    ((int) $latestVersion) + 1,

                'status' =>
                    self::STATUS_DRAFT,

                'generated_by' =>
                    $generatedBy,

                'public_token' => null,
                'pdf_path' => null,
                'pdf_generated_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'sent_at' => null,
                'viewed_at' => null,
                'accepted_at' => null,
                'declined_at' => null,
                'expired_at' => null,
                'cancelled_at' => null,
                'decline_reason' => null,
            ]);

            $revision->save();

            $this->loadMissing('salaryComponents');

            $componentIdMap = [];

            foreach (
                $this->salaryComponents as $component
            ) {
                $newComponent =
                    $revision->salaryComponents()->create([
                        'component_name' =>
                            $component->component_name,

                        'component_type' =>
                            $component->component_type,

                        'calculation_type' =>
                            $component->calculation_type,

                        'percentage_of_component_id' =>
                            null,

                        'amount' =>
                            $component->amount,

                        'percentage' =>
                            $component->percentage,

                        'frequency' =>
                            $component->frequency,

                        'show_in_offer' =>
                            $component->show_in_offer,

                        'affects_in_hand' =>
                            $component->affects_in_hand,

                        'is_taxable' =>
                            $component->is_taxable,

                        'sort_order' =>
                            $component->sort_order,

                        'description' =>
                            $component->description,
                    ]);

                $componentIdMap[
                    $component->id
                ] = $newComponent->id;
            }

            foreach (
                $this->salaryComponents as $component
            ) {
                if (
                    !$component->percentage_of_component_id
                ) {
                    continue;
                }

                $newComponentId =
                    $componentIdMap[$component->id]
                    ?? null;

                $newParentId =
                    $componentIdMap[
                        $component
                            ->percentage_of_component_id
                    ]
                    ?? null;

                if (!$newComponentId) {
                    continue;
                }

                OfferSalaryComponent::query()
                    ->whereKey($newComponentId)
                    ->update([
                        'percentage_of_component_id' =>
                            $newParentId,
                    ]);
            }

            return $revision;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Offer Number Generation
    |--------------------------------------------------------------------------
    */

    public static function generateOfferNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->format('Y');

            $latestOffer = self::withTrashed()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = 1;

            if ($latestOffer) {
                $existingSequence =
                    self::extractOfferSequence(
                        $latestOffer->offer_number
                    );

                $sequence = $existingSequence + 1;
            }

            return sprintf(
                'OFR-%s-%05d',
                $year,
                $sequence
            );
        });
    }

    protected static function extractOfferSequence(
        ?string $offerNumber
    ): int {
        if (
            !$offerNumber ||
            !preg_match(
                '/OFR-\d{4}-(\d+)$/',
                $offerNumber,
                $matches
            )
        ) {
            return 0;
        }

        return (int) $matches[1];
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(
            OfferSalaryComponent::class,
            'candidate_offer_id'
        )->ordered();
    }

    public function earningComponents(): HasMany
    {
        return $this->hasMany(
            OfferSalaryComponent::class,
            'candidate_offer_id'
        )
            ->where(
                'component_type',
                OfferSalaryComponent::TYPE_EARNING
            )
            ->ordered();
    }

    public function deductionComponents(): HasMany
    {
        return $this->hasMany(
            OfferSalaryComponent::class,
            'candidate_offer_id'
        )
            ->where(
                'component_type',
                OfferSalaryComponent::TYPE_DEDUCTION
            )
            ->ordered();
    }

    public function employerContributionComponents(): HasMany
    {
        return $this->hasMany(
            OfferSalaryComponent::class,
            'candidate_offer_id'
        )
            ->where(
                'component_type',
                OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION
            )
            ->ordered();
    }

    public function calculateMonthlyGross(): float
    {
        $this->loadMissing(
            'salaryComponents.percentageOfComponent'
        );

        return round(
            $this->salaryComponents
                ->filter(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->component_type ===
                    OfferSalaryComponent::TYPE_EARNING
                )
                ->sum(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->toMonthlyAmount(
                        $component->calculateAmount()
                    )
                ),
            2
        );
    }

    public function calculateMonthlyDeductions(): float
    {
        $this->loadMissing(
            'salaryComponents.percentageOfComponent'
        );

        return round(
            $this->salaryComponents
                ->filter(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->component_type ===
                    OfferSalaryComponent::TYPE_DEDUCTION
                    && $component->affects_in_hand
                )
                ->sum(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->toMonthlyAmount(
                        $component->calculateAmount()
                    )
                ),
            2
        );
    }

    public function calculateMonthlyEmployerContributions(): float
    {
        $this->loadMissing(
            'salaryComponents.percentageOfComponent'
        );

        return round(
            $this->salaryComponents
                ->filter(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->component_type ===
                    OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION
                )
                ->sum(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->toMonthlyAmount(
                        $component->calculateAmount()
                    )
                ),
            2
        );
    }

    public function calculateMonthlyInHand(): float
    {
        return round(
            $this->calculateMonthlyGross()
            - $this->calculateMonthlyDeductions(),
            2
        );
    }

    public function calculateAnnualCtc(): float
    {
        $this->loadMissing(
            'salaryComponents.percentageOfComponent'
        );

        return round(
            $this->salaryComponents
                ->filter(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    in_array(
                        $component->component_type,
                        [
                            OfferSalaryComponent::TYPE_EARNING,
                            OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION,
                        ],
                        true
                    )
                )
                ->sum(
                    fn(
                    OfferSalaryComponent $component
                ) =>
                    $component->toAnnualAmount(
                        $component->calculateAmount()
                    )
                ),
            2
        );
    }

    public function synchronizeSalarySummary(): void
    {
        $this->forceFill([
            'salary_in_hand' =>
                $this->calculateMonthlyInHand(),

            'salary_ctc' =>
                $this->calculateAnnualCtc(),
        ])->save();
    }

    public function recalculateSalaryComponents(): void
    {
        $this->loadMissing(
            'salaryComponents.percentageOfComponent'
        );

        foreach (
            $this->salaryComponents as $component
        ) {
            if (
                $component->calculation_type ===
                OfferSalaryComponent::CALCULATION_PERCENTAGE
            ) {
                $component->recalculate();
            }
        }

        $this->unsetRelation('salaryComponents');

        $this->synchronizeSalarySummary();
    }

    public function createDefaultSalaryComponents(): void
    {
        if ($this->salaryComponents()->exists()) {
            return;
        }

        $defaults =
            OfferSalaryComponent::defaultComponents();

        $createdComponents = [];

        foreach ($defaults as $componentData) {
            $component =
                $this->salaryComponents()->create(
                    $componentData
                );

            $createdComponents[] =
                $component;
        }

        $basicComponent =
            collect($createdComponents)->first(
                fn(
                OfferSalaryComponent $component
            ) =>
                strtolower(
                    $component->component_name
                ) === 'basic salary'
            );

        if (!$basicComponent) {
            return;
        }

        collect($createdComponents)
            ->filter(
                fn(
                OfferSalaryComponent $component
            ) =>
                $component->calculation_type ===
                OfferSalaryComponent::CALCULATION_PERCENTAGE
            )
            ->each(
                function (OfferSalaryComponent $component) use ($basicComponent) {
                    $component->update([
                        'percentage_of_component_id' =>
                            $basicComponent->id,
                    ]);
                }
            );
    }

    public function canBeSent(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_APPROVED,
                self::STATUS_SENT,
            ],
            true
        );
    }

    public function canBeCancelled(): bool
    {
        return !in_array(
            $this->status,
            [
                self::STATUS_ACCEPTED,
                self::STATUS_DECLINED,
                self::STATUS_CANCELLED,
                self::STATUS_EXPIRED,
            ],
            true
        );
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sent_by'
        );
    }

    public function portalTokenIsValid(): bool
    {
        if (!$this->portal_token_hash) {
            return false;
        }

        if ($this->portal_token_revoked_at) {
            return false;
        }

        if (
            $this->portal_token_expires_at &&
            $this->portal_token_expires_at->isPast()
        ) {
            return false;
        }

        return true;
    }

    public function canBeViewedByCandidate(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SENT,
                self::STATUS_VIEWED,
                self::STATUS_ACCEPTED,
                self::STATUS_DECLINED,
            ],
            true
        ) && $this->portalTokenIsValid();
    }

    public function canReceiveCandidateResponse(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SENT,
                self::STATUS_VIEWED,
            ],
            true
        ) &&
            !$this->responded_at &&
            $this->portalTokenIsValid() &&
            (
                !$this->valid_till ||
                $this->valid_till->endOfDay()->isFuture()
            );
    }

    public function isExpiredForCandidate(): bool
    {
        return $this->valid_till &&
            $this->valid_till->endOfDay()->isPast() &&
            !$this->responded_at;
    }

    public function revokePortalAccess(): void
    {
        $this->forceFill([
            'portal_token_revoked_at' => now(),
        ])->save();
    }
}