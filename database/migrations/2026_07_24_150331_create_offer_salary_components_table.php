<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offer_salary_components', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Candidate Offer
            |--------------------------------------------------------------------------
            */

            $table->foreignId('candidate_offer_id')
                ->constrained('candidate_offers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Component Details
            |--------------------------------------------------------------------------
            */

            /*
             * Examples:
             *
             * Basic Salary
             * House Rent Allowance
             * Employer PF
             * Employee PF
             * Performance Bonus
             */
            $table->string('component_name');

            /*
             * Supported values:
             *
             * earning
             * deduction
             * employer_contribution
             */
            $table->string('component_type', 40)
                ->default('earning')
                ->index();

            /*
             * Supported values:
             *
             * fixed
             * percentage
             */
            $table->string('calculation_type', 30)
                ->default('fixed');

            /*
             * Used when calculation_type is percentage.
             *
             * Example:
             *
             * HRA = 40% of Basic Salary
             * PF = 12% of Basic Salary
             */
            $table->foreignId('percentage_of_component_id')
                ->nullable()
                ->constrained('offer_salary_components')
                ->nullOnDelete();

            /*
             * Fixed amount or calculated result.
             */
            $table->decimal('amount', 14, 2)
                ->default(0);

            /*
             * Percentage value when calculation_type is percentage.
             *
             * Example:
             * 12.00 means 12%
             */
            $table->decimal('percentage', 8, 4)
                ->nullable();

            /*
             * Supported frequencies:
             *
             * monthly
             * quarterly
             * half_yearly
             * annual
             * one_time
             */
            $table->string('frequency', 30)
                ->default('monthly');

            /*
             * Whether this salary component should appear
             * in the offer letter salary annexure.
             */
            $table->boolean('show_in_offer')
                ->default(true);

            /*
             * Whether this component affects the employee's
             * calculated in-hand salary.
             */
            $table->boolean('affects_in_hand')
                ->default(true);

            /*
             * Whether this component is taxable.
             *
             * This is useful for future payroll integration.
             */
            $table->boolean('is_taxable')
                ->default(true);

            /*
             * Controls component order in UI and PDF.
             */
            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            /*
             * Optional internal description.
             *
             * Example:
             * "Calculated as 12% of Basic Salary"
             */
            $table->string('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'candidate_offer_id',
                    'component_type',
                ],
                'offer_salary_components_offer_type_index'
            );

            $table->index(
                [
                    'candidate_offer_id',
                    'sort_order',
                ],
                'offer_salary_components_offer_sort_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'offer_salary_components'
        );
    }
};