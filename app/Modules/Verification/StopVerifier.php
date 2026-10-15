<?php

namespace App\Modules\Verification;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Checks the driver's evidence against the proofs the stop needs. Everything missing is reported at once, so the
 * driver fixes it in one go. The delivery code is checked last, so a try is only spent when everything else is there.
 *
 * Evidence keys: code, photo_path, signature_path, recipient_name, id_type, id_last4, cod_collected.
 */
class StopVerifier
{
    public const ID_TYPES = ['nin', 'drivers_licence', 'voters_card', 'passport', 'staff_id', 'other'];

    public function __construct(private VerificationPolicy $policy, private ExceptionService $exceptions, private DeliveryCodes $codes)
    {
    }

    /** @return array{required:list<string>, satisfied:list<string>, waived:list<string>} */
    public function check(object $stop, array $e): array
    {
        $required = $this->policy->forStop($stop);
        $waived = array_values(array_intersect($required, $this->exceptions->waived((int) $stop->id)));
        $missing = [];
        $satisfied = [];

        foreach (array_diff($required, $waived, ['code']) as $m) {
            $problem = match ($m) {
                'photo' => empty($e['photo_path']) ? 'a photo of the hand-over' : null,
                'signature' => empty($e['signature_path']) ? "the receiver's signature" : null,
                'receiver_name' => $this->receiverProblem((int) $stop->shipment_id, (string) ($e['recipient_name'] ?? '')),
                'receiver_id' => (! in_array($e['id_type'] ?? null, self::ID_TYPES, true) || ! preg_match('/^\d{4}$/', (string) ($e['id_last4'] ?? '')))
                    ? "the receiver's ID type and its last 4 digits" : null,
                'scan' => $this->scanProblem($stop),
                'cod' => $this->codProblem((int) $stop->shipment_id, $e['cod_collected'] ?? null),
                default => null,
            };
            $problem ? $missing[] = $problem : $satisfied[] = $m;
        }
        $needsCode = in_array('code', $required, true) && ! in_array('code', $waived, true);
        if ($needsCode && trim((string) ($e['code'] ?? '')) === '') {
            $missing[] = 'the delivery code from the receiver';
        }
        if ($missing) {
            throw new RuntimeException('Still needed: '.implode('; ', $missing).'.');
        }
        if ($needsCode) {
            $this->codes->verify((int) $stop->id, (string) $e['code']);
            $satisfied[] = 'code';
        }

        return ['required' => array_values($required), 'satisfied' => array_values(array_intersect($required, $satisfied)), 'waived' => $waived];
    }

    /** When the agreement names who may receive the parcel, the name given must be one of them. */
    private function receiverProblem(int $shipmentId, string $name): ?string
    {
        $name = $this->plain($name);
        if ($name === '') {
            return "the receiver's name";
        }
        $allowed = $this->policy->forShipment($shipmentId)['authorised_receivers'];
        if ($allowed && ! in_array($name, array_map(fn ($n) => $this->plain($n), $allowed), true)) {
            return 'a receiver named in the agreement ('.implode(', ', $allowed).')';
        }

        return null;
    }

    /** A multi-parcel job cannot be handed over with parcels unaccounted for. */
    private function scanProblem(object $stop): ?string
    {
        $type = $stop->type === 'pickup' ? 'pickup' : 'delivery';
        // On a multi-stop route only the parcels loaded or unloaded at this stop count.
        $column = $stop->type === 'pickup' ? 'pickup_stop_id' : 'dropoff_stop_id';
        $all = DB::table('packages')->where('shipment_id', $stop->shipment_id)
            ->where(fn ($q) => $q->where($column, $stop->id)->orWhereNull($column))->pluck('public_id', 'id');
        if ($all->isEmpty()) {
            return null;
        }
        $seen = DB::table('package_scans')->where('shipment_id', $stop->shipment_id)->where('scan_type', $type)->distinct()->pluck('package_id')->all();
        $left = $all->except($seen);

        return $left->isEmpty() ? null : 'scan '.$left->count().' more parcel'.($left->count() === 1 ? '' : 's').' ('.count($seen).' of '.$all->count().' scanned)';
    }

    private function codProblem(int $shipmentId, $collected): ?string
    {
        $due = (int) DB::table('shipments')->where('id', $shipmentId)->value('cod_amount');

        return is_numeric($collected) && (int) $collected === $due ? null : 'the cash collected (₦'.number_format($due / 100).')';
    }

    private function plain(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }
}
