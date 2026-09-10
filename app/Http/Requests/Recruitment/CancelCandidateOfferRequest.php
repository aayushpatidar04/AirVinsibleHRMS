<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class CancelCandidateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' =>
                'Please enter the reason for cancelling this offer.',

            'reason.min' =>
                'The cancellation reason must contain at least 5 characters.',
        ];
    }
}