<?php

namespace App\Modules\Notifications\Sms;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Twilio Programmable Messaging (North America and most other countries). Sends from a Twilio number (TWILIO_PHONE_NUMBER,
 * "+12265550123") or, better, a Messaging Service (TWILIO_MESSAGING_SERVICE_SID, "MG..."), which handles number
 * pools, opt-outs (STOP) and Canadian/US carrier registration.
 */
class TwilioGateway implements SmsGateway
{
    public function send(string $phone, string $message): array
    {
        $cfg = config('services.twilio');
        $from = trim((string) ($cfg['phone_number'] ?? ''));
        $service = trim((string) ($cfg['messaging_service_sid'] ?? ''));
        if (empty($cfg['sid']) || empty($cfg['auth_token']) || ($from === '' && $service === '')) {
            return ['ok' => false, 'ref' => null, 'error' => 'twilio_not_configured', 'provider' => 'twilio'];
        }

        return self::post((string) $cfg['sid'], (string) $cfg['auth_token'], $phone, $message, $from, $service);
    }

    /** Shared with the admin "Notices" sender (MessagingService), which keeps its own Twilio credentials. */
    public static function post(string $sid, string $token, string $phone, string $message, string $from = '', string $service = ''): array
    {
        $to = '+'.ltrim($phone, '+');
        $body = ['To' => $to, 'Body' => $message] + ($service !== '' ? ['MessagingServiceSid' => $service] : ['From' => $from]);
        try {
            $res = Http::timeout(15)->acceptJson()->asForm()->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $body);
        } catch (Throwable $e) {
            return ['ok' => false, 'ref' => null, 'error' => 'network: '.$e->getMessage(), 'provider' => 'twilio'];
        }
        $json = $res->json() ?? [];
        if ($res->successful() && ! empty($json['sid'])) {
            return ['ok' => true, 'ref' => (string) $json['sid'], 'error' => null, 'provider' => 'twilio'];
        }

        return ['ok' => false, 'ref' => null, 'error' => substr('twilio '.$res->status().': '.($json['message'] ?? $res->body()), 0, 250), 'provider' => 'twilio'];
    }
}
