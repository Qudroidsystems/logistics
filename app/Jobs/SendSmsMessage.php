<?php

namespace App\Jobs;

use App\Modules\Notifications\Sms\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/** Sends one text and records the outcome on sms_log. A failed send is retried; the last failure stays on the row. */
class SendSmsMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [20, 120];

    public function __construct(public int $logId, public string $message)
    {
    }

    public function handle(): void
    {
        $row = DB::table('sms_log')->find($this->logId);
        if (! $row || $row->status === 'sent') {
            return;
        }
        $gw = SmsService::gateway();
        $r = $gw->send($row->phone, $this->message);
        DB::table('sms_log')->where('id', $row->id)->update([
            'status' => $r['ok'] ? 'sent' : 'failed', 'provider' => $r['provider'], 'provider_ref' => $r['ref'],
            'error' => $r['error'], 'attempts' => DB::raw('attempts + 1'), 'sent_at' => $r['ok'] ? now() : null, 'updated_at' => now(),
        ]);
        if (! $r['ok']) {
            $this->fail(new \RuntimeException($r['error'] ?? 'sms failed'));
        }
    }
}
