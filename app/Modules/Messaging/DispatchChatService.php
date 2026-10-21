<?php

namespace App\Modules\Messaging;

use App\Modules\Dispatch\DriverService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Notifications\Push\PushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Chat between the rider on a job and the provider's dispatch desk (owner, admin, dispatcher). One conversation per
 * shipment (type `dispatcher_driver`), separate from the customer chat, so the customer never sees it.
 * Open while a rider holds the job; afterwards the history stays readable on the job page.
 *
 * Returns the same thread shape as ChatService::thread, so the driver app's chat screen shows it unchanged.
 */
class DispatchChatService
{
    public const TYPE = 'dispatcher_driver';

    public const MAX_LENGTH = 1000;

    /** Team roles that answer the rider. */
    public const DESK_ROLES = ['owner', 'admin', 'dispatcher'];

    public const QUICK = [
        'driver' => ['Customer is not answering', 'Address is wrong', "I'm stuck in traffic", 'Please call me'],
        'dispatch' => ['Please call the customer', 'Wait 10 more minutes', 'Bring it back to the office', 'OK, thanks'],
    ];

    public function __construct(private PushService $push, private NotificationService $notifications) {}

    /** "driver" (the rider holding the job now), "dispatch" (a desk member of the job's provider) or null. */
    public function role(int $shipmentId, int $userId): ?string
    {
        $s = DB::table('shipments')->where('id', $shipmentId)->first(['operator_id']);
        if (! $s) {
            return null;
        }
        if ($this->riderUserId($shipmentId) === $userId) {
            return 'driver';
        }
        $desk = DB::table('operator_members')->where(['operator_id' => $s->operator_id, 'user_id' => $userId, 'status' => 'active'])
            ->whereIn('role', self::DESK_ROLES)->exists();

        return $desk ? 'dispatch' : null;
    }

    public function thread(int $shipmentId, int $userId, int $after = 0): ?array
    {
        $role = $this->role($shipmentId, $userId);
        if (! $role) {
            return null;
        }
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $shipmentId)->first(['s.public_id', 'o.order_number']);
        $rider = $this->riderUserId($shipmentId);
        $open = $rider !== null;
        $conv = $this->conversationId($shipmentId);
        $rows = $conv ? DB::table('messages as m')->leftJoin('users as u', 'u.id', '=', 'm.sender_id')->where('m.conversation_id', $conv)
            ->where('m.id', '>', $after)->orderBy('m.id')->limit(200)->get(['m.*', 'u.name as sender_name']) : collect();
        if ($conv && $rows->isNotEmpty()) {
            // The rider reads the desk's messages; any desk member reading marks the rider's messages read.
            DB::table('messages')->where('conversation_id', $conv)->whereNull('read_at')
                ->when($role === 'driver', fn ($q) => $q->where('sender_id', '<>', $userId))
                ->when($role === 'dispatch', fn ($q) => $q->where('sender_id', $rider ?? 0))
                ->update(['read_at' => now()]);
        }
        $riderName = $rider ? (string) DB::table('users')->where('id', $rider)->value('name') : null;

        return [
            'shipment' => $s->public_id, 'order_number' => $s->order_number, 'open' => $open, 'me' => $role,
            'other' => $role === 'driver' ? 'Dispatch' : ($riderName ? "{$riderName} (rider)" : 'Rider'),
            'closed_note' => $open ? null : 'No rider holds this job now, so the dispatch chat is closed.',
            'quick' => $open ? self::QUICK[$role] : [],
            'calls' => false,
            'messages' => $rows->map(fn ($m) => [
                'id' => (int) $m->id, 'mine' => (int) $m->sender_id === $userId, 'kind' => $m->kind, 'body' => $m->body,
                'at' => $m->created_at, 'read' => $m->read_at !== null,
                // The desk is several people: show who said what.
                'sender' => $role === 'dispatch' || (int) $m->sender_id !== $userId ? Str::of((string) $m->sender_name)->trim()->explode(' ')->first() : null,
            ])->all(),
        ];
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
        $role = $this->role($shipmentId, $userId);
        if (! $role) {
            throw new RuntimeException('This chat is not yours.');
        }
        $rider = $this->riderUserId($shipmentId);
        if ($rider === null) {
            throw new RuntimeException('No rider holds this job now, so the dispatch chat is closed.');
        }
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $shipmentId)->first(['s.public_id', 's.operator_id', 'o.order_number']);

        $id = DB::transaction(function () use ($shipmentId, $userId, $rider, $body) {
            DB::table('conversations')->insertOrIgnore([
                'public_id' => (string) Str::ulid(), 'shipment_id' => $shipmentId, 'type' => self::TYPE, 'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $conv = $this->conversationId($shipmentId);
            foreach (array_unique([$rider, $userId]) as $u) {
                DB::table('conversation_participants')->insertOrIgnore(['conversation_id' => $conv, 'user_id' => $u]);
            }

            return DB::table('messages')->insertGetId([
                'conversation_id' => $conv, 'sender_id' => $userId, 'kind' => 'text', 'body' => $body, 'moderation_flag' => false, 'created_at' => now(),
            ]);
        });

        $name = Str::of((string) DB::table('users')->where('id', $userId)->value('name'))->trim()->explode(' ')->first();
        if ($role === 'dispatch') {
            $this->push->toUser($rider, 'Dispatch', Str::limit($body, 120), ['event' => 'dispatch.message', 'shipment' => $s->public_id]);
        } else {
            $this->notifications->notifyOperator((int) $s->operator_id, 'delivery.dispatch_message', ['name' => $name, 'order' => $s->order_number, 'message' => Str::limit($body, 120)],
                "/provider/jobs/{$s->public_id}", self::DESK_ROLES);
        }

        return ['id' => $id];
    }

    /** Messages from the desk the rider has not read yet (for the job screen badge). */
    public function unreadForRider(int $shipmentId, int $riderUserId): int
    {
        $conv = $this->conversationId($shipmentId);

        return $conv ? DB::table('messages')->where('conversation_id', $conv)->where('sender_id', '<>', $riderUserId)->whereNull('read_at')->count() : 0;
    }

    private function riderUserId(int $shipmentId): ?int
    {
        $id = DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')
            ->where('a.shipment_id', $shipmentId)->whereIn('a.status', DriverService::LIVE)->value('d.user_id');

        return $id ? (int) $id : null;
    }

    private function conversationId(int $shipmentId): ?int
    {
        $id = DB::table('conversations')->where(['shipment_id' => $shipmentId, 'type' => self::TYPE])->value('id');

        return $id ? (int) $id : null;
    }
}
