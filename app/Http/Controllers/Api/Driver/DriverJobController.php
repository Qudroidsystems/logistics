<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Modules\Tracking\DriverSyncService;
use App\Modules\Tracking\LocationIngestService;
use App\Modules\Tracking\StopService;
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
            'proof_type' => 'required|in:otp,photo,signature,qr_scan',
            'photo' => 'nullable|image|max:6144', 'otp' => 'nullable|string|max:12',
            'lat' => 'required|numeric', 'lng' => 'required|numeric', 'recipient_name' => 'nullable|string|max:120',
            // Sent by the offline queue so a retried upload completes the stop once.
            'client_event_id' => 'nullable|string|max:64', 'at' => 'nullable|date',
        ]);
        $driverId = $this->driverId($request);
        $work = function () use ($request, $stops, $driverId, $stop, $d) {
            // Never trust a client-supplied path: the file is stored here, on the private disk.
            $path = $request->attributes->get('proof_path')
                ?: ($request->hasFile('photo') ? $request->file('photo')->store('proofs/'.date('Y/m')) : null);
            $stops->complete($driverId, $stop, $d['proof_type'], $path, $d['otp'] ?? null, (float) $d['lat'], (float) $d['lng'], $d['recipient_name'] ?? null, $d['at'] ?? null);

            return ['shipment_id' => (int) DB::table('shipment_stops')->where('id', $stop)->value('shipment_id'), 'stop_id' => $stop];
        };

        if (! empty($d['client_event_id'])) {
            $r = app(DriverSyncService::class)->one($request->user()->id, $driverId, $d['client_event_id'], 'stop.photo', $d['at'] ?? null, array_diff_key($d, ['photo' => 1]), $work);

            return $r['status'] === 'accepted'
                ? response()->json(['ok' => true, 'duplicate' => $r['duplicate'], 'server_time' => now()->toIso8601String()])
                : response()->json(['message' => $r['message'], 'duplicate' => $r['duplicate']], 422);
        }

        try {
            DB::transaction($work);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
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
