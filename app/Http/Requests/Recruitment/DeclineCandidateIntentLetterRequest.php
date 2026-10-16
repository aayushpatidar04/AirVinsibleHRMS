<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class DeclineCandidateIntentLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_name' => ['required', 'string', 'min:2', 'max:255'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'confirmation' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'candidate_name.required' => 'Please enter your full name.',
            'reason.required' => 'Please provide a reason for declining the Letter of Intent.',
            'confirmation.accepted' => 'Please confirm that you want to decline this Letter of Intent.',
        ];
    }
}
