<?php

namespace App\Jobs;

use App\Modules\Partner\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    private const BACKOFF_MINUTES = [1, 5, 30, 120, 720];

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(WebhookService $svc): void
    {
        $d = DB::table('webhook_deliveries')->find($this->deliveryId);
        $e = $d ? DB::table('webhook_endpoints')->find($d->endpoint_id) : null;
        if (! $d || ! $e || $d->delivered_at || ! $e->active) {
            return;
        }
        $attempt = $d->attempt + 1;
        try {
            $res = Http::timeout(10)->withHeaders(['X-Logistics-Signature' => $svc->sign($d->payload, $e->secret), 'Content-Type' => 'application/json'])
                ->withBody($d->payload, 'application/json')->post($e->url);
            $ok = $res->successful();
            $update = ['attempt' => $attempt, 'response_status' => $res->status(), 'response_body' => mb_substr($res->body(), 0, 500)];
        } catch (Throwable $ex) {
            $ok = false;
            $update = ['attempt' => $attempt, 'response_status' => null, 'response_body' => mb_substr($ex->getMessage(), 0, 500)];
        }

        if ($ok) {
            $update += ['delivered_at' => now(), 'next_retry_at' => null];
        } elseif ($attempt <= count(self::BACKOFF_MINUTES)) {
            $mins = self::BACKOFF_MINUTES[$attempt - 1];
            $update += ['next_retry_at' => now()->addMinutes($mins)];
            self::dispatch($this->deliveryId)->delay(now()->addMinutes($mins));
        }
        DB::table('webhook_deliveries')->where('id', $this->deliveryId)->update($update + ['updated_at' => now()]);
    }
}
