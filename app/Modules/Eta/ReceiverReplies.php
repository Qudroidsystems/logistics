<?php

namespace App\Modules\Eta;

use App\Modules\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The person receiving the parcel tells the rider whether they are ready, from the tracking link or the app.
 * The reply is kept on the drop-off stop, shown on the rider's job screen and pushed to the rider.
 */
class ReceiverReplies
{
    public const OPTIONS = [
        'ready' => "I'm ready",
        'need_time' => 'I need 10 more minutes',
        'call_me' => 'Call me when you arrive',
        'someone_else' => 'Someone else will collect it',
    ];

    public function __construct(private NotificationService $notifications) {}

    /** @return array{reply:string, label:string, note:?string} */
    public function reply(int $shipmentId, string $reply, ?string $note = null): array
    {
        if (! isset(self::OPTIONS[$reply])) {
            throw new RuntimeException('Choose one of the replies.');
        }
        $note = trim(preg_replace('/\s+/', ' ', (string) $note)) ?: null;
        $note = $note ? mb_substr($note, 0, 140) : null;
        $stop = DB::table('shipment_stops')->where('shipment_id', $shipmentId)->where('type', 'dropoff')
            ->whereNotIn('status', ['completed', 'failed', 'skipped'])->orderBy('seq')->first(['id']);
        $status = DB::table('shipments')->where('id', $shipmentId)->value('status');
        if (! $stop || ! in_array($status, EtaService::MOVING, true)) {
            throw new RuntimeException('This delivery is not on its way, so there is nobody to tell yet.');
        }
        DB::table('shipment_stops')->where('id', $stop->id)->update(['readiness' => $reply, 'readiness_note' => $note, 'readiness_at' => now(), 'updated_at' => now()]);

        $row = DB::table('assignments as a')->join('driver_profiles as p', 'p.id', '=', 'a.driver_profile_id')
            ->join('shipments as s', 's.id', '=', 'a.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
            ->where('a.shipment_id', $shipmentId)->whereIn('a.status', ['accepted', 'en_route', 'active'])->orderByDesc('a.id')
            ->first(['p.user_id', 's.public_id', 'o.order_number']);
        if ($row) {
            $this->notifications->notify((int) $row->user_id, 'driver.receiver_replied',
                ['order' => $row->order_number, 'reply' => self::OPTIONS[$reply].($note ? ": {$note}" : '')], "/driver/jobs/{$row->public_id}");
        }

        return ['reply' => $reply, 'label' => self::OPTIONS[$reply], 'note' => $note];
    }

    /** The reply on the shipment's drop-off, or null. */
    public static function current(?object $stop): ?array
    {
        if (! $stop || empty($stop->readiness)) {
            return null;
        }

        return ['reply' => $stop->readiness, 'label' => self::OPTIONS[$stop->readiness] ?? $stop->readiness, 'note' => $stop->readiness_note, 'at' => $stop->readiness_at];
    }
}
