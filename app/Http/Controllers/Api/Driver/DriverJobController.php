<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
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
        ]);
        // Never trust a client-supplied path: the file is stored here, on the private disk.
        $path = $request->attributes->get('proof_path')
            ?: ($request->hasFile('photo') ? $request->file('photo')->store('proofs/'.date('Y/m')) : null);
        try {
            $stops->complete($this->driverId($request), $stop, $d['proof_type'], $path, $d['otp'] ?? null, (float) $d['lat'], (float) $d['lng'], $d['recipient_name'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    private function driverId(Request $request): int
    {
        $id = DB::table('driver_profiles')->where('user_id', $request->user()->id)->where('status', 'active')->value('id');
        abort_unless($id, 403, 'No active driver profile.');

        return (int) $id;
    }
}
