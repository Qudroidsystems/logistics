<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // SMS through Twilio: set SMS_DRIVER=twilio. Use a Messaging Service SID (MG...) or a Twilio phone number (+1...).
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN', env('TWILIO_TOKEN')),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
    ],
    'device' => [
        'key' => env('DEVICE_API_KEY'),
    ],

    'paystack' => [
        'active'          => env('PAYSTACK_ACTIVE', true),
        'mode'            => env('PAYSTACK_MODE', 'sandbox'),
        'secret_key'      => env('PAYSTACK_SECRET_KEY'),
        'public_key'      => env('PAYSTACK_PUBLIC_KEY'),
        'test_secret_key' => env('PAYSTACK_TEST_SECRET_KEY'),
        'test_public_key' => env('PAYSTACK_TEST_PUBLIC_KEY'),
        'live_secret_key' => env('PAYSTACK_LIVE_SECRET_KEY'),
        'live_public_key' => env('PAYSTACK_LIVE_PUBLIC_KEY'),
    ],

    'push' => [
        // log (default, nothing is sent) | fcm
        'driver' => env('PUSH_DRIVER', 'log'),
    ],

    'fcm' => [
        // Path to the Firebase service-account JSON (keep it outside git, e.g. storage/app/private/fcm.json)
        'credentials' => env('FCM_CREDENTIALS'),
    ],

    'sms' => [
        // log (default, nothing is sent) | termii (Nigeria) | twilio (Canada, US and most other countries)
        'driver' => env('SMS_DRIVER', 'log'),
        // WhatsApp for the same messages, once WhatsApp is switched on and filled in under Notification Settings:
        //   instead (default)  WhatsApp first; a message WhatsApp cannot deliver goes out as a text
        //   also               both a text and a WhatsApp message
        //   off                texts only
        'whatsapp' => env('SMS_WHATSAPP', 'instead'),
    ],

    'termii' => [
        'api_key'   => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID'),
        'base_url'  => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
        // dnd = transactional route that reaches DND numbers; generic = cheaper, may be blocked by DND
        'channel'   => env('TERMII_CHANNEL', 'dnd'),
    ],

    // Calls between rider and customer through one shared number, so neither sees the other's phone.
    'calls' => [
        // off (default: the rider app dials directly, as before) | log (pretend, for testing) | africastalking
        'driver' => env('CALLS_DRIVER', 'off'),
        // The shared number, in international form (+234...). Callers see this instead of the other phone.
        'number' => env('CALLS_NUMBER'),
        // Put in the callback URL set on the number in the Africa's Talking dashboard: /api/v1/voice/{secret}
        'callback_secret' => env('CALLS_CALLBACK_SECRET'),
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'voice_url' => env('AFRICASTALKING_VOICE_URL', 'https://voice.africastalking.com'),
    ],

    // Accounting sync. Each provider connects its own company; these are the app's developer keys.
    'quickbooks' => [
        'client_id' => env('QUICKBOOKS_CLIENT_ID'),
        'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
        // sandbox | production
        'environment' => env('QUICKBOOKS_ENVIRONMENT', 'sandbox'),
    ],

    'xero' => [
        'client_id' => env('XERO_CLIENT_ID'),
        'client_secret' => env('XERO_CLIENT_SECRET'),
        'scopes' => env('XERO_SCOPES', 'offline_access accounting.transactions accounting.settings.read'),
    ],

];
