<?php

namespace Tests\Unit;

use App\Models\OfferSalaryComponent;
use App\Services\Recruitment\OfferSalaryCalculator;
use PHPUnit\Framework\TestCase;

class OfferSalaryCalculatorTest extends TestCase
{
    public function test_it_calculates_salary_summary(): void
    {
        $calculator =
            new OfferSalaryCalculator();

        $result = $calculator->summarize([
            [
                'row_key' => 'basic',

                'component_name' =>
                    'Basic Salary',

                'component_type' =>
                    OfferSalaryComponent::TYPE_EARNING,

                'calculation_type' =>
                    OfferSalaryComponent::CALCULATION_FIXED,

                'amount' => 20000,

                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_MONTHLY,

                'show_in_offer' => true,
                'affects_in_hand' => true,
                'is_taxable' => true,
                'sort_order' => 10,
            ],

            [
                'row_key' => 'hra',

                'component_name' =>
                    'House Rent Allowance',

                'component_type' =>
                    OfferSalaryComponent::TYPE_EARNING,

                'calculation_type' =>
                    OfferSalaryComponent::CALCULATION_PERCENTAGE,

                'percentage_of_row_key' =>
                    'basic',

                'percentage' => 40,

                'amount' => 0,

                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_MONTHLY,

                'show_in_offer' => true,
                'affects_in_hand' => true,
                'is_taxable' => true,
                'sort_order' => 20,
            ],

            [
                'row_key' => 'employee-pf',

                'component_name' =>
                    'Employee PF',

                'component_type' =>
                    OfferSalaryComponent::TYPE_DEDUCTION,

                'calculation_type' =>
                    OfferSalaryComponent::CALCULATION_PERCENTAGE,

                'percentage_of_row_key' =>
                    'basic',

                'percentage' => 12,

                'amount' => 0,

                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_MONTHLY,

                'show_in_offer' => true,
                'affects_in_hand' => true,
                'is_taxable' => false,
                'sort_order' => 30,
            ],

            [
                'row_key' => 'employer-pf',

                'component_name' =>
                    'Employer PF',

                'component_type' =>
                    OfferSalaryComponent::TYPE_EMPLOYER_CONTRIBUTION,

                'calculation_type' =>
                    OfferSalaryComponent::CALCULATION_PERCENTAGE,

                'percentage_of_row_key' =>
                    'basic',

                'percentage' => 12,

                'amount' => 0,

                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_MONTHLY,

                'show_in_offer' => true,
                'affects_in_hand' => false,
                'is_taxable' => false,
                'sort_order' => 40,
            ],
        ]);

        $this->assertSame(
            28000.0,
            $result['monthly_gross']
        );

        $this->assertSame(
            2400.0,
            $result['monthly_deductions']
        );

        $this->assertSame(
            2400.0,
            $result[
                'monthly_employer_contributions'
            ]
        );

        $this->assertSame(
            25600.0,
            $result['monthly_in_hand']
        );

        $this->assertSame(
            364800.0,
            $result['annual_ctc']
        );
    }

    public function test_one_time_component_is_in_annual_ctc_but_not_monthly_gross(): void
    {
        $calculator =
            new OfferSalaryCalculator();

        $result = $calculator->summarize([
            [
                'row_key' => 'joining-bonus',

                'component_name' =>
                    'Joining Bonus',

                'component_type' =>
                    OfferSalaryComponent::TYPE_EARNING,

                'calculation_type' =>
                    OfferSalaryComponent::CALCULATION_FIXED,

                'amount' => 25000,

                'frequency' =>
                    OfferSalaryComponent::FREQUENCY_ONE_TIME,

                'show_in_offer' => true,
                'affects_in_hand' => true,
                'is_taxable' => true,
                'sort_order' => 10,
            ],
        ]);

        $this->assertSame(
            0.0,
            $result['monthly_gross']
        );

        $this->assertSame(
            25000.0,
            $result['annual_ctc']
        );
    }
}