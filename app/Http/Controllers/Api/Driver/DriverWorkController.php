<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Dispatch\DriverService;
use Illuminate\Http\Request;
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

    // ----------------------------------------------------------------

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
