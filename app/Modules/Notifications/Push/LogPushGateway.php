<?php

namespace App\Modules\Notifications\Push;

use Illuminate\Support\Facades\Log;

/** Default driver: writes the push to the log and sends nothing. */
class LogPushGateway implements PushGateway
{
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        Log::info('PUSH (log driver)', ['token' => substr($token, 0, 10).'…', 'title' => $title, 'body' => $body, 'data' => $data]);

        return ['ok' => true, 'invalid' => false, 'error' => null];
    }
}
