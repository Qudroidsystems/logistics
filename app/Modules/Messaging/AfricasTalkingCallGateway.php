<?php

namespace App\Modules\Messaging;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Africa's Talking voice: rings the caller from the shared number, then our callback (/api/v1/voice/{secret}) connects them. */
class AfricasTalkingCallGateway implements CallGateway
{
    public function call(string $phone): array
    {
        $cfg = config('services.africastalking');
        $from = config('services.calls.number');
        if (empty($cfg['username']) || empty($cfg['api_key']) || empty($from)) {
            return ['ok' => false, 'ref' => null, 'error' => 'africastalking_not_configured', 'provider' => 'africastalking'];
        }
        try {
            $res = Http::timeout(15)->acceptJson()->asForm()->withHeaders(['apiKey' => $cfg['api_key']])
                ->post(rtrim($cfg['voice_url'], '/').'/call', ['username' => $cfg['username'], 'from' => $from, 'to' => '+'.$phone]);
        } catch (Throwable $e) {
            return ['ok' => false, 'ref' => null, 'error' => 'network: '.$e->getMessage(), 'provider' => 'africastalking'];
        }
        $entry = $res->json('entries.0') ?? [];
        if ($res->successful() && ! empty($entry['sessionId']) && ($entry['status'] ?? '') === 'Queued') {
            return ['ok' => true, 'ref' => (string) $entry['sessionId'], 'error' => null, 'provider' => 'africastalking'];
        }

        return ['ok' => false, 'ref' => null, 'error' => substr('africastalking '.$res->status().': '.($entry['status'] ?? $res->json('errorMessage') ?? $res->body()), 0, 250), 'provider' => 'africastalking'];
    }
}
