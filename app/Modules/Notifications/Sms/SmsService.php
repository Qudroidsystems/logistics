<?php

namespace App\Modules\Notifications\Sms;

use App\Jobs\SendSmsMessage;
use App\Jobs\SendWhatsAppMessage;
use App\Services\Messaging\MessagingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queues text messages and records each on sms_log. Best effort: a failure here is logged and never breaks the
 * delivery or money step that asked for the text.
 *
 * When WhatsApp is switched on (Notification Settings) the same message also, or instead, goes out on WhatsApp,
 * per config('services.sms.whatsapp'): see channels().
 */
class SmsService
{
    /**
     * Any local or international form to digits with the country code and no "+" ("0803 123 4567" => "2348031234567"
     * on a Nigerian install, "(226) 339-8404" => "12263398404" on a Canadian one); null when it can't be a number.
     */
    public static function normalize(?string $phone): ?string
    {
        return \App\Support\Phone::international($phone);
    }

    /** The original Nigeria-only rule, kept for reference by older callers. */
    public static function normalizeNigerian(?string $phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '234') && strlen($d) === 13) {
            return $d;
        }
        if (str_starts_with($d, '0') && strlen($d) === 11) {
            return '234'.substr($d, 1);
        }
        if (strlen($d) === 10 && in_array($d[0], ['7', '8', '9'], true)) {
            return '234'.$d;
        }

        return null;
    }

    public static function gateway(): SmsGateway
    {
        return match (config('services.sms.driver', 'log')) {
            'termii' => new TermiiGateway,
            'twilio' => new TwilioGateway,
            default => new LogGateway,
        };
    }

    /**
     * Pure: which channels one message goes out on.
     *
     * @return list<string> 'sms' and/or 'whatsapp'
     */
    public static function channels(string $mode, bool $whatsappReady): array
    {
        if (! $whatsappReady) {
            return ['sms'];
        }

        return match ($mode) {
            'instead' => ['whatsapp'],
            'also' => ['sms', 'whatsapp'],
            default => ['sms'],
        };
    }

    /** WhatsApp is switched on and fully filled in under Notification Settings. */
    public static function whatsappReady(): bool
    {
        try {
            return app(MessagingService::class)->enabled('whatsapp');
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Queue a text to a user's own phone number. Returns false when they have none or the number is unusable. */
    public function toUser(int $userId, string $message, ?string $templateKey = null): bool
    {
        $phone = self::normalize(DB::table('users')->where('id', $userId)->value('phone_number'));

        return $phone ? $this->toNumber($phone, $message, $userId, $templateKey) : false;
    }

    /** Queue a text to any number (e.g. a delivery recipient). $userId is only for the delivery log. */
    public function toNumber(?string $phone, string $message, ?int $userId = null, ?string $templateKey = null): bool
    {
        $to = self::normalize($phone);
        if (! $to) {
            return false;
        }
        try {
            $channels = self::channels((string) config('services.sms.whatsapp', 'instead'), self::whatsappReady());
            foreach ($channels as $channel) {
                // The log row needs a user; guest numbers are logged without one.
                $id = DB::table('sms_log')->insertGetId([
                    'user_id' => $userId, 'phone' => $to, 'channel' => $channel, 'template_key' => $templateKey, 'status' => 'queued',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                if ($channel === 'whatsapp') {
                    // Alone on WhatsApp, the message falls back to a text if WhatsApp cannot deliver it.
                    SendWhatsAppMessage::dispatch($id, $message, ! in_array('sms', $channels, true))->afterCommit();
                } else {
                    SendSmsMessage::dispatch($id, $message)->afterCommit();
                }
            }

            return true;
        } catch (Throwable $e) {
            Log::warning("SMS queue failed: {$e->getMessage()}");

            return false;
        }
    }
}
