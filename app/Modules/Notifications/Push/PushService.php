<?php

namespace App\Modules\Notifications\Push;

use App\Jobs\SendPushMessage;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Queues one push per registered device of a user. Best effort: never throws into the caller. */
class PushService
{
    public static function gateway(): PushGateway
    {
        return config('services.push.driver') === 'fcm' ? new FcmGateway() : new LogPushGateway();
    }

    /** @param array<string,scalar|null> $data */
    public function toUser(int $userId, string $title, string $body, array $data = []): void
    {
        try {
            $tokens = DB::table('devices')->where('user_id', $userId)->whereNotNull('push_token')->pluck('push_token')->unique();
            foreach ($tokens as $t) {
                SendPushMessage::dispatch($t, $title, $body, $data)->afterCommit();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
