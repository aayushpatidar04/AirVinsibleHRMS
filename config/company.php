<?php

return [
    'name' => env(
        'COMPANY_NAME',
        config('app.name')
    ),

    'legal_name' => env(
        'COMPANY_LEGAL_NAME',
        env('COMPANY_NAME', config('app.name'))
    ),

    'address' => env(
        'COMPANY_ADDRESS'
    ),

    'email' => env(
        'COMPANY_EMAIL'
    ),

    'phone' => env(
        'COMPANY_PHONE'
    ),

    'website' => env(
        'COMPANY_WEBSITE'
    ),

    'logo_path' => env(
        'COMPANY_LOGO_PATH',
        'images/company-logo.png'
    ),

    'hr_name' => env(
        'COMPANY_HR_NAME',
        'Human Resources'
    ),

    'hr_designation' => env(
        'COMPANY_HR_DESIGNATION',
        'HR Department'
    ),

    'hr_signature_path' => env(
        'COMPANY_HR_SIGNATURE_PATH',
        'images/hr-signature.png'
    ),
];