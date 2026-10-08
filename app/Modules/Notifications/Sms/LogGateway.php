<?php

namespace App\Modules\Notifications\Sms;

use Illuminate\Support\Facades\Log;

/** Used when no provider is configured (local/dev): writes the message to the log instead of sending it. */
class LogGateway implements SmsGateway
{
    public function send(string $phone, string $message): array
    {
        Log::info("SMS (not sent, no provider configured) to {$phone}: {$message}");

        return ['ok' => true, 'ref' => null, 'error' => null, 'provider' => 'log'];
    }
}
