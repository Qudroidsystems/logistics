<?php

namespace App\Modules\Support;

use App\Modules\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Support inbox: a customer asks for help (about an order or in general) from the website or the app; staff answer
 * under Operations > Support. Internal notes are never shown to the customer. Statuses:
 * new → open (staff working) → pending_customer (waiting for the customer) → resolved → closed. A customer reply reopens.
 */
class SupportService
{
    public const CATEGORIES = [
        'late' => 'My delivery is late', 'damaged' => 'Something arrived damaged', 'missing' => 'Something is missing',
        'address' => 'Change the address or time', 'payment' => 'Payment or charge', 'refund' => 'Refund',
        'driver' => 'About the driver', 'account' => 'My account', 'other' => 'Something else',
    ];

    public const STATUSES = ['new' => 'New', 'open' => 'Open', 'pending_customer' => 'Waiting for customer', 'resolved' => 'Resolved', 'closed' => 'Closed'];

    /** Answered within this many hours (first reply), by priority. */
    private const FIRST_REPLY_HOURS = ['high' => 1, 'normal' => 4];

    public function __construct(private NotificationService $notifications) {}

    /** @return string the ticket's public id */
    public function open(int $userId, string $category, string $body, ?string $shipmentPublicId = null, string $channel = 'web'): string
    {
        if (! isset(self::CATEGORIES[$category])) {
            throw new RuntimeException('Choose what it is about.');
        }
        $body = trim($body);
        if (mb_strlen($body) < 5) {
            throw new RuntimeException('Tell us a little more so we can help.');
        }
        $shipment = null;
        if ($shipmentPublicId) {
            $shipment = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.public_id', $shipmentPublicId)
                ->where('o.customer_id', $userId)->first(['s.id', 's.operator_id', 'o.order_number']);
            if (! $shipment) {
                throw new RuntimeException('That order was not found on your account.');
            }
        }
        if (DB::table('support_tickets')->where('requester_id', $userId)->where('created_at', '>', now()->subHour())->count() >= 10) {
            throw new RuntimeException('You have opened a lot of requests in the last hour. Reply to an open one instead.');
        }
        $priority = in_array($category, ['damaged', 'missing', 'payment', 'late'], true) ? 'high' : 'normal';
        $pid = (string) Str::ulid();
        $id = DB::transaction(function () use ($userId, $category, $body, $shipment, $channel, $priority, $pid) {
            $id = DB::table('support_tickets')->insertGetId([
                'public_id' => $pid, 'operator_id' => $shipment->operator_id ?? 1, 'number' => 'TK'.now()->format('ymd').strtoupper(Str::random(5)),
                'requester_id' => $userId, 'channel' => in_array($channel, ['app', 'web', 'whatsapp', 'phone', 'email'], true) ? $channel : 'web',
                'shipment_id' => $shipment->id ?? null, 'category' => $category, 'priority' => $priority, 'status' => 'new',
                'subject' => self::CATEGORIES[$category].($shipment ? ' · '.$shipment->order_number : ''),
                'sla_first_response_due' => now()->addHours(self::FIRST_REPLY_HOURS[$priority]), 'sla_resolve_due' => now()->addHours(48),
                'last_message_at' => now(), 'staff_unread' => true, 'customer_unread' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('ticket_messages')->insert(['ticket_id' => $id, 'sender_id' => $userId, 'internal' => false, 'body' => mb_substr($body, 0, 4000), 'created_at' => now()]);

            return $id;
        });
        $t = DB::table('support_tickets')->where('id', $id)->first();
        $this->tellStaff($t, 'support.new', ['number' => $t->number, 'subject' => $t->subject]);

        return $pid;
    }

    /** The customer's own tickets, newest activity first. */
    public function mine(int $userId): array
    {
        return DB::table('support_tickets')->where('requester_id', $userId)->orderByDesc('last_message_at')->limit(100)
            ->get(['public_id', 'number', 'subject', 'status', 'customer_unread', 'last_message_at', 'created_at'])
            ->map(fn ($t) => (array) $t + ['status_label' => self::customerStatus($t->status)])->all();
    }

    public static function customerStatus(string $s): string
    {
        return match ($s) {
            'new', 'open' => 'We are looking into it',
            'pending_customer' => 'Waiting for your reply',
            'resolved', 'closed' => 'Resolved',
            default => ucfirst($s),
        };
    }

    /** A ticket with its messages. $forCustomer hides internal notes and checks ownership. */
    public function thread(string $publicId, ?int $customerId = null): array
    {
        $t = DB::table('support_tickets as t')->join('users as u', 'u.id', '=', 't.requester_id')
            ->leftJoin('users as a', 'a.id', '=', 't.assignee_id')->leftJoin('shipments as s', 's.id', '=', 't.shipment_id')
            ->leftJoin('orders as o', 'o.id', '=', 's.order_id')
            ->where('t.public_id', $publicId)->when($customerId !== null, fn ($q) => $q->where('t.requester_id', $customerId))
            ->first(['t.*', 'u.name as customer', 'u.email as customer_email', 'u.phone_number as customer_phone', 'a.name as assignee',
                's.public_id as shipment', 's.tracking_code', 's.status as shipment_status', 'o.order_number']);
        if (! $t) {
            throw new RuntimeException('That request was not found.');
        }
        $messages = DB::table('ticket_messages as m')->leftJoin('users as u', 'u.id', '=', 'm.sender_id')->where('m.ticket_id', $t->id)
            ->when($customerId !== null, fn ($q) => $q->where('m.internal', false))->orderBy('m.id')
            ->get(['m.id', 'm.body', 'm.internal', 'm.created_at', 'm.sender_id', 'u.name as sender']);
        if ($customerId !== null && $t->customer_unread) {
            DB::table('support_tickets')->where('id', $t->id)->update(['customer_unread' => false]);
        }

        return ['ticket' => $t, 'messages' => $messages->map(fn ($m) => (array) $m + ['mine' => (int) $m->sender_id === (int) $t->requester_id])->all()];
    }

    public function customerReply(string $publicId, int $userId, string $body): void
    {
        $t = DB::table('support_tickets')->where('public_id', $publicId)->where('requester_id', $userId)->first();
        if (! $t) {
            throw new RuntimeException('That request was not found.');
        }
        $this->add($t, $userId, $body, false);
        DB::table('support_tickets')->where('id', $t->id)->update([
            'status' => in_array($t->status, ['new'], true) ? 'new' : 'open', 'staff_unread' => true, 'resolved_at' => null, 'last_message_at' => now(), 'updated_at' => now(),
        ]);
        $this->tellStaff($t, 'support.customer_replied', ['number' => $t->number, 'subject' => (string) $t->subject]);
    }

    /** Staff answer ($internal = a note only staff see). */
    public function staffReply(string $publicId, int $staffId, string $body, bool $internal = false, ?string $status = null): void
    {
        $t = DB::table('support_tickets')->where('public_id', $publicId)->first() ?? throw new RuntimeException('That request was not found.');
        $this->add($t, $staffId, $body, $internal);
        $update = ['assignee_id' => $t->assignee_id ?? $staffId, 'staff_unread' => false, 'updated_at' => now()];
        if (! $internal) {
            $update += ['status' => $status && isset(self::STATUSES[$status]) ? $status : 'pending_customer', 'customer_unread' => true, 'last_message_at' => now(),
                'first_responded_at' => $t->first_responded_at ?? now()];
            if (in_array($update['status'], ['resolved', 'closed'], true)) {
                $update['resolved_at'] = now();
            }
        }
        DB::table('support_tickets')->where('id', $t->id)->update($update);
        if (! $internal) {
            $this->notifications->notify((int) $t->requester_id, 'support.reply', ['number' => $t->number, 'subject' => (string) $t->subject],
                route('account.help.show', $t->public_id, false));
        }
    }

    public function setStatus(string $publicId, string $status): void
    {
        if (! isset(self::STATUSES[$status])) {
            throw new RuntimeException('Unknown status.');
        }
        DB::table('support_tickets')->where('public_id', $publicId)->update([
            'status' => $status, 'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null, 'staff_unread' => false, 'updated_at' => now(),
        ]);
    }

    public function assign(string $publicId, ?int $staffId): void
    {
        DB::table('support_tickets')->where('public_id', $publicId)->update(['assignee_id' => $staffId, 'status' => DB::raw("CASE WHEN status = 'new' THEN 'open' ELSE status END"), 'updated_at' => now()]);
    }

    /** The staff inbox. $view: open | mine | unassigned | waiting | resolved | all */
    public function inbox(string $view, int $staffId, ?string $search = null)
    {
        $q = DB::table('support_tickets as t')->join('users as u', 'u.id', '=', 't.requester_id')->leftJoin('users as a', 'a.id', '=', 't.assignee_id')
            ->select('t.public_id', 't.number', 't.subject', 't.status', 't.priority', 't.channel', 't.staff_unread', 't.last_message_at', 't.created_at',
                't.sla_first_response_due', 't.first_responded_at', 'u.name as customer', 'a.name as assignee');
        match ($view) {
            'mine' => $q->where('t.assignee_id', $staffId)->whereIn('t.status', ['new', 'open', 'pending_customer']),
            'unassigned' => $q->whereNull('t.assignee_id')->whereIn('t.status', ['new', 'open']),
            'waiting' => $q->where('t.status', 'pending_customer'),
            'resolved' => $q->whereIn('t.status', ['resolved', 'closed']),
            'all' => $q,
            default => $q->whereIn('t.status', ['new', 'open']),
        };
        if ($search) {
            $s = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $q->where(fn ($w) => $w->where('t.number', 'ilike', $s)->orWhere('t.subject', 'ilike', $s)->orWhere('u.name', 'ilike', $s)->orWhere('u.email', 'ilike', $s));
        }

        return $q->orderByRaw("t.priority = 'high' DESC")->orderByDesc('t.staff_unread')->orderBy('t.last_message_at')->limit(200)->get();
    }

    public function counts(int $staffId): array
    {
        $base = fn () => DB::table('support_tickets');

        return [
            'open' => $base()->whereIn('status', ['new', 'open'])->count(),
            'mine' => $base()->where('assignee_id', $staffId)->whereIn('status', ['new', 'open', 'pending_customer'])->count(),
            'unassigned' => $base()->whereNull('assignee_id')->whereIn('status', ['new', 'open'])->count(),
            'waiting' => $base()->where('status', 'pending_customer')->count(),
            'overdue' => $base()->whereIn('status', ['new', 'open'])->whereNull('first_responded_at')->where('sla_first_response_due', '<', now())->count(),
        ];
    }

    private function add(object $t, int $senderId, string $body, bool $internal): void
    {
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Write a message first.');
        }
        DB::table('ticket_messages')->insert(['ticket_id' => $t->id, 'sender_id' => $senderId, 'internal' => $internal, 'body' => mb_substr($body, 0, 4000), 'created_at' => now()]);
    }

    private function tellStaff(object $t, string $event, array $vars): void
    {
        try {
            $ids = $t->assignee_id ? collect([(int) $t->assignee_id])
                : \App\Models\User::permission('Reply support ticket')->where('is_disabled', false)->limit(50)->pluck('id');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return; // permissions not seeded yet
        }
        foreach ($ids as $id) {
            $this->notifications->notify((int) $id, $event, $vars, '/ops/support/'.$t->public_id);
        }
    }
}
