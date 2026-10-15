<?php

namespace App\Modules\Eta;

use App\Modules\Notifications\NotificationService;
use App\Modules\Notifications\Sms\SmsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * "Your rider is about 5 minutes away", sent once per stop at the points the provider chose.
 *
 *   - kinds: eta_<N> (N minutes away), near (within the provider's distance), arrived, delay_<n> (running late)
 *   - each kind fires at most once per stop (stop_alerts is unique on stop + kind)
 *   - when several are due at once (the first estimate is already 3 minutes), only the closest goes out and the
 *     others are recorded as skipped, so nobody gets "15 minutes away" and "5 minutes away" in the same second
 *   - the job giver gets a push in their app; the person receiving the parcel gets a text, unless the provider
 *     turned texts off or it is the job giver's own number
 *   - only while the rider is actually travelling to that stop
 */
class ArrivalAlerts
{
    public const DEFAULTS = ['eta_minutes' => [15, 5], 'near_m' => 500, 'arrived' => true, 'delay_minutes' => 10, 'sms' => true];
    public const ETA_CHOICES = [30, 20, 15, 10, 5, 2];

    public function __construct(private EtaService $eta, private NotificationService $notifications) {}

    /** @return array{eta_minutes:int[], near_m:?int, arrived:bool, delay_minutes:?int, sms:bool, saved:bool} */
    public function settings(int $operatorId): array
    {
        $row = DB::table('alert_settings')->where('operator_id', $operatorId)->first();
        if (! $row) {
            return self::DEFAULTS + ['saved' => false];
        }

        return ['eta_minutes' => self::minutes(json_decode($row->eta_minutes, true) ?: []), 'near_m' => $row->near_m ? (int) $row->near_m : null,
            'arrived' => (bool) $row->arrived, 'delay_minutes' => $row->delay_minutes ? (int) $row->delay_minutes : null, 'sms' => (bool) $row->sms, 'saved' => true];
    }

    public function save(int $operatorId, array $etaMinutes, ?int $nearM, bool $arrived, ?int $delayMinutes, bool $sms, int $userId): void
    {
        DB::table('alert_settings')->upsert([[
            'operator_id' => $operatorId, 'eta_minutes' => json_encode(self::minutes($etaMinutes)),
            'near_m' => $nearM ? max(100, min(2000, $nearM)) : null, 'arrived' => $arrived,
            'delay_minutes' => $delayMinutes ? max(5, min(60, $delayMinutes)) : null, 'sms' => $sms,
            'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]], ['operator_id'], ['eta_minutes', 'near_m', 'arrived', 'delay_minutes', 'sms', 'updated_by', 'updated_at']);
    }

    /** After each batch of rider pings: works out the estimate and sends whatever has become due. Never throws. */
    public function check(int $shipmentId): void
    {
        try {
            // A savepoint, so a failure here cannot poison the caller's transaction.
            DB::transaction(fn () => $this->checkNow($shipmentId));
        } catch (Throwable $ex) {
            Log::warning("Arrival alerts for shipment {$shipmentId} failed: {$ex->getMessage()}");
        }
    }

    private function checkNow(int $shipmentId): void
    {
        $e = $this->eta->forShipment($shipmentId);
        if (! $e || ! $e['travelling']) {
            return;
        }
        $ship = $this->shipment($shipmentId);
        $cfg = $this->settings((int) $ship->operator_id);
        $done = DB::table('stop_alerts')->where('stop_id', $e['stop_id'])->pluck('kind')->all();

        $pick = self::due($cfg['eta_minutes'], $cfg['near_m'], $e['minutes'], $e['distance_m'], $done);
        foreach ($pick['skip'] as $kind) {
            $this->record($e, $kind, 'skipped', null);
        }
        if ($pick['send'] && $this->record($e, $pick['send'], 'sent', null)) {
            $when = $pick['send'] === 'near' ? "under {$cfg['near_m']} m away" : "{$e['label']} away";
            $this->send($ship, $e, $pick['send'], 'delivery.approaching', ['when' => $when],
                "The rider with delivery {$ship->order_number} is {$when}.");
        }

        if ($cfg['delay_minutes'] && self::delayed(CarbonImmutable::parse($e['due_at']), CarbonImmutable::now()->addMinutes($e['minutes']), $cfg['delay_minutes'])) {
            $n = 1 + DB::table('stop_alerts')->where('stop_id', $e['stop_id'])->where('kind', 'like', 'delay_%')->count();
            $this->eta->rebase($e['stop_id'], CarbonImmutable::now()->addMinutes($e['minutes']));
            if ($this->record($e, "delay_{$n}", 'sent', null)) {
                $this->send($ship, $e, "delay_{$n}", 'delivery.delayed', ['when' => $e['label']],
                    "The rider with delivery {$ship->order_number} is running late. Now expected in {$e['label']}.");
            }
        }
    }

    /** The rider reached the drop-off (geofence): the receiver is told by text. The job giver already gets the "arrived" push. */
    public function arrived(int $shipmentId, int $stopId, string $stopType): void
    {
        if ($stopType !== 'dropoff') {
            return;
        }
        try {
            DB::transaction(fn () => $this->arrivedNow($shipmentId, $stopId, $stopType));
        } catch (Throwable $ex) {
            Log::warning("Arrived text for shipment {$shipmentId} failed: {$ex->getMessage()}");
        }
    }

    private function arrivedNow(int $shipmentId, int $stopId, string $stopType): void
    {
        $ship = $this->shipment($shipmentId);
        if (! $this->settings((int) $ship->operator_id)['arrived']) {
            return;
        }
        $e = ['stop_id' => $stopId, 'stop_type' => $stopType, 'shipment_id' => $shipmentId, 'minutes' => 0, 'distance_m' => 0];
        if ($this->record($e, 'arrived', 'sent', null)) {
            $sent = $this->text($ship, $stopId, "The rider with delivery {$ship->order_number} has arrived.", 'delivery.recipient_arrived');
            DB::table('stop_alerts')->where('stop_id', $stopId)->where('kind', 'arrived')->update(['channels' => json_encode($sent ? ['sms'] : [])]);
        }
    }

    /** What the provider sees on the job page. */
    public function forShipment(int $shipmentId): array
    {
        return DB::table('stop_alerts as a')->join('shipment_stops as st', 'st.id', '=', 'a.stop_id')->where('a.shipment_id', $shipmentId)
            ->orderBy('a.id')->get(['a.kind', 'a.status', 'a.channels', 'a.created_at', 'st.type as stop_type'])
            ->map(fn ($r) => ['kind' => $r->kind, 'label' => self::describe($r->kind), 'status' => $r->status, 'stop_type' => $r->stop_type,
                'channels' => json_decode((string) $r->channels, true) ?: [], 'at' => $r->created_at])->all();
    }

    // ---------------------------------------------------------------- pure helpers (unit tested)

    /**
     * Which alert to send now, given the estimate and what already went out. The closest due alert is sent; the others
     * that are also due are skipped, because they would only repeat it.
     *
     * @param  int[]  $etaMinutes
     * @param  string[]  $done
     * @return array{send:?string, skip:string[]}
     */
    public static function due(array $etaMinutes, ?int $nearM, int $minutes, int $distanceM, array $done): array
    {
        $due = [];
        if ($nearM && $distanceM <= $nearM) {
            $due[] = 'near';
        }
        $mins = self::minutes($etaMinutes);
        sort($mins);
        foreach ($mins as $t) {
            if ($minutes <= $t) {
                $due[] = "eta_{$t}";
            }
        }
        $due = array_values(array_diff($due, $done));

        return ['send' => $due[0] ?? null, 'skip' => array_slice($due, 1)];
    }

    /** Running late: the new expected time is at least the margin past the time we said. */
    public static function delayed(CarbonImmutable $dueAt, CarbonImmutable $newDue, int $marginMinutes): bool
    {
        return $newDue->getTimestamp() - $dueAt->getTimestamp() >= $marginMinutes * 60;
    }

    /** @return int[] valid choices, largest first, no repeats */
    public static function minutes(array $m): array
    {
        $m = array_values(array_unique(array_filter(array_map('intval', $m), fn ($x) => in_array($x, self::ETA_CHOICES, true))));
        rsort($m);

        return $m;
    }

    public static function describe(string $kind): string
    {
        return match (true) {
            $kind === 'near' => 'Rider is close',
            $kind === 'arrived' => 'Rider arrived',
            str_starts_with($kind, 'eta_') => 'About '.substr($kind, 4).' min away',
            str_starts_with($kind, 'delay_') => 'Running late',
            default => $kind,
        };
    }

    // ---------------------------------------------------------------- sending

    private function shipment(int $shipmentId): object
    {
        return DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.id', $shipmentId)
            ->first(['s.id', 's.public_id', 's.operator_id', 'o.customer_id', 'o.merchant_id', 'o.order_number']);
    }

    /** Claims the alert; false when it was already claimed (another batch of pings got there first). */
    private function record(array $e, string $kind, string $status, ?array $channels): bool
    {
        return DB::table('stop_alerts')->insertOrIgnore([
            'stop_id' => $e['stop_id'], 'shipment_id' => $e['shipment_id'] ?? DB::table('shipment_stops')->where('id', $e['stop_id'])->value('shipment_id'),
            'kind' => $kind, 'status' => $status, 'eta_s' => isset($e['minutes']) ? $e['minutes'] * 60 : null, 'distance_m' => $e['distance_m'] ?? null,
            'channels' => $channels ? json_encode($channels) : null, 'created_at' => now(),
        ]) === 1;
    }

    private function send(object $ship, array $e, string $kind, string $event, array $vars, string $sms): void
    {
        $place = $e['stop_type'] === 'pickup' ? 'the pickup' : 'the drop-off';
        $channels = [];
        if (! $ship->merchant_id) {
            $this->notifications->notify((int) $ship->customer_id, $event, $vars + ['order' => $ship->order_number, 'place' => $place], "/orders/{$ship->public_id}");
            $channels[] = 'push';
        }
        if ($e['stop_type'] === 'dropoff' && $this->text($ship, $e['stop_id'], $sms, "{$event}_recipient")) {
            $channels[] = 'sms';
        }
        DB::table('stop_alerts')->where('stop_id', $e['stop_id'])->where('kind', $kind)->update(['channels' => json_encode($channels)]);
    }

    /** Texts the drop-off contact, with their tracking link so they can tell the rider they need more time. */
    private function text(object $ship, int $stopId, string $message, string $templateKey): bool
    {
        if (! $this->settings((int) $ship->operator_id)['sms']) {
            return false;
        }
        $phone = DB::table('shipment_stops')->where('id', $stopId)->value('contact_phone');
        $own = DB::table('users')->where('id', $ship->customer_id)->value('phone_number');
        if (! $phone || ($own && SmsService::normalize($own) === SmsService::normalize($phone))) {
            return false;
        }
        $token = DB::table('tracking_links')->where('shipment_id', $ship->id)->where('audience', 'recipient')->whereNull('revoked_at')->orderByDesc('id')->value('token');

        return app(SmsService::class)->toNumber($phone, $message.($token ? ' Reply or follow: '.url('/track/'.$token) : ''), null, $templateKey);
    }
}
