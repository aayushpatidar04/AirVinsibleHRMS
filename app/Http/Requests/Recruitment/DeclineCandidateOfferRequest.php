<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class DeclineCandidateOfferRequest extends FormRequest
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

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:3000',
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

            'reason.required' =>
                'Please tell us why you are declining this offer.',

            'reason.min' =>
                'Please provide a slightly more detailed reason.',

            'consent.accepted' =>
                'Please confirm your decision before submitting.',
        ];
    }
}