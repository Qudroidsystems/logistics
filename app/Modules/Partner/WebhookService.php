<?php

namespace App\Modules\Partner;

use App\Jobs\DeliverWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebhookService
{
    /** Queue one signed delivery per active endpoint subscribed to the event. */
    public function emit(int $operatorId, string $event, array $data, bool $isTest = false): void
    {
        $eventId = 'evt_'.Str::lower(Str::random(24));
        $endpoints = DB::table('webhook_endpoints')->where('operator_id', $operatorId)->where('active', true)->where('is_test', $isTest)->get();
        foreach ($endpoints as $e) {
            $subs = json_decode($e->events, true) ?: [];
            if ($subs && ! in_array($event, $subs, true) && ! in_array('*', $subs, true)) {
                continue;
            }
            $id = DB::table('webhook_deliveries')->insertGetId([
                'endpoint_id' => $e->id, 'event' => $event, 'event_id' => $eventId,
                'payload' => json_encode(['id' => $eventId, 'type' => $event, 'created' => now()->toIso8601String(), 'data' => $data]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DeliverWebhook::dispatch($id)->afterCommit();
        }
    }

    /** Header value: t=<unix>,v1=<hmac_sha256("t.body")>. Receivers reject timestamps older than 5 minutes. */
    public function sign(string $body, string $secret, ?int $t = null): string
    {
        $t ??= time();

        return "t={$t},v1=".hash_hmac('sha256', "{$t}.{$body}", $secret);
    }
}
