<?php

return [
    'enabled' => filter_var(
        env('CAPTCHA_ENABLED', true),
        FILTER_VALIDATE_BOOL
    ),

    'driver' => env('CAPTCHA_DRIVER', 'internal'),

    'ttl_seconds' => (int) env('CAPTCHA_TTL_SECONDS', 300),

    'google' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    ],
];
