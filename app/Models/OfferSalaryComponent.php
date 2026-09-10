<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferSalaryComponent extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Component Types
    |--------------------------------------------------------------------------
    */

    public const TYPE_EARNING = 'earning';

    public const TYPE_DEDUCTION = 'deduction';

    public const TYPE_EMPLOYER_CONTRIBUTION =
        'employer_contribution';

    /*
    |--------------------------------------------------------------------------
    | Calculation Types
    |--------------------------------------------------------------------------
    */

    public const CALCULATION_FIXED = 'fixed';

    public const CALCULATION_PERCENTAGE = 'percentage';

    /*
    |--------------------------------------------------------------------------
    | Frequencies
    |--------------------------------------------------------------------------
    */

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_HALF_YEARLY = 'half_yearly';

    public const FREQUENCY_ANNUAL = 'annual';

    public const FREQUENCY_ONE_TIME = 'one_time';

    protected $fillable = [
        'candidate_offer_id',

        'component_name',
        'component_type',

        'calculation_type',
        'percentage_of_component_id',

        'amount',
        'percentage',

        'frequency',

        'show_in_offer',
        'affects_in_hand',
        'is_taxable',

        'sort_order',
        'description',
    ];

    protected $casts = [
        'candidate_offer_id' => 'integer',

        'percentage_of_component_id' => 'integer',

        'amount' => 'decimal:2',

        'percentage' => 'decimal:4',

        'show_in_offer' => 'boolean',

        'affects_in_hand' => 'boolean',

        'is_taxable' => 'boolean',

        'sort_order' => 'integer',
    ];

    protected $appends = [
        'component_type_label',
        'calculation_type_label',
        'frequency_label',

        'monthly_amount',
        'annual_amount',

        'is_earning',
        'is_deduction',
        'is_employer_contribution',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function offer(): BelongsTo
    {
        return $this->belongsTo(
            CandidateOffer::class,
            'candidate_offer_id'
        );
    }

    /**
     * Parent component used for percentage calculation.
     *
     * Example:
     * HRA is 40% of Basic Salary.
     */
    public function percentageOfComponent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'percentage_of_component_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOrdered(
        Builder $query
    ): Builder {
        return $query
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeEarnings(
        Builder $query
    ): Builder {
        return $query->where(
            'component_type',
            self::TYPE_EARNING
        );
    }

    public function scopeDeductions(
        Builder $query
    ): Builder {
        return $query->where(
            'component_type',
            self::TYPE_DEDUCTION
        );
    }

    public function scopeEmployerContributions(
        Builder $query
    ): Builder {
        return $query->where(
            'component_type',
            self::TYPE_EMPLOYER_CONTRIBUTION
        );
    }

    public function scopeVisibleInOffer(
        Builder $query
    ): Builder {
        return $query->where(
            'show_in_offer',
            true
        );
    }

    public function scopeAffectingInHand(
        Builder $query
    ): Builder {
        return $query->where(
            'affects_in_hand',
            true
        );
    }

    public function scopeFixed(
        Builder $query
    ): Builder {
        return $query->where(
            'calculation_type',
            self::CALCULATION_FIXED
        );
    }

    public function scopePercentageBased(
        Builder $query
    ): Builder {
        return $query->where(
            'calculation_type',
            self::CALCULATION_PERCENTAGE
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getComponentTypeLabelAttribute(): string
    {
        return self::componentTypeOptions()[
            $this->component_type
        ] ?? ucfirst(
            str_replace(
                '_',
                ' ',
                $this->component_type
            )
        );
    }

    public function getCalculationTypeLabelAttribute(): string
    {
        return self::calculationTypeOptions()[
            $this->calculation_type
        ] ?? ucfirst(
            str_replace(
                '_',
                ' ',
                $this->calculation_type
            )
        );
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::frequencyOptions()[
            $this->frequency
        ] ?? ucfirst(
            str_replace(
                '_',
                ' ',
                $this->frequency
            )
        );
    }

    public function getMonthlyAmountAttribute(): float
    {
        return $this->toMonthlyAmount(
            (float) $this->amount
        );
    }

    public function getAnnualAmountAttribute(): float
    {
        return $this->toAnnualAmount(
            (float) $this->amount
        );
    }

    public function getIsEarningAttribute(): bool
    {
        return $this->component_type ===
            self::TYPE_EARNING;
    }

    public function getIsDeductionAttribute(): bool
    {
        return $this->component_type ===
            self::TYPE_DEDUCTION;
    }

    public function getIsEmployerContributionAttribute(): bool
    {
        return $this->component_type ===
            self::TYPE_EMPLOYER_CONTRIBUTION;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculation Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate this component's amount.
     *
     * Fixed components return their stored amount.
     *
     * Percentage components calculate against their
     * referenced parent component.
     */
    public function calculateAmount(): float
    {
        if (
            $this->calculation_type ===
            self::CALCULATION_FIXED
        ) {
            return round(
                (float) $this->amount,
                2
            );
        }

        if (
            $this->calculation_type !==
            self::CALCULATION_PERCENTAGE
        ) {
            return 0.0;
        }

        if (!$this->percentage_of_component_id) {
            return round(
                (float) $this->amount,
                2
            );
        }

        $baseComponent =
            $this->relationLoaded(
                'percentageOfComponent'
            )
                ? $this->percentageOfComponent
                : $this
                    ->percentageOfComponent()
                    ->first();

        if (!$baseComponent) {
            return round(
                (float) $this->amount,
                2
            );
        }

        $baseAmount =
            $baseComponent->calculateAmount();

        $percentage =
            (float) ($this->percentage ?? 0);

        return round(
            $baseAmount * ($percentage / 100),
            2
        );
    }

    /**
     * Update and persist the calculated amount.
     *
     * Useful after changing the referenced base component.
     */
    public function recalculate(): float
    {
        $calculatedAmount =
            $this->calculateAmount();

        if (
            $this->calculation_type ===
            self::CALCULATION_PERCENTAGE
        ) {
            $this->forceFill([
                'amount' => $calculatedAmount,
            ])->save();
        }

        return $calculatedAmount;
    }

    /**
     * Convert an amount stored with this component's
     * frequency into a monthly amount.
     */
    public function toMonthlyAmount(
        ?float $amount = null
    ): float {
        $value = $amount ??
            $this->calculateAmount();

        return round(
            match ($this->frequency) {
                self::FREQUENCY_MONTHLY =>
                    $value,

                self::FREQUENCY_QUARTERLY =>
                    $value / 3,

                self::FREQUENCY_HALF_YEARLY =>
                    $value / 6,

                self::FREQUENCY_ANNUAL =>
                    $value / 12,

                self::FREQUENCY_ONE_TIME =>
                    0,

                default =>
                    $value,
            },
            2
        );
    }

    /**
     * Convert an amount stored with this component's
     * frequency into an annual amount.
     */
    public function toAnnualAmount(
        ?float $amount = null
    ): float {
        $value = $amount ??
            $this->calculateAmount();

        return round(
            match ($this->frequency) {
                self::FREQUENCY_MONTHLY =>
                    $value * 12,

                self::FREQUENCY_QUARTERLY =>
                    $value * 4,

                self::FREQUENCY_HALF_YEARLY =>
                    $value * 2,

                self::FREQUENCY_ANNUAL =>
                    $value,

                self::FREQUENCY_ONE_TIME =>
                    $value,

                default =>
                    $value * 12,
            },
            2
        );
    }

    /**
     * Whether this component should increase gross earnings.
     */
    public function increasesGrossSalary(): bool
    {
        return $this->component_type ===
            self::TYPE_EARNING;
    }

    /**
     * Whether this component should reduce in-hand salary.
     */
    public function reducesInHandSalary(): bool
    {
        return
            $this->component_type ===
                self::TYPE_DEDUCTION
            && $this->affects_in_hand;
    }

    /**
     * Whether this component increases employer CTC.
     */
    public function increasesEmployerCost(): bool
    {
        return in_array(
            $this->component_type,
            [
                self::TYPE_EARNING,
                self::TYPE_EMPLOYER_CONTRIBUTION,
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Static Options
    |--------------------------------------------------------------------------
    */

    public static function componentTypeOptions(): array
    {
        return [
            self::TYPE_EARNING =>
                'Earning',

            self::TYPE_DEDUCTION =>
                'Deduction',

            self::TYPE_EMPLOYER_CONTRIBUTION =>
                'Employer Contribution',
        ];
    }

    public static function calculationTypeOptions(): array
    {
        return [
            self::CALCULATION_FIXED =>
                'Fixed Amount',

            self::CALCULATION_PERCENTAGE =>
                'Percentage',
        ];
    }

    public static function frequencyOptions(): array
    {
        return [
            self::FREQUENCY_MONTHLY =>
                'Monthly',

            self::FREQUENCY_QUARTERLY =>
                'Quarterly',

            self::FREQUENCY_HALF_YEARLY =>
                'Half Yearly',

            self::FREQUENCY_ANNUAL =>
                'Annual',

            self::FREQUENCY_ONE_TIME =>
                'One Time',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Suggested Defaults
    |--------------------------------------------------------------------------
    */

    /**
     * Standard initial salary rows for a new offer.
     *
     * These are returned as arrays because they have not
     * been persisted yet.
     */
    public static function defaultComponents(): array
    {
        return [
            [
                'component_name' =>
                    'Basic Salary',

                'component_type' =>
                    self::TYPE_EARNING,

                'calculation_type' =>
                    self::CALCULATION_FIXED,

                'percentage_of_component_id' =>
                    null,

                'percentage' =>
                    null,

                'amount' =>
                    0,

                'frequency' =>
                    self::FREQUENCY_MONTHLY,

                'show_in_offer' =>
                    true,

                'affects_in_hand' =>
                    true,

                'is_taxable' =>
                    true,

                'sort_order' =>
                    10,

                'description' =>
                    null,
            ],

            [
                'component_name' =>
                    'House Rent Allowance',

                'component_type' =>
                    self::TYPE_EARNING,

                'calculation_type' =>
                    self::CALCULATION_PERCENTAGE,

                /*
                 * The frontend will connect this row to the
                 * Basic Salary row before persistence.
                 */
                'percentage_of_component_id' =>
                    null,

                'percentage' =>
                    40,

                'amount' =>
                    0,

                'frequency' =>
                    self::FREQUENCY_MONTHLY,

                'show_in_offer' =>
                    true,

                'affects_in_hand' =>
                    true,

                'is_taxable' =>
                    true,

                'sort_order' =>
                    20,

                'description' =>
                    'Calculated as a percentage of Basic Salary.',
            ],

            [
                'component_name' =>
                    'Special Allowance',

                'component_type' =>
                    self::TYPE_EARNING,

                'calculation_type' =>
                    self::CALCULATION_FIXED,

                'percentage_of_component_id' =>
                    null,

                'percentage' =>
                    null,

                'amount' =>
                    0,

                'frequency' =>
                    self::FREQUENCY_MONTHLY,

                'show_in_offer' =>
                    true,

                'affects_in_hand' =>
                    true,

                'is_taxable' =>
                    true,

                'sort_order' =>
                    30,

                'description' =>
                    null,
            ],

            [
                'component_name' =>
                    'Employee PF',

                'component_type' =>
                    self::TYPE_DEDUCTION,

                'calculation_type' =>
                    self::CALCULATION_PERCENTAGE,

                'percentage_of_component_id' =>
                    null,

                'percentage' =>
                    12,

                'amount' =>
                    0,

                'frequency' =>
                    self::FREQUENCY_MONTHLY,

                'show_in_offer' =>
                    true,

                'affects_in_hand' =>
                    true,

                'is_taxable' =>
                    false,

                'sort_order' =>
                    40,

                'description' =>
                    'Employee contribution calculated from Basic Salary.',
            ],

            [
                'component_name' =>
                    'Employer PF',

                'component_type' =>
                    self::TYPE_EMPLOYER_CONTRIBUTION,

                'calculation_type' =>
                    self::CALCULATION_PERCENTAGE,

                'percentage_of_component_id' =>
                    null,

                'percentage' =>
                    12,

                'amount' =>
                    0,

                'frequency' =>
                    self::FREQUENCY_MONTHLY,

                'show_in_offer' =>
                    true,

                'affects_in_hand' =>
                    false,

                'is_taxable' =>
                    false,

                'sort_order' =>
                    50,

                'description' =>
                    'Employer contribution calculated from Basic Salary.',
            ],
        ];
    }
}