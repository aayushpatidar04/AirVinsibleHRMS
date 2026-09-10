<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class SendCandidateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_email' => [
                'required',
                'email',
                'max:255',
            ],

            'cc' => [
                'nullable',
                'array',
                'max:10',
            ],

            'cc.*' => [
                'required',
                'email',
                'distinct',
                'max:255',
            ],

            'subject' => [
                'required',
                'string',
                'max:255',
            ],

            'message' => [
                'required',
                'string',
                'max:10000',
            ],

            'attach_pdf' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_email.required' =>
                'Candidate email address is required.',

            'recipient_email.email' =>
                'Enter a valid candidate email address.',

            'subject.required' =>
                'Email subject is required.',

            'message.required' =>
                'Email message is required.',

            'cc.*.email' =>
                'Every CC recipient must have a valid email address.',
        ];
    }
}