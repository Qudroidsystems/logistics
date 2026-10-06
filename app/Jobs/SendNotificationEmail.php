<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Sends one notification email and records the outcome on notification_deliveries. */
class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $deliveryId, public string $subject, public string $body)
    {
    }

    public function handle(): void
    {
        $d = DB::table('notification_deliveries')->find($this->deliveryId);
        $email = $d ? DB::table('users')->where('id', $d->user_id)->value('email') : null;
        if (! $d || ! $email || $d->status === 'sent') {
            return;
        }

        Mail::raw($this->body."\n\n— ".config('app.name'), fn ($m) => $m->to($email)->subject($this->subject));
        DB::table('notification_deliveries')->where('id', $d->id)->update(['status' => 'sent', 'provider' => 'mail', 'sent_at' => now(), 'updated_at' => now()]);
    }

    public function failed(\Throwable $e): void
    {
        DB::table('notification_deliveries')->where('id', $this->deliveryId)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
    }
}
