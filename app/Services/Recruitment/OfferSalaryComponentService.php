<?php

namespace App\Services\Recruitment;

use App\Models\CandidateOffer;
use App\Models\OfferSalaryComponent;
use Illuminate\Support\Facades\DB;

class OfferSalaryComponentService
{
    public function __construct(
        private readonly OfferSalaryCalculator $calculator
    ) {
    }

    /**
     * Replace all salary components belonging to an offer.
     *
     * Replacing the rows is safer for the builder because rows can
     * be reordered, removed, or assigned new percentage references.
     */
    public function sync(
        CandidateOffer $offer,
        array $components
    ): array {
        return DB::transaction(
            function () use (
                $offer,
                $components
            ) {
                $summary =
                    $this->calculator->summarize(
                        $components
                    );

                $oldIdToRowKey =
                    collect($summary['components'])
                        ->filter(
                            fn (array $component) =>
                                filled($component['id'])
                        )
                        ->mapWithKeys(
                            fn (array $component) => [
                                (int) $component['id'] =>
                                    $component['row_key'],
                            ]
                        );

                $offer->salaryComponents()->delete();

                $rowKeyToId = [];

                /*
                 * First pass:
                 * create rows without self-reference foreign keys.
                 */
                foreach (
                    $summary['components']
                    as $component
                ) {
                    $created =
                        $offer
                            ->salaryComponents()
                            ->create(
                                $this->databasePayload(
                                    $component,
                                    null
                                )
                            );

                    $rowKeyToId[
                        $component['row_key']
                    ] = $created->id;
                }

                /*
                 * Second pass:
                 * attach percentage rows to their new parent IDs.
                 */
                foreach (
                    $summary['components']
                    as $component
                ) {
                    if (
                        $component['calculation_type'] !==
                        OfferSalaryComponent::CALCULATION_PERCENTAGE
                    ) {
                        continue;
                    }

                    $baseRowKey =
                        $component[
                            'percentage_of_row_key'
                        ]
                        ?? null;

                    /*
                     * Existing records might submit only the old DB ID.
                     */
                    if (
                        !$baseRowKey
                        && $component[
                            'percentage_of_component_id'
                        ]
                    ) {
                        $baseRowKey =
                            $oldIdToRowKey[
                                $component[
                                    'percentage_of_component_id'
                                ]
                            ]
                            ?? null;
                    }

                    $componentId =
                        $rowKeyToId[
                            $component['row_key']
                        ]
                        ?? null;

                    $baseComponentId =
                        $baseRowKey
                            ? (
                                $rowKeyToId[
                                    $baseRowKey
                                ]
                                ?? null
                            )
                            : null;

                    if (
                        !$componentId
                        || !$baseComponentId
                    ) {
                        continue;
                    }

                    OfferSalaryComponent::query()
                        ->whereKey($componentId)
                        ->update([
                            'percentage_of_component_id' =>
                                $baseComponentId,
                        ]);
                }

                $offer->forceFill([
                    'salary_ctc' =>
                        $summary['annual_ctc'],

                    'salary_in_hand' =>
                        $summary['monthly_in_hand'],

                    /*
                     * Keep the JSON field synchronized temporarily
                     * for backward compatibility.
                     */
                    'salary_structure_json' =>
                        $summary['components'],
                ])->save();

                $offer->unsetRelation(
                    'salaryComponents'
                );

                return $summary;
            }
        );
    }

    private function databasePayload(
        array $component,
        ?int $percentageOfComponentId
    ): array {
        return [
            'component_name' =>
                $component['component_name'],

            'component_type' =>
                $component['component_type'],

            'calculation_type' =>
                $component['calculation_type'],

            'percentage_of_component_id' =>
                $percentageOfComponentId,

            'amount' =>
                $component['amount'],

            'percentage' =>
                $component['percentage'],

            'frequency' =>
                $component['frequency'],

            'show_in_offer' =>
                $component['show_in_offer'],

            'affects_in_hand' =>
                $component['affects_in_hand'],

            'is_taxable' =>
                $component['is_taxable'],

            'sort_order' =>
                $component['sort_order'],

            'description' =>
                $component['description'],
        ];
    }
}