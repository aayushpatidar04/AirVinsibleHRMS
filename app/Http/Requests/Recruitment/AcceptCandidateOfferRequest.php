<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class AcceptCandidateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'consent' => [
                'accepted',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'candidate_name.required' =>
                'Please enter your full name.',

            'consent.accepted' =>
                'You must confirm that you have read and accepted the offer.',
        ];
    }
}