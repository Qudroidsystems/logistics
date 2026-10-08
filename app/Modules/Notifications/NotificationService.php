<?php

namespace App\Modules\Notifications;

use App\Jobs\SendNotificationEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * In-app notifications (the standard Laravel `notifications` table, so `$user->notifications` works) plus a queued email
 * for the messages that need one. Sending is best effort: a notification problem is logged and never breaks the money or
 * delivery step that triggered it.
 *
 * SMS goes through Notifications\Sms\SmsService (Termii; set SMS_DRIVER=termii). Push goes through Notifications\Push\PushService (Firebase; set PUSH_DRIVER=fcm).
 */
class NotificationService
{
    /** Who on a provider's team hears about operational news. */
    public const PROVIDER_ROLES = ['owner', 'admin', 'finance', 'dispatcher'];

    /** @param array<string,scalar|null> $vars */
    public function notify(int $userId, string $event, array $vars = [], ?string $url = null): void
    {
        $tpl = NotificationTemplates::ALL[$event] ?? null;
        if (! $tpl) {
            Log::warning("Unknown notification event {$event}");

            return;
        }
        try {
            // Nested transaction = savepoint, so a failed insert cannot poison the caller's Postgres transaction.
            DB::transaction(function () use ($userId, $event, $vars, $url, $tpl) {
                [$inApp, $emailOk] = NotificationPreferences::channels($userId, $event);
                $title = self::render($tpl['title'], $vars);
                $body = self::render($tpl['body'], $vars);
                if ($inApp) {
                    app(Push\PushService::class)->toUser($userId, $title, $body, ['event' => $event, 'url' => $url]);
                    DB::table('notifications')->insert([
                        'id' => (string) Str::uuid(), 'type' => $event, 'notifiable_type' => User::class, 'notifiable_id' => $userId,
                        'data' => json_encode(['event' => $event, 'title' => $title, 'body' => $body, 'url' => $url]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                if ($emailOk && ! empty($tpl['email']) && DB::table('users')->where('id', $userId)->whereNotNull('email')->exists()) {
                    $deliveryId = DB::table('notification_deliveries')->insertGetId([
                        'user_id' => $userId, 'template_key' => $event, 'channel' => 'email', 'status' => 'queued', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    SendNotificationEmail::dispatch($deliveryId, $title, $body)->afterCommit();
                }
            });
        } catch (Throwable $e) {
            Log::warning("Notification {$event} to user {$userId} failed: {$e->getMessage()}");
        }
    }

    /** Everyone on a provider's team with one of the roles. */
    public function notifyOperator(int $operatorId, string $event, array $vars = [], ?string $url = null, array $roles = self::PROVIDER_ROLES): void
    {
        try {
            $ids = DB::table('operator_members')->where('operator_id', $operatorId)->where('status', 'active')->whereIn('role', $roles)->pluck('user_id');
        } catch (Throwable $e) {
            Log::warning("Notification {$event} to operator {$operatorId} failed: {$e->getMessage()}");

            return;
        }
        foreach ($ids as $id) {
            $this->notify((int) $id, $event, $vars, $url);
        }
    }

    /**
     * Called from ShipmentEvents::record, the single place the delivery lifecycle is recorded.
     * Orders placed through a merchant's API are skipped for the customer: the merchant tells its own customers (via webhooks).
     */
    public function shipmentEvent(int $shipmentId, string $type, array $meta = []): void
    {
        $map = self::SHIPMENT_MAP[$type] ?? null;
        if (! $map) {
            return;
        }
        try {
            $row = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $shipmentId)
                ->first(['s.public_id', 's.operator_id', 'o.customer_id', 'o.merchant_id', 'o.order_number']);
        } catch (Throwable $e) {
            Log::warning("Shipment notification lookup failed: {$e->getMessage()}");

            return;
        }
        if (! $row) {
            return;
        }
        $vars = ['order' => $row->order_number];
        if (! empty($map['customer']) && ! $row->merchant_id) {
            $this->notify((int) $row->customer_id, $map['customer'], $vars, "/orders/{$row->public_id}");
        }
        if (! empty($map['provider'])) {
            $this->notifyOperator((int) $row->operator_id, $map['provider'], $vars, "/provider/jobs/{$row->public_id}");
        }
        if ($type === 'assigned') {
            $this->textRecipient($shipmentId, (string) $row->public_id, (string) $row->order_number);
        }
    }

    /**
     * When a rider is assigned, text the drop-off contact the 4-digit delivery code and their tracking link.
     * The rider must be given this code at hand-over, so it goes only to the person receiving the parcel.
     */
    private function textRecipient(int $shipmentId, string $publicId, string $order): void
    {
        try {
            $phone = DB::table('shipment_stops')->where('shipment_id', $shipmentId)->where('type', 'dropoff')->orderByDesc('seq')->value('contact_phone');
            if (! $phone) {
                return;
            }
            $code = app(\App\Modules\Tracking\TrackingService::class)->deliveryCode($publicId);
            $token = DB::table('tracking_links')->where('shipment_id', $shipmentId)->where('audience', 'recipient')->whereNull('revoked_at')->orderByDesc('id')->value('token');
            $link = $token ? ' Track: '.url('/track/'.$token) : '';
            app(\App\Modules\Notifications\Sms\SmsService::class)->toNumber(
                $phone, "A rider is bringing delivery {$order}. Give the rider this code when you receive it: {$code}.{$link}", null, 'delivery.recipient_code'
            );
        } catch (Throwable $e) {
            Log::warning("Recipient text for shipment {$shipmentId} failed: {$e->getMessage()}");
        }
    }

    /** shipment event type => template for each side. */
    private const SHIPMENT_MAP = [
        'created' => ['provider' => 'delivery.created_provider'],
        'assigned' => ['customer' => 'delivery.assigned'],
        'arrived' => ['customer' => 'delivery.arrived'],
        'picked_up' => ['customer' => 'delivery.picked_up'],
        'delivered' => ['customer' => 'delivery.delivered', 'provider' => 'delivery.delivered_provider'],
        'confirmed' => ['provider' => 'delivery.confirmed_provider'],
        'disputed' => ['provider' => 'delivery.disputed_provider'],
        'dispute_resolved' => ['customer' => 'delivery.dispute_resolved_customer', 'provider' => 'delivery.dispute_resolved_provider'],
        'cancelled' => ['customer' => 'delivery.cancelled_customer', 'provider' => 'delivery.cancelled_provider'],
        'delivery_failed' => ['customer' => 'delivery.failed_customer', 'provider' => 'delivery.failed_provider'],
        'returned' => ['customer' => 'delivery.returned_customer', 'provider' => 'delivery.returned_provider'],
    ];

    // ---------------------------------------------------------------- helpers

    /** Pure: fills {placeholders}; a missing value leaves the sentence readable instead of showing the braces. */
    public static function render(string $template, array $vars): string
    {
        $out = preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? ''), $template);

        return trim(preg_replace('/\s{2,}/', ' ', $out));
    }

    /** Kobo to a display amount: 1250000 => "₦12,500", 1250050 => "₦12,500.50". */
    public static function naira(int $kobo): string
    {
        return '₦'.number_format($kobo / 100, $kobo % 100 === 0 ? 0 : 2);
    }
}
