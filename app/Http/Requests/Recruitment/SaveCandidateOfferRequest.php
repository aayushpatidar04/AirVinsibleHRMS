<?php

namespace App\Http\Requests\Recruitment;

use App\Models\CandidateOffer;
use App\Models\OfferSalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCandidateOfferRequest extends FormRequest
{
    /**
     * Authorization is handled by CandidateOfferController
     * because create and update use different policy arguments.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Employment Details
            |--------------------------------------------------------------------------
            */

            'process_name' => [
                'required',
                'string',
                'max:255',
            ],

            'designation' => [
                'required',
                'string',
                'max:255',
            ],

            'department' => [
                'nullable',
                'string',
                'max:255',
            ],

            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'reporting_manager_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'employment_type' => [
                'required',
                Rule::in(
                    array_keys(
                        CandidateOffer::employmentTypeOptions()
                    )
                ),
            ],

            'probation_months' => [
                'nullable',
                'integer',
                'min:0',
                'max:60',
            ],

            'joining_date' => [
                'required',
                'date',
            ],

            'reporting_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'work_location' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Compensation
            |--------------------------------------------------------------------------
            */

            'salary_ctc' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'salary_in_hand' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'salary_basic' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_hra' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_special_allowance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_pf' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_bonus' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_variable' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_other_allowances' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'pf_allowed' => [
                'required',
                'boolean',
            ],

            'salary_components' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'salary_components.*.id' => [
                'nullable',
                'integer',
            ],

            'salary_components.*.row_key' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'salary_components.*.component_name' => [
                'required',
                'string',
                'max:255',
            ],

            'salary_components.*.component_type' => [
                'required',
                Rule::in(
                    array_keys(
                        OfferSalaryComponent::componentTypeOptions()
                    )
                ),
            ],

            'salary_components.*.calculation_type' => [
                'required',
                Rule::in(
                    array_keys(
                        OfferSalaryComponent::calculationTypeOptions()
                    )
                ),
            ],

            'salary_components.*.percentage_of_component_id' => [
                'nullable',
                'integer',
            ],

            'salary_components.*.percentage_of_row_key' => [
                'nullable',
                'string',
                'max:100',
            ],

            'salary_components.*.amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'salary_components.*.percentage' => [
                'nullable',
                'required_if:salary_components.*.calculation_type,percentage',
                'numeric',
                'min:0',
                'max:1000',
            ],

            'salary_components.*.frequency' => [
                'required',
                Rule::in(
                    array_keys(
                        OfferSalaryComponent::frequencyOptions()
                    )
                ),
            ],

            'salary_components.*.show_in_offer' => [
                'required',
                'boolean',
            ],

            'salary_components.*.affects_in_hand' => [
                'required',
                'boolean',
            ],

            'salary_components.*.is_taxable' => [
                'required',
                'boolean',
            ],

            'salary_components.*.sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'salary_components.*.description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Offer Content
            |--------------------------------------------------------------------------
            */

            'salary_annexure' => [
                'nullable',
                'string',
            ],

            'offer_terms' => [
                'nullable',
                'string',
            ],

            'internal_remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'offer_valid_till' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'revision_reason' => [
                $this->routeIs(
                    'recruitment.offers.revisions.store'
                )
                    ? 'required'
                    : 'nullable',

                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'process_name.required' =>
                'Please select the assigned process or project.',

            'designation.required' =>
                'Please enter the final designation.',

            'employment_type.required' =>
                'Please select the employment type.',

            'joining_date.required' =>
                'Please select the proposed joining date.',

            'salary_components.required' =>
                'Please add at least one salary component.',

            'salary_components.min' =>
                'Please add at least one salary component.',

            'salary_components.*.component_name.required' =>
                'Every salary component must have a name.',

            'salary_components.*.component_type.required' =>
                'Every salary component must have a type.',

            'salary_components.*.calculation_type.required' =>
                'Every salary component must have a calculation type.',

            'salary_components.*.percentage.required_if' =>
                'Enter a percentage for percentage-based components.',

            'salary_components.*.row_key.distinct' =>
                'Every salary component must have a unique row identifier.',

            'offer_valid_till.required' =>
                'Please specify the offer validity date.',

            'offer_valid_till.after_or_equal' =>
                'The offer validity date cannot be in the past.',

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pf_allowed' => filter_var(
                $this->input('pf_allowed', false),
                FILTER_VALIDATE_BOOLEAN
            ),

            'branch_id' => $this->nullableInteger(
                $this->input('branch_id')
            ),

            'reporting_manager_id' => $this->nullableInteger(
                $this->input('reporting_manager_id')
            ),

            'probation_months' => $this->nullableInteger(
                $this->input('probation_months')
            ),

            'salary_components' =>
                $this->normalizeSalaryComponents(
                    $this->input(
                        'salary_components',
                        []
                    )
                ),
        ]);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeSalaryComponents(
        mixed $components
    ): array {
        if (!is_array($components)) {
            return [];
        }

        return collect($components)
            ->filter(
                fn($component) =>
                is_array($component)
            )
            ->values()
            ->map(
                function (array $component, int $index) {
                    return [
                        'id' =>
                            filled(
                                $component['id'] ?? null
                            )
                            ? (int) $component['id']
                            : null,

                        'row_key' =>
                            filled(
                                $component[
                                    'row_key'
                                ] ?? null
                            )
                            ? (string) $component[
                                'row_key'
                            ]
                            : 'component-' .
                            ($index + 1),

                        'component_name' =>
                            trim(
                                (string) (
                                    $component[
                                        'component_name'
                                    ]
                                    ?? ''
                                )
                            ),

                        'component_type' =>
                            $component[
                                'component_type'
                            ]
                            ?? OfferSalaryComponent::TYPE_EARNING,

                        'calculation_type' =>
                            $component[
                                'calculation_type'
                            ]
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

                        'amount' =>
                            $component['amount'] ?? 0,

                        'percentage' =>
                            filled(
                                $component[
                                    'percentage'
                                ] ?? null
                            )
                            ? $component[
                                'percentage'
                            ]
                            : null,

                        'frequency' =>
                            $component['frequency']
                            ?? OfferSalaryComponent::FREQUENCY_MONTHLY,

                        'show_in_offer' =>
                            filter_var(
                                $component[
                                    'show_in_offer'
                                ] ?? true,
                                FILTER_VALIDATE_BOOLEAN
                            ),

                        'affects_in_hand' =>
                            filter_var(
                                $component[
                                    'affects_in_hand'
                                ] ?? true,
                                FILTER_VALIDATE_BOOLEAN
                            ),

                        'is_taxable' =>
                            filter_var(
                                $component[
                                    'is_taxable'
                                ] ?? true,
                                FILTER_VALIDATE_BOOLEAN
                            ),

                        'sort_order' =>
                            isset(
                            $component[
                                'sort_order'
                            ]
                        )
                            ? (int) $component[
                                'sort_order'
                            ]
                            : (($index + 1) * 10),

                        'description' =>
                            filled(
                                $component[
                                    'description'
                                ] ?? null
                            )
                            ? trim(
                                (string) $component[
                                    'description'
                                ]
                            )
                            : null,
                    ];
                }
            )
            ->all();
    }
}