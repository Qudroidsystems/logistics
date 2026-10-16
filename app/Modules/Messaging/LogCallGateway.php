<?php

namespace App\Modules\Messaging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Pretends to place the call, for local testing. */
class LogCallGateway implements CallGateway
{
    public function call(string $phone): array
    {
        Log::info('call.placed', ['to' => $phone]);

        return ['ok' => true, 'ref' => 'log_'.Str::random(16), 'error' => null, 'provider' => 'log'];
    }
}
