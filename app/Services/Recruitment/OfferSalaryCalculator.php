<?php

namespace App\Services\Recruitment;

use App\Models\OfferSalaryComponent;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OfferSalaryCalculator
{
    /**
     * Normalize and calculate salary rows before saving.
     *
     * Percentage rows can reference another frontend row using
     * percentage_of_row_key. The database IDs are assigned later.
     */
    public function calculate(array $components): array
    {
        $components = collect($components)
            ->values()
            ->map(
                fn (array $component, int $index) =>
                    $this->normalizeComponent(
                        $component,
                        $index
                    )
            );

        $this->validateUniqueRowKeys($components);

        /*
         * Resolve calculation dependencies.
         *
         * Multiple passes allow a percentage component to depend
         * on another percentage component.
         */
        $maximumPasses = max(
            $components->count(),
            1
        );

        for ($pass = 0; $pass < $maximumPasses; $pass++) {
            $changed = false;

            $components = $components->map(
                function (
                    array $component
                ) use (
                    $components,
                    &$changed
                ) {
                    if (
                        $component['calculation_type'] !==
                        OfferSalaryComponent::CALCULATION_PERCENTAGE
                    ) {
                        return $component;
                    }

                    $baseRowKey =
                        $component['percentage_of_row_key']
                        ?? null;

                    if (!$baseRowKey) {
                        return $component;
                    }

                    $baseComponent =
                        $components->firstWhere(
                            'row_key',
                            $baseRowKey
                        );

                    if (!$baseComponent) {
                        return $component;
                    }

                    $calculatedAmount = round(
                        (float) $baseComponent['amount']
                        * (
                            (float) $component['percentage']
                            / 100
                        ),
                        2
                    );

                    if (
                        (float) $component['amount'] !==
                        $calculatedAmount
                    ) {
                        $component['amount'] =
                            $calculatedAmount;

                        $changed = true;
                    }

                    return $component;
                }
            );

            if (!$changed) {
                break;
            }
        }

        $this->validatePercentageReferences(
            $components
        );

        return $components
            ->map(
                fn (array $component) =>
                    $this->appendConvertedAmounts(
                        $component
                    )
            )
            ->values()
            ->all();
    }

    public function summarize(
        array $components
    ): array {
        $calculated = collect(
            $this->calculate($components)
        );

        $monthlyGross = round(
            $calculated
                ->where(
                    'component_type',
                    OfferSalaryComponent::TYPE_EARNING
                )
                ->sum('monthly_amount'),
            2
        );

        $monthlyDeductions = round(
            $calculated
                ->filter(
                    fn (array $component) =>
                        $component['component_type'] ===
                            OfferSalaryComponent::TYPE_DEDUCTION
                        && $component['affects_in_hand']
                )
                ->sum('monthly_amount'),
            2
        );

        $monthlyEmployerContributions = round(
            $calculated
                ->where(
                    'component_type',
                    OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION
                )
                ->sum('monthly_amount'),
            2
        );

        $annualCtc = round(
            $calculated
                ->filter(
                    fn (array $component) =>
                        in_array(
                            $component['component_type'],
                            [
                                OfferSalaryComponent::TYPE_EARNING,
                                OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION,
                            ],
                            true
                        )
                )
                ->sum('annual_amount'),
            2
        );

        return [
            'components' =>
                $calculated->values()->all(),

            'monthly_gross' =>
                $monthlyGross,

            'monthly_deductions' =>
                $monthlyDeductions,

            'monthly_employer_contributions' =>
                $monthlyEmployerContributions,

            'monthly_in_hand' => round(
                $monthlyGross - $monthlyDeductions,
                2
            ),

            'annual_ctc' =>
                $annualCtc,
        ];
    }

    private function normalizeComponent(
        array $component,
        int $index
    ): array {
        $rowKey = filled(
            $component['row_key'] ?? null
        )
            ? (string) $component['row_key']
            : 'component-' . ($index + 1);

        return [
            'id' =>
                isset($component['id'])
                    ? (int) $component['id']
                    : null,

            'row_key' =>
                $rowKey,

            'component_name' =>
                trim(
                    (string) (
                        $component['component_name']
                        ?? ''
                    )
                ),

            'component_type' =>
                $component['component_type']
                ?? OfferSalaryComponent::TYPE_EARNING,

            'calculation_type' =>
                $component['calculation_type']
                ?? OfferSalaryComponent::CALCULATION_FIXED,

            'percentage_of_component_id' =>
                filled(
                    $component[
                        'percentage_of_component_id'
                    ] ?? null
                )
                    ? (int) $component[
                        'percentage_of_component_id'
                    ]
                    : null,

            'percentage_of_row_key' =>
                filled(
                    $component[
                        'percentage_of_row_key'
                    ] ?? null
                )
                    ? (string) $component[
                        'percentage_of_row_key'
                    ]
                    : null,

            'amount' => round(
                (float) (
                    $component['amount'] ?? 0
                ),
                2
            ),

            'percentage' =>
                filled(
                    $component['percentage'] ?? null
                )
                    ? round(
                        (float) $component[
                            'percentage'
                        ],
                        4
                    )
                    : null,

            'frequency' =>
                $component['frequency']
                ?? OfferSalaryComponent::FREQUENCY_MONTHLY,

            'show_in_offer' =>
                filter_var(
                    $component['show_in_offer']
                    ?? true,
                    FILTER_VALIDATE_BOOLEAN
                ),

            'affects_in_hand' =>
                filter_var(
                    $component['affects_in_hand']
                    ?? true,
                    FILTER_VALIDATE_BOOLEAN
                ),

            'is_taxable' =>
                filter_var(
                    $component['is_taxable']
                    ?? true,
                    FILTER_VALIDATE_BOOLEAN
                ),

            'sort_order' =>
                isset($component['sort_order'])
                    ? (int) $component['sort_order']
                    : (($index + 1) * 10),

            'description' =>
                filled(
                    $component['description']
                    ?? null
                )
                    ? trim(
                        (string) $component[
                            'description'
                        ]
                    )
                    : null,
        ];
    }

    private function appendConvertedAmounts(
        array $component
    ): array {
        $amount = (float) $component['amount'];

        $component['monthly_amount'] = round(
            match ($component['frequency']) {
                OfferSalaryComponent::FREQUENCY_MONTHLY =>
                    $amount,

                OfferSalaryComponent::FREQUENCY_QUARTERLY =>
                    $amount / 3,

                OfferSalaryComponent::FREQUENCY_HALF_YEARLY =>
                    $amount / 6,

                OfferSalaryComponent::FREQUENCY_ANNUAL =>
                    $amount / 12,

                OfferSalaryComponent::FREQUENCY_ONE_TIME =>
                    0,

                default =>
                    $amount,
            },
            2
        );

        $component['annual_amount'] = round(
            match ($component['frequency']) {
                OfferSalaryComponent::FREQUENCY_MONTHLY =>
                    $amount * 12,

                OfferSalaryComponent::FREQUENCY_QUARTERLY =>
                    $amount * 4,

                OfferSalaryComponent::FREQUENCY_HALF_YEARLY =>
                    $amount * 2,

                OfferSalaryComponent::FREQUENCY_ANNUAL =>
                    $amount,

                OfferSalaryComponent::FREQUENCY_ONE_TIME =>
                    $amount,

                default =>
                    $amount * 12,
            },
            2
        );

        return $component;
    }

    private function validateUniqueRowKeys(
        Collection $components
    ): void {
        $duplicateKeys = $components
            ->pluck('row_key')
            ->duplicates()
            ->values();

        if ($duplicateKeys->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'salary_components' =>
                'Every salary component must have a unique row key.',
        ]);
    }

    private function validatePercentageReferences(
        Collection $components
    ): void {
        foreach ($components as $index => $component) {
            if (
                $component['calculation_type'] !==
                OfferSalaryComponent::CALCULATION_PERCENTAGE
            ) {
                continue;
            }

            if (
                !$component['percentage_of_row_key']
                && !$component[
                    'percentage_of_component_id'
                ]
            ) {
                throw ValidationException::withMessages([
                    "salary_components.{$index}.percentage_of_row_key" =>
                        'Select the salary component on which this percentage is calculated.',
                ]);
            }

            if (
                $component['percentage_of_row_key'] ===
                $component['row_key']
            ) {
                throw ValidationException::withMessages([
                    "salary_components.{$index}.percentage_of_row_key" =>
                        'A salary component cannot calculate a percentage of itself.',
                ]);
            }

            if (
                $component['percentage_of_row_key']
                && !$components->contains(
                    'row_key',
                    $component[
                        'percentage_of_row_key'
                    ]
                )
            ) {
                throw ValidationException::withMessages([
                    "salary_components.{$index}.percentage_of_row_key" =>
                        'The selected base salary component does not exist.',
                ]);
            }
        }
    }
}