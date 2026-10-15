<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Modules\Tracking\DriverSyncService;
use App\Modules\Tracking\LocationIngestService;
use App\Modules\Tracking\StopService;
use App\Modules\Verification\DeliveryCodes;
use App\Modules\Verification\ExceptionService;
use App\Modules\Verification\WrongDeliveryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DriverJobController extends Controller
{
    public function location(Request $request, LocationIngestService $ingest)
    {
        $data = $request->validate([
            'pings' => 'required|array|min:1|max:100',
            'pings.*.lat' => 'required|numeric|between:-90,90',
            'pings.*.lng' => 'required|numeric|between:-180,180',
            'pings.*.at' => 'required|date',
            'pings.*.accuracy' => 'nullable|numeric',
            'pings.*.speed' => 'nullable|numeric',
            'pings.*.heading' => 'nullable|numeric',
            'pings.*.battery' => 'nullable|integer|between:0,100',
            'pings.*.mocked' => 'nullable|boolean',
        ]);

        return response()->json($ingest->ingest($this->driverId($request), $data['pings']));
    }

    public function completeStop(Request $request, int $stop, StopService $stops)
    {
        $d = $request->validate([
            // Kept for older app versions; the agreed proofs decide what is needed now.
            'proof_type' => 'nullable|in:otp,photo,signature,qr_scan',
            'photo' => 'nullable|image|max:6144', 'signature' => 'nullable|image|max:2048', 'otp' => 'nullable|string|max:12',
            'lat' => 'required|numeric', 'lng' => 'required|numeric', 'recipient_name' => 'nullable|string|max:120',
            'id_type' => 'nullable|string|max:20', 'id_last4' => 'nullable|string|max:4', 'cod_collected' => 'nullable|integer|min:0',
            // Sent by the offline queue so a retried upload completes the stop once.
            'client_event_id' => 'nullable|string|max:64', 'at' => 'nullable|date',
        ]);
        $driverId = $this->driverId($request);
        $work = function () use ($request, $stops, $driverId, $stop, $d) {
            // Never trust a client-supplied path: files are stored here, on the private disk.
            $photo = $request->attributes->get('proof_path')
                ?: ($request->hasFile('photo') ? $request->file('photo')->store('proofs/'.date('Y/m')) : null);
            $signature = $request->attributes->get('signature_path')
                ?: ($request->hasFile('signature') ? $request->file('signature')->store('proofs/'.date('Y/m')) : null);
            $stops->complete($driverId, $stop, self::evidence($d, $photo, $signature), (float) $d['lat'], (float) $d['lng'], $d['at'] ?? null);

            return ['shipment_id' => (int) DB::table('shipment_stops')->where('id', $stop)->value('shipment_id'), 'stop_id' => $stop];
        };

        if (! empty($d['client_event_id'])) {
            $r = app(DriverSyncService::class)->one($request->user()->id, $driverId, $d['client_event_id'], 'stop.photo', $d['at'] ?? null, array_diff_key($d, ['photo' => 1, 'signature' => 1]), $work);

            return $r['status'] === 'accepted'
                ? response()->json(['ok' => true, 'duplicate' => $r['duplicate'], 'server_time' => now()->toIso8601String()])
                : response()->json(['message' => $r['message'], 'duplicate' => $r['duplicate']], 422);
        }

        try {
            DB::transaction($work);
        } catch (WrongDeliveryCode $e) {
            // The completion rolled back; the wrong try still counts towards the lock.
            app(DeliveryCodes::class)->recordFailure($e->stopId);

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    /** The driver cannot provide one of the agreed proofs: ask the provider to waive it, with a reason. */
    public function requestException(Request $request, int $stop, ExceptionService $exceptions)
    {
        $d = $request->validate(['method' => 'required|string|max:16', 'reason' => 'required|string|max:1000']);
        try {
            return response()->json(['exception' => $exceptions->request($this->driverId($request), $stop, $d['method'], $d['reason'])]);
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Request fields (or an offline action's data) to the evidence StopService checks. */
    public static function evidence(array $d, ?string $photo = null, ?string $signature = null): array
    {
        return [
            'code' => $d['otp'] ?? null, 'photo_path' => $photo, 'signature_path' => $signature,
            'recipient_name' => $d['recipient_name'] ?? null, 'id_type' => $d['id_type'] ?? null,
            'id_last4' => isset($d['id_last4']) ? (string) $d['id_last4'] : null, 'cod_collected' => $d['cod_collected'] ?? null,
        ];
    }

    /**
     * The offline queue's upload: a batch of actions the phone saved, each with its own id.
     * Every action gets its own answer; one rejected action never blocks the rest.
     */
    public function sync(Request $request, DriverSyncService $sync)
    {
        $d = $request->validate([
            'events' => 'required|array|min:1|max:'.DriverSyncService::BATCH_LIMIT,
            'events.*.id' => 'required|string|max:64',
            'events.*.type' => 'required|string|max:32',
            'events.*.at' => 'nullable|date',
            'events.*.data' => 'nullable|array',
        ]);

        return response()->json([
            'results' => $sync->apply($request->user()->id, $this->driverId($request), $d['events']),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function driverId(Request $request): int
    {
        $id = DB::table('driver_profiles')->where('user_id', $request->user()->id)->where('status', 'active')->value('id');
        abort_unless($id, 403, 'No active driver profile.');

        return (int) $id;
    }
}
