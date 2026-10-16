<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class SaveCandidateIntentLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_title' => ['required', 'string', 'max:255'],
            'work_location' => ['required', 'string', 'max:255'],
            'monthly_ctc' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'monthly_in_hand' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'joining_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
