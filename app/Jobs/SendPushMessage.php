<?php

namespace App\Jobs;

use App\Modules\Notifications\Push\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/** Sends one push to one device token. A token the provider says is dead is removed from the device row. */
class SendPushMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [15, 90];

    public function __construct(public string $token, public string $title, public string $body, public array $data = [])
    {
    }

    public function handle(): void
    {
        $r = PushService::gateway()->send($this->token, $this->title, $this->body, $this->data);
        if ($r['invalid']) {
            DB::table('devices')->where('push_token', $this->token)->update(['push_token' => null]);

            return;
        }
        if (! $r['ok']) {
            $this->fail(new \RuntimeException($r['error'] ?? 'push failed'));
        }
    }
}
