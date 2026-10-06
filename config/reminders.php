<?php
return [
    /*
    | Channels the school has enabled in .env
    | Admin can still choose a subset when sending.
    */
    'channels' => [
        'email'    => env('REMINDER_EMAIL_ENABLED', true),
        'sms'      => env('REMINDER_SMS_ENABLED', false),
        'whatsapp' => env('REMINDER_WHATSAPP_ENABLED', false),
    ],

    /*
    | How many student_ids can be sent synchronously (with an immediate
    | sent/skipped/failed summary) before falling back to the queue.
    | Above this, each student's send is dispatched as its own job.
    */
    'sync_batch_limit' => env('REMINDER_SYNC_BATCH_LIMIT', 15),

    /*
    | Contact fields, tried in order; ALL non-empty matches are used
    | (fan-out to father_phone + mother_phone if both exist), not just
    | the first. Sourced from parentRegistration + studentRegistration.
    */
    'contacts' => [
        'email_fields' => ['parent_email', 'student_email'],
        'phone_fields' => ['father_phone', 'mother_phone', 'student_phone'],
    ],

    'sms' => [
        'driver'  => env('SMS_DRIVER', 'log'), // log | termii | africastalking
        'from'    => env('SMS_FROM', 'SCHOOL'),
        'termii'  => [
            'api_key' => env('TERMII_API_KEY'),
            'sender'  => env('TERMII_SENDER', 'SCHOOL'),
            'url'     => env('TERMII_URL', 'https://api.ng.termii.com/api/sms/send'),
        ],
        'africastalking' => [
            'username' => env('AT_USERNAME'),
            'api_key'  => env('AT_API_KEY'),
            'from'     => env('AT_FROM', 'SCHOOL'),
        ],
    ],

    'whatsapp' => [
        // meta | twilio | log
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'meta' => [
            'token'             => env('WHATSAPP_META_TOKEN'),
            'phone_number_id'   => env('WHATSAPP_META_PHONE_NUMBER_ID'),
            'template_name'     => env('WHATSAPP_TEMPLATE_NAME', 'fee_reminder'),
            'template_language' => env('WHATSAPP_TEMPLATE_LANG', 'en'),
        ],
        'twilio' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_WHATSAPP_FROM'), // e.g. whatsapp:+1415...
        ],
    ],

    'school_name' => env('APP_NAME', 'School'),
];