<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Dispatch\DriverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** The driver's own work for the app: availability, offers, jobs and starting a trip. */
class DriverWorkController extends Controller
{
    public function __construct(private DriverService $drivers)
    {
    }

    public function me(Request $request)
    {
        $d = $this->driver($request);

        return response()->json([
            'driver' => $d, 'offers' => $this->drivers->offers($d->id), 'jobs' => $this->drivers->jobs($d->id),
        ]);
    }

    public function availability(Request $request)
    {
        $d = $this->driver($request);
        $data = $request->validate(['availability' => 'required|in:online,offline,break']);

        return $this->run(function () use ($d, $data) {
            $this->drivers->setAvailability($d->id, $data['availability']);

            return ['availability' => $data['availability']];
        });
    }

    public function offers(Request $request)
    {
        return response()->json($this->drivers->offers($this->driver($request)->id));
    }

    public function accept(Request $request, int $offer, DispatchService $dispatch)
    {
        $d = $this->driver($request);

        return $this->run(function () use ($dispatch, $offer, $d) {
            $assignment = $dispatch->respond($offer, $d->id, true);

            return ['assignment_id' => $assignment];
        });
    }

    public function decline(Request $request, int $offer, DispatchService $dispatch)
    {
        $d = $this->driver($request);
        $data = $request->validate(['reason' => 'nullable|string|max:60']);

        return $this->run(function () use ($dispatch, $offer, $d, $data) {
            $dispatch->respond($offer, $d->id, false, $data['reason'] ?? null);

            return ['ok' => true];
        });
    }

    public function jobs(Request $request)
    {
        $d = $this->driver($request);

        return response()->json(['live' => $this->drivers->jobs($d->id), 'done' => $this->drivers->history($d->id)]);
    }

    public function job(Request $request, string $shipment)
    {
        $job = $this->drivers->job($this->driver($request)->id, $shipment);
        abort_unless($job, 404);

        return response()->json($job);
    }

    public function start(Request $request, string $shipment)
    {
        $d = $this->driver($request);

        return $this->run(function () use ($d, $shipment) {
            $this->drivers->startTrip($d->id, $shipment);

            return ['ok' => true];
        });
    }

    public function release(Request $request, string $shipment)
    {
        $d = $this->driver($request);
        $data = $request->validate(['reason' => 'required|string|max:30', 'note' => 'nullable|string|max:500']);

        return $this->run(function () use ($d, $shipment, $data) {
            $this->drivers->release($d->id, $shipment, $data['reason'], $data['note'] ?? null);

            return ['ok' => true];
        });
    }

    public function issue(Request $request, string $shipment)
    {
        $d = $this->driver($request);
        $data = $request->validate(['reason' => 'required|string|max:30', 'note' => 'nullable|string|max:500']);

        return $this->run(function () use ($d, $shipment, $data) {
            $this->drivers->reportIssue($d->id, $shipment, $data['reason'], $data['note'] ?? null);

            return ['ok' => true];
        });
    }

    public function fail(Request $request, string $shipment, \App\Modules\Marketplace\FailedDeliveryService $failed)
    {
        $d = $this->driver($request);
        $data = $request->validate(['reason' => 'required|string|max:30', 'note' => 'nullable|string|max:500']);

        return $this->run(fn () => $failed->markFailed($d->id, $shipment, $data['reason'], $data['note'] ?? null));
    }

    public function earnings(Request $request)
    {
        return response()->json($this->drivers->earnings($this->driver($request)->id));
    }

    // ----------------------------------------------------------------

    /**
     * Looks up a scanned QR or typed parcel code. Only answers for parcels on a job this driver holds,
     * so a label photographed elsewhere tells a stranger nothing.
     */
    public function package(Request $request)
    {
        $d = $this->driver($request);
        $code = (string) $request->validate(['code' => 'required|string|max:60'])['code'];
        $p = app(\App\Modules\Tracking\ParcelCodes::class)->find($code);
        $s = $p ? DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')->where('a.shipment_id', $p->shipment_id)
            ->where('a.driver_profile_id', $d->id)->whereIn('a.status', ['accepted', 'en_route', 'active', 'completed'])->first(['s.public_id', 's.status']) : null;
        if (! $s) {
            return response()->json(['error' => 'not_found', 'message' => 'This code is not on any of your jobs.'], 404);
        }

        return response()->json(['shipment' => $s->public_id, 'shipment_status' => $s->status, 'package' => [
            'public_id' => $p->public_id, 'seq' => (int) $p->seq, 'description' => $p->description, 'barcode' => $p->barcode,
            'quantity' => (int) $p->quantity, 'fragile' => (bool) $p->fragile, 'status' => $p->status,
            'of' => DB::table('packages')->where('shipment_id', $p->shipment_id)->count(),
        ]]);
    }

    private function driver(Request $request): object
    {
        $d = $this->drivers->profileFor($request->user()->id, (int) $request->user()->current_operator_id);
        abort_unless($d, 403, 'No active driver profile.');

        return $d;
    }

    private function run(callable $fn)
    {
        try {
            return response()->json($fn());
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
