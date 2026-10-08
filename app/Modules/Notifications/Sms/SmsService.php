<?php

namespace App\Modules\Notifications\Sms;

use App\Jobs\SendSmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queues text messages and records each on notification_deliveries (channel `sms`). Best effort: a failure here
 * is logged and never breaks the delivery or money step that asked for the text.
 */
class SmsService
{
    /** Pure: any Nigerian number format to international digits without "+" ("0803 123 4567" => "2348031234567"); null when it can't be one. */
    public static function normalize(?string $phone): ?string
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
            default => new LogGateway,
        };
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
            // The log row needs a user; guest numbers are logged without one.
            $id = DB::table('sms_log')->insertGetId([
                'user_id' => $userId, 'phone' => $to, 'template_key' => $templateKey, 'status' => 'queued',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            SendSmsMessage::dispatch($id, $message)->afterCommit();

            return true;
        } catch (Throwable $e) {
            Log::warning("SMS queue failed: {$e->getMessage()}");

            return false;
        }
    }
}
