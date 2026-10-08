<?php

namespace App\Modules\Notifications\Sms;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Termii messaging API. The default channel is `dnd`, the transactional route that also reaches numbers on the DND list,
 * which is what offers, delivery codes and verification codes need. The base URL is per-account (shown in the Termii dashboard).
 */
class TermiiGateway implements SmsGateway
{
    public function send(string $phone, string $message): array
    {
        $cfg = config('services.termii');
        if (empty($cfg['api_key']) || empty($cfg['sender_id'])) {
            return ['ok' => false, 'ref' => null, 'error' => 'termii_not_configured', 'provider' => 'termii'];
        }
        try {
            $res = Http::timeout(15)->acceptJson()->post(rtrim($cfg['base_url'], '/').'/api/sms/send', [
                'to' => $phone,
                'from' => $cfg['sender_id'],
                'sms' => $message,
                'type' => 'plain',
                'channel' => $cfg['channel'] ?: 'dnd',
                'api_key' => $cfg['api_key'],
            ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'ref' => null, 'error' => 'network: '.$e->getMessage(), 'provider' => 'termii'];
        }
        $body = $res->json() ?? [];
        if ($res->successful() && ! empty($body['message_id'])) {
            return ['ok' => true, 'ref' => (string) $body['message_id'], 'error' => null, 'provider' => 'termii'];
        }

        return ['ok' => false, 'ref' => null, 'error' => substr('termii '.$res->status().': '.($body['message'] ?? $res->body()), 0, 250), 'provider' => 'termii'];
    }
}
