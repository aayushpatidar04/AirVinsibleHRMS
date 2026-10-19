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
        'assets/images/logo.png'
    ),

    // Footer line printed on every page of the letter PDFs.
    'letter_footer' => env(
        'COMPANY_LETTER_FOOTER',
        '300, 3rd Floor, Mangal City Mall, Vijay Nagar, Indore, M.P. – 452011 Mail ID: - hr@airvincible.com'
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