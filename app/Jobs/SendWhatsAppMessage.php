<?php

namespace App\Jobs;

use App\Services\Messaging\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Sends one notice on WhatsApp (the approved two-variable template set up under Notification Settings) and records
 * the outcome on sms_log (channel `whatsapp`). When WhatsApp is the only channel for this message ($fallbackToSms),
 * a message WhatsApp refuses, or that still fails after the retries, is sent as a normal text instead.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [20, 120];

    public function __construct(public int $logId, public string $message, public bool $fallbackToSms = false)
    {
    }

    public function handle(MessagingService $messaging): void
    {
        $row = DB::table('sms_log')->find($this->logId);
        if (! $row || $row->status === 'sent') {
            return;
        }
        $name = $row->user_id ? trim((string) DB::table('users')->where('id', $row->user_id)->value('name')) : '';
        $first = $name === '' ? 'there' : explode(' ', $name)[0];
        $r = $messaging->send('whatsapp', $row->phone, $this->message, ['name' => $first]);
        $ok = ($r['status'] ?? '') === 'sent';
        DB::table('sms_log')->where('id', $row->id)->update([
            'status' => $ok ? 'sent' : 'failed', 'provider' => 'whatsapp', 'provider_ref' => $ok ? mb_substr((string) ($r['id'] ?? ''), 0, 80) : null,
            'error' => $ok ? null : ($r['error'] ?? 'WhatsApp send failed'), 'attempts' => DB::raw('attempts + 1'), 'sent_at' => $ok ? now() : null, 'updated_at' => now(),
        ]);
        if ($ok) {
            return;
        }
        // Worth another go later (rate limit, provider down): let the queue retry while tries remain.
        if (! empty($r['retryable']) && $this->attempts() < $this->tries) {
            $this->release($this->backoff[$this->attempts() - 1] ?? 120);

            return;
        }
        $this->sendAsText();
    }

    /** The job crashed on its last try: the message still reaches the person as a text. */
    public function failed(?\Throwable $e = null): void
    {
        $this->sendAsText();
    }

    private function sendAsText(): void
    {
        if (! $this->fallbackToSms) {
            return;
        }
        $row = DB::table('sms_log')->find($this->logId);
        if (! $row || $row->status === 'sent') {
            return;
        }
        // Mark the WhatsApp row so a second call (handle, then failed) cannot send the text twice.
        $claimed = DB::table('sms_log')->where('id', $row->id)->where('status', '<>', 'fell_back')->update(['status' => 'fell_back', 'updated_at' => now()]);
        if (! $claimed) {
            return;
        }
        $id = DB::table('sms_log')->insertGetId([
            'user_id' => $row->user_id, 'phone' => $row->phone, 'channel' => 'sms', 'template_key' => $row->template_key, 'status' => 'queued',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        SendSmsMessage::dispatch($id, $this->message);
    }
}
