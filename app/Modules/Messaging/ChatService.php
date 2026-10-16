<?php

namespace App\Modules\Messaging;

use App\Modules\Dispatch\DriverService;
use App\Modules\Notifications\Push\PushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Chat between the customer and the rider carrying their parcel. It opens when a rider takes the job and turns
 * read-only when the job ends. A rider who is taken off the job loses the chat; the next rider sees it.
 */
class ChatService
{
    public const MAX_LENGTH = 1000;

    /** One-tap messages for slow networks and riders on the move. */
    public const QUICK = [
        'driver' => ["I'm on my way", "I've arrived", 'Please come outside', "I can't find the address. Please call me"],
        'customer' => ["I'm coming out", 'Please wait a few minutes', 'Call me when you arrive', 'Please leave it with security'],
    ];

    public function __construct(private PushService $push) {}

    /** The customer, the current rider (if any) and whether the chat is open. */
    public function sides(int $shipmentId): ?object
    {
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $shipmentId)
            ->first(['s.id', 's.public_id', 's.operator_id', 'o.order_number', 'o.customer_id']);
        if (! $s) {
            return null;
        }
        $rider = DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')->join('users as u', 'u.id', '=', 'd.user_id')
            ->where('a.shipment_id', $shipmentId)->whereIn('a.status', DriverService::LIVE)->first(['u.id', 'u.name']);
        $s->driver_id = $rider ? (int) $rider->id : null;
        $s->driver_name = $rider ? self::firstName($rider->name) : null;
        $s->customer_name = self::firstName((string) DB::table('users')->where('id', $s->customer_id)->value('name'));
        $s->open = (bool) $rider;

        return $s;
    }

    /** "customer" or "driver" for someone on this job, else null. A past rider is no longer on it. */
    public function role(object $sides, int $userId): ?string
    {
        return match (true) {
            (int) $sides->customer_id === $userId => 'customer',
            $sides->driver_id === $userId => 'driver',
            default => null,
        };
    }

    /** The chat as the person sees it, with messages after $after (an id), marking the other side's as read. */
    public function thread(int $shipmentId, int $userId, int $after = 0): ?array
    {
        $sides = $this->sides($shipmentId);
        $role = $sides ? $this->role($sides, $userId) : null;
        if (! $role) {
            return null;
        }
        $conv = DB::table('conversations')->where(['shipment_id' => $shipmentId, 'type' => 'customer_driver'])->value('id');
        $rows = $conv ? DB::table('messages')->where('conversation_id', $conv)->where('id', '>', $after)->orderBy('id')->limit(200)->get() : collect();
        if ($conv && $rows->isNotEmpty()) {
            DB::table('messages')->where('conversation_id', $conv)->where('sender_id', '<>', $userId)->whereNull('read_at')->update(['read_at' => now()]);
        }

        return [
            'shipment' => $sides->public_id, 'order_number' => $sides->order_number, 'open' => $sides->open, 'me' => $role,
            'other' => $role === 'customer' ? ($sides->driver_name ? "{$sides->driver_name} (rider)" : 'Your rider') : ($sides->customer_name ?: 'Customer'),
            'closed_note' => $sides->open ? null : ($role === 'customer' ? 'The chat opens when a rider takes your job and closes when it ends.' : 'This job has ended, so the chat is closed.'),
            'quick' => $sides->open ? self::QUICK[$role] : [],
            'calls' => $sides->open && app(CallService::class)->enabled(),
            'messages' => $rows->map(fn ($m) => [
                'id' => (int) $m->id, 'mine' => (int) $m->sender_id === $userId, 'kind' => $m->kind, 'body' => $m->body,
                'at' => $m->created_at, 'read' => $m->read_at !== null,
            ])->all(),
        ];
    }

    /** For the order screen: can they chat now, with whom, and how many unread. */
    public function summary(int $shipmentId, int $userId): array
    {
        $sides = $this->sides($shipmentId);
        $unread = DB::table('messages as m')->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.shipment_id', $shipmentId)->where('c.type', 'customer_driver')->where('m.sender_id', '<>', $userId)->whereNull('m.read_at')->count();
        $any = $unread > 0 || DB::table('conversations')->where(['shipment_id' => $shipmentId, 'type' => 'customer_driver'])->exists();

        return ['open' => (bool) $sides?->open, 'rider' => $sides?->driver_name, 'unread' => $unread, 'has_messages' => $any,
            'calls' => $sides?->open && app(CallService::class)->enabled()];
    }

    public function send(int $shipmentId, int $userId, string $body): array
    {
        $body = trim(preg_replace('/\s+/u', ' ', $body));
        if ($body === '') {
            throw new RuntimeException('Type a message first.');
        }
        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw new RuntimeException('That message is too long. Keep it under '.self::MAX_LENGTH.' characters.');
        }
        $sides = $this->sides($shipmentId);
        $role = $sides ? $this->role($sides, $userId) : null;
        if (! $role) {
            throw new RuntimeException('This chat is not yours.');
        }
        if (! $sides->open) {
            throw new RuntimeException($role === 'customer' ? 'You can chat once a rider takes your job.' : 'This job has ended, so the chat is closed.');
        }

        $id = DB::transaction(function () use ($shipmentId, $sides, $userId, $body) {
            $conv = $this->conversation($shipmentId);
            foreach (array_filter([(int) $sides->customer_id, $sides->driver_id]) as $u) {
                DB::table('conversation_participants')->insertOrIgnore(['conversation_id' => $conv, 'user_id' => $u]);
            }

            return DB::table('messages')->insertGetId([
                'conversation_id' => $conv, 'sender_id' => $userId, 'kind' => 'text', 'body' => $body,
                // Phone numbers typed into the chat are allowed, but flagged so support can see them in a dispute.
                'moderation_flag' => (bool) preg_match('/(\+?\d[\s-]?){10,}/', $body), 'created_at' => now(),
            ]);
        });

        $to = $role === 'customer' ? $sides->driver_id : (int) $sides->customer_id;
        $from = $role === 'customer' ? ($sides->customer_name ?: 'Customer') : ($sides->driver_name ? "{$sides->driver_name} (your rider)" : 'Your rider');
        $this->push->toUser($to, $from, Str::limit($body, 120), ['event' => 'chat.message', 'shipment' => $sides->public_id]);

        return ['id' => $id];
    }

    /** What the provider's team sees on the job page, for support and disputes. */
    public function forStaff(int $shipmentId): array
    {
        $customer = (int) DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $shipmentId)->value('o.customer_id');

        return DB::table('messages as m')->join('conversations as c', 'c.id', '=', 'm.conversation_id')->leftJoin('users as u', 'u.id', '=', 'm.sender_id')
            ->where('c.shipment_id', $shipmentId)->where('c.type', 'customer_driver')
            ->orderBy('m.id')->get(['m.body', 'm.created_at', 'm.moderation_flag', 'm.sender_id', 'u.name'])
            ->map(fn ($m) => ['name' => $m->name, 'side' => (int) $m->sender_id === $customer ? 'Customer' : 'Rider', 'body' => $m->body, 'at' => $m->created_at, 'flagged' => (bool) $m->moderation_flag])->all();
    }

    private function conversation(int $shipmentId): int
    {
        DB::table('conversations')->insertOrIgnore([
            'public_id' => (string) Str::ulid(), 'shipment_id' => $shipmentId, 'type' => 'customer_driver', 'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return (int) DB::table('conversations')->where(['shipment_id' => $shipmentId, 'type' => 'customer_driver'])->value('id');
    }

    private static function firstName(?string $name): string
    {
        return (string) Str::of((string) $name)->trim()->explode(' ')->first();
    }
}
