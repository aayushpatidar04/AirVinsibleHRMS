<?php

return [
    'offer_portal' => [
        'default_expiry_days' => env(
            'OFFER_PORTAL_EXPIRY_DAYS',
            15
        ),

        'support_email' => env(
            'OFFER_PORTAL_SUPPORT_EMAIL',
            env('COMPANY_EMAIL')
        ),

        'support_phone' => env(
            'OFFER_PORTAL_SUPPORT_PHONE',
            env('COMPANY_PHONE')
        ),
    ],
];