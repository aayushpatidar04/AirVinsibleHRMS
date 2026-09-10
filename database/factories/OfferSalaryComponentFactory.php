<?php

namespace Database\Factories;

use App\Models\CandidateOffer;
use App\Models\OfferSalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfferSalaryComponent>
 */
class OfferSalaryComponentFactory extends Factory
{
    protected $model =
        OfferSalaryComponent::class;

    public function definition(): array
    {
        return [
            'candidate_offer_id' =>
                CandidateOffer::factory(),

            'component_name' =>
                fake()->randomElement([
                    'Basic Salary',
                    'House Rent Allowance',
                    'Special Allowance',
                    'Performance Incentive',
                    'Employee PF',
                    'Employer PF',
                ]),

            'component_type' =>
                OfferSalaryComponent::TYPE_EARNING,

            'calculation_type' =>
                OfferSalaryComponent::CALCULATION_FIXED,

            'percentage_of_component_id' =>
                null,

            'amount' =>
                fake()->randomFloat(
                    2,
                    1000,
                    50000
                ),

            'percentage' =>
                null,

            'frequency' =>
                OfferSalaryComponent::FREQUENCY_MONTHLY,

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
        ];
    }

    public function earning(): static
    {
        return $this->state(
            fn () => [
                'component_type' =>
                    OfferSalaryComponent::TYPE_EARNING,

                'affects_in_hand' =>
                    true,
            ]
        );
    }

    public function deduction(): static
    {
        return $this->state(
            fn () => [
                'component_type' =>
                    OfferSalaryComponent::TYPE_DEDUCTION,

                'affects_in_hand' =>
                    true,
            ]
        );
    }

    public function employerContribution(): static
    {
        return $this->state(
            fn () => [
                'component_type' =>
                    OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION,

                'affects_in_hand' =>
                    false,
            ]
        );
    }

    public function annual(): static
    {
        return $this->state(
            fn () => [
                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_ANNUAL,
            ]
        );
    }

    public function oneTime(): static
    {
        return $this->state(
            fn () => [
                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_ONE_TIME,
            ]
        );
    }
}