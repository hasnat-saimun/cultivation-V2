<?php

return [
    // Toggle sending on present/absent
    'sms_on_present' => env('SMS_ON_PRESENT', false),
    'sms_on_absent' => env('SMS_ON_ABSENT', true),

    // Message templates. Available placeholders: {student}, {date}, {status}
    'sms_message_present' => env('SMS_MESSAGE_PRESENT', 'Hello {student}, your attendance for {date} is {status}.'),
    'sms_message_absent' => env('SMS_MESSAGE_ABSENT', 'Alert: {student} was marked {status} on {date}.'),

    // Generic HTTP gateway options
    'provider' => env('SMS_PROVIDER', 'http'),
    'api_url' => env('SMS_API_URL', ''),
    'api_key' => env('SMS_API_KEY', ''),
    'sender' => env('SMS_SENDER', ''),
    'timeout' => env('SMS_TIMEOUT', 10),
    // Twilio specific
    'twilio_account_sid' => env('TWILIO_ACCOUNT_SID', ''),
    'twilio_auth_token' => env('TWILIO_AUTH_TOKEN', ''),
    'twilio_from' => env('TWILIO_FROM', ''),
    // HTTP provider parameter mapping. Map request param name => template.
    // Templates support placeholders: {to}, {message}, {api_key}, {sender}
    'http_param_map' => [
        'to' => '{to}',
        'message' => '{message}',
        'api_key' => '{api_key}',
        'sender' => '{sender}',
    ],
    // Optional: per-provider SMS pricing information (for admin display)
    'rates' => [
        'http' => [
            'per_sms' => env('SMS_RATE_HTTP_PER_SMS', 0.40),
            'currency' => env('SMS_RATE_HTTP_CURRENCY', 'BDT'),
            'note' => 'Example HTTP gateway rate (per single SMS)',
        ],
        'twilio' => [
            'per_sms' => env('SMS_RATE_TWILIO_PER_SMS', 0.05),
            'currency' => env('SMS_RATE_TWILIO_CURRENCY', 'USD'),
            'note' => 'Twilio example rate; actual charged by Twilio',
        ],
    ],
    // Alpha SMS API endpoint for fetching live plans/rates
    'alpha_rate_url' => env('ALPHA_SMS_RATE_URL', 'https://sms.net.bd/#pricing'),
];
