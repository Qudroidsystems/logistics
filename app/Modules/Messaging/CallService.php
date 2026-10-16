<?php

namespace App\Modules\Messaging;

use App\Modules\Notifications\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Calls between the rider and the customer (or the people at a pickup or drop-off) through the company's shared
 * number. We ring the caller first; when they answer, the voice provider asks us who to connect, and both phones
 * show only the shared number. Someone who calls the shared number back is connected to the other person on their
 * live job. With calls off, the rider app dials directly as before.
 */
class CallService
{
    /** Calls one person may start on one job in an hour. */
    public const HOURLY_LIMIT = 10;

    /** How long after the last call the shared number still connects a call back. */
    public const CALL_BACK_HOURS = 6;

    public const WHO = ['customer' => 'the customer', 'driver' => 'your rider', 'pickup' => 'the sender', 'dropoff' => 'the receiver'];

    public function __construct(private ChatService $chat) {}

    public function enabled(): bool
    {
        return in_array(config('services.calls.driver'), ['log', 'africastalking'], true);
    }

    public static function gateway(): CallGateway
    {
        return match (config('services.calls.driver')) {
            'africastalking' => new AfricasTalkingCallGateway,
            default => new LogCallGateway,
        };
    }

    /** Last four digits only, for showing the rider who they are about to call. */
    public static function hide(?string $phone): string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);

        return $d === '' ? '' : '•••• '.substr($d, -4);
    }

    /**
     * Ring the caller and connect them to $who once they answer.
     *
     * @param  string  $who  customer | driver | pickup | dropoff
     */
    public function start(int $shipmentId, int $userId, string $who): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Calls through the app are not set up.');
        }
        $sides = $this->chat->sides($shipmentId);
        $role = $sides ? $this->chat->role($sides, $userId) : null;
        if (! $role) {
            throw new RuntimeException('This job is not yours.');
        }
        if (! $sides->open) {
            throw new RuntimeException('Calls are only for a job a rider is on.');
        }
        $allowed = $role === 'customer' ? ['driver'] : ['customer', 'pickup', 'dropoff'];
        if (! in_array($who, $allowed, true)) {
            throw new RuntimeException('You cannot call that person on this job.');
        }

        $from = SmsService::normalize(DB::table('users')->where('id', $userId)->value('phone_number'));
        if (! $from) {
            throw new RuntimeException('Add your phone number to your profile first, so we can ring you.');
        }
        $to = SmsService::normalize(match ($who) {
            'customer' => DB::table('users')->where('id', $sides->customer_id)->value('phone_number'),
            'driver' => DB::table('users')->where('id', $sides->driver_id)->value('phone_number'),
            default => DB::table('shipment_stops')->where('shipment_id', $shipmentId)->where('type', $who)->orderBy('seq')->value('contact_phone'),
        });
        if (! $to) {
            throw new RuntimeException('There is no phone number for '.self::WHO[$who].'.');
        }
        if ($to === $from) {
            throw new RuntimeException('That is your own number.');
        }
        $recent = DB::table('call_logs')->where('shipment_id', $shipmentId)->where('caller_id', $userId)->where('created_at', '>', now()->subHour())->count();
        if ($recent >= self::HOURLY_LIMIT) {
            throw new RuntimeException('Too many calls on this job in the last hour. Send a chat message instead.');
        }

        $res = self::gateway()->call($from);
        DB::table('call_logs')->insert([
            'shipment_id' => $shipmentId, 'caller_id' => $userId, 'purpose' => $who, 'from_number' => $from, 'to_number' => $to,
            'provider' => $res['provider'], 'provider_ref' => $res['ref'], 'outcome' => $res['ok'] ? 'ringing' : 'failed', 'created_at' => now(),
        ]);
        if (! $res['ok']) {
            report(new RuntimeException("Call on shipment {$shipmentId} failed: {$res['error']}"));
            throw new RuntimeException('We could not place the call just now. Try again, or send a chat message.');
        }
        $this->remember($shipmentId, $from, $to);

        return ['ok' => true, 'message' => "We're ringing your phone now. Answer it and we'll connect you to ".self::WHO[$who].'.'];
    }

    /**
     * The voice provider's callback. Returns the reply XML: connect an answered call, or say why not.
     *
     * @param  array<string,mixed>  $p  Africa's Talking fields: sessionId, isActive, direction, callerNumber, durationInSeconds, status
     */
    public function answer(array $p): string
    {
        $ref = (string) ($p['sessionId'] ?? '');
        if ((string) ($p['isActive'] ?? '1') === '0') {
            // The call has ended: keep how it went.
            DB::table('call_logs')->where('provider_ref', $ref)->update([
                'duration_s' => max(0, (int) ($p['durationInSeconds'] ?? 0)), 'outcome' => substr(strtolower((string) ($p['status'] ?? 'ended')), 0, 16), 'ended_at' => now(),
            ]);

            return self::xml('');
        }

        $call = $ref !== '' ? DB::table('call_logs')->where('provider_ref', $ref)->first() : null;
        if (! $call && strtolower((string) ($p['direction'] ?? '')) === 'inbound') {
            $call = $this->callBack($ref, SmsService::normalize((string) ($p['callerNumber'] ?? '')));
        }
        if (! $call) {
            return self::xml('<Say>This number only connects calls about a delivery that is under way. Please use the app to reach your rider.</Say>');
        }
        DB::table('call_logs')->where('id', $call->id)->update(['outcome' => 'connecting']);
        $number = config('services.calls.number');

        return self::xml('<Dial phoneNumbers="+'.e($call->to_number).'"'.($number ? ' callerId="'.e($number).'"' : '').' />');
    }

    /** Someone rang the shared number: connect them to the other person on their most recent live job. */
    private function callBack(string $ref, ?string $caller): ?object
    {
        if (! $caller) {
            return null;
        }
        $row = DB::table('masked_numbers')->where('proxy_number', (string) config('services.calls.number'))->where('expires_at', '>', now())
            ->whereRaw('participants @> ?::jsonb', [json_encode([$caller])])->orderByDesc('expires_at')->first();
        if (! $row || ! $this->chat->sides((int) $row->shipment_id)?->open) {
            return null;
        }
        $other = collect(json_decode($row->participants, true))->first(fn ($n) => $n !== $caller);
        if (! $other) {
            return null;
        }
        $id = DB::table('call_logs')->insertGetId([
            'shipment_id' => $row->shipment_id, 'purpose' => 'inbound', 'from_number' => $caller, 'to_number' => $other,
            'provider' => (string) config('services.calls.driver'), 'provider_ref' => $ref ?: null, 'outcome' => 'ringing', 'created_at' => now(),
        ]);

        return DB::table('call_logs')->where('id', $id)->first();
    }

    /** Let either side ring the shared number back for a while. */
    private function remember(int $shipmentId, string $a, string $b): void
    {
        $proxy = (string) config('services.calls.number');
        $pair = [$a, $b];
        sort($pair);
        $existing = DB::table('masked_numbers')->where('shipment_id', $shipmentId)->where('proxy_number', $proxy)
            ->whereRaw('participants = ?::jsonb', [json_encode($pair)])->value('id');
        $until = now()->addHours(self::CALL_BACK_HOURS);
        $existing
            ? DB::table('masked_numbers')->where('id', $existing)->update(['expires_at' => $until])
            : DB::table('masked_numbers')->insert(['shipment_id' => $shipmentId, 'proxy_number' => $proxy, 'participants' => json_encode($pair), 'expires_at' => $until]);
    }

    private static function xml(string $inner): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Response>'.$inner.'</Response>';
    }
}
