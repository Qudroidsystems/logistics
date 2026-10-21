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
            // Cash on delivery collected and not yet handed in at the office.
            'cash_held' => app(\App\Modules\Cod\CodService::class)->heldBy((int) $d->id),
        ]);
    }

    /**
     * GET /v1/driver/handle: the @handle customers type to ask for this rider. A rider at a company has their own;
     * an independent rider's handle is their business page's.
     */
    public function handle(Request $request, \App\Modules\Directory\DirectoryService $dir)
    {
        return response()->json($this->handleInfo($this->driver($request), $dir));
    }

    /** PUT /v1/driver/handle {handle} */
    public function setHandle(Request $request, \App\Modules\Directory\DirectoryService $dir)
    {
        $d = $this->driver($request);
        $request->validate(['handle' => 'required|string|max:31']);
        $own = $this->ownPage($d);
        try {
            $h = $own ? $dir->validate((string) $request->input('handle'), null, (int) $d->operator_id) : $dir->validate((string) $request->input('handle'), (int) $d->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'invalid', 'message' => $e->getMessage()], 422);
        }
        $own
            ? DB::table('provider_profiles')->where('operator_id', $d->operator_id)->update(['public_slug' => $h, 'updated_at' => now()])
            : DB::table('driver_profiles')->where('id', $d->id)->update(['handle' => $h, 'updated_at' => now()]);

        return response()->json($this->handleInfo($d, $dir) + ['message' => "Saved. Customers can now find you as @{$h}."]);
    }

    /** An independent rider with a public page uses that page's handle. */
    private function ownPage(object $d): ?string
    {
        $type = DB::table('operators')->where('id', $d->operator_id)->value('type');

        return $type === 'independent_driver' ? DB::table('provider_profiles')->where('operator_id', $d->operator_id)->value('public_slug') : null;
    }

    private function handleInfo(object $d, \App\Modules\Directory\DirectoryService $dir): array
    {
        $own = $this->ownPage($d);
        $op = DB::table('operators as o')->leftJoin('provider_profiles as p', 'p.operator_id', '=', 'o.id')->where('o.id', $d->operator_id)->first(['o.display_name', 'p.listed']);

        return [
            'handle' => $own ?? $dir->ensureDriverHandle((int) $d->id),
            'company' => $own ? null : $op?->display_name,
            // Customers only find riders of businesses that are listed on the marketplace.
            'findable' => (bool) ($op?->listed ?? false),
        ];
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
