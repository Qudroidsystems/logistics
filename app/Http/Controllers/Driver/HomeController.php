<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Api\Driver\DriverJobController;
use App\Http\Controllers\Api\Driver\DriverWorkController;
use App\Http\Controllers\Controller;
use App\Modules\Dispatch\DispatchService;
use App\Modules\Dispatch\DriverService;
use App\Modules\Tracking\LocationIngestService;
use App\Modules\Tracking\StopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The driver's phone-friendly pages: go online, take offers, run the job, hand over with a code or photo.
 * Pages call the same code as the driver JSON API, so a rule lives in one place.
 */
class HomeController extends Controller
{
    public function __construct(private DriverService $drivers, private DriverWorkController $work, private DriverJobController $jobs)
    {
    }

    public function home(Request $request)
    {
        $d = $this->driver($request);

        return view('driver.home', [
            'd' => $d, 'offers' => $this->drivers->offers($d->id), 'jobs' => $this->drivers->jobs($d->id),
            'pagetitle' => 'Driver',
        ]);
    }

    public function availability(Request $request)
    {
        $this->driver($request);

        return $this->back($this->work->availability($request), 'Updated.');
    }

    public function accept(Request $request, int $offer)
    {
        $res = $this->work->accept($request, $offer, app(DispatchService::class));
        if ($res->getStatusCode() >= 400) {
            return $this->back($res, '');
        }
        $shipment = DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')->where('a.id', $res->getData(true)['assignment_id'])->value('s.public_id');

        return redirect()->route('driver.job', $shipment)->with('success', 'Job accepted. Start the trip when you are ready.');
    }

    public function decline(Request $request, int $offer)
    {
        return $this->back($this->work->decline($request, $offer, app(DispatchService::class)), 'Offer declined.');
    }

    public function job(Request $request, string $shipment)
    {
        $d = $this->driver($request);
        $job = $this->drivers->job($d->id, $shipment);
        abort_unless($job, 404);

        return view('driver.job', [
            'job' => $job, 'issues' => DriverService::ISSUES, 'next' => collect($job['stops'])->first(fn ($s) => ! in_array($s['status'], ['completed', 'failed', 'skipped'], true)), 'failReasons' => \App\Modules\Marketplace\FailedDeliveryService::REASONS, 'pagetitle' => 'Job',
        ]);
    }

    public function start(Request $request, string $shipment)
    {
        return $this->back($this->work->start($request, $shipment), 'On your way to the pickup.');
    }

    public function completeStop(Request $request, string $shipment, int $stop)
    {
        $d = $this->driver($request);
        $request->validate([
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180',
            'otp' => 'nullable|string|max:12', 'recipient_name' => 'nullable|string|max:120',
            'id_type' => 'nullable|string|max:20', 'id_last4' => 'nullable|string|max:4',
            'photo' => 'nullable|image|max:6144', 'signature' => 'nullable|image|max:2048', 'cod_naira' => 'nullable|numeric|min:0',
        ], ['lat.required' => 'We could not read your location. Allow location access and try again.']);
        if ($request->filled('cod_naira')) {
            // The form takes naira; the stop check compares kobo.
            $request->merge(['cod_collected' => (int) round(((float) $request->input('cod_naira')) * 100)]);
        }

        // The stop must belong to this job and this driver.
        $job = $this->drivers->job($d->id, $shipment);
        abort_unless($job && collect($job['stops'])->contains('id', $stop), 404);

        $path = $request->file('photo') ? $request->file('photo')->store('proofs/'.date('Y/m')) : null;
        $request->attributes->set('proof_path', $path);
        $request->attributes->set('signature_path', $request->file('signature') ? $request->file('signature')->store('proofs/'.date('Y/m')) : null);

        return $this->back($this->jobs->completeStop($request, $stop, app(StopService::class)), 'Done.');
    }

    /** A parcel code typed (or scanned with the phone's camera app) at pickup or drop-off. */
    public function scan(Request $request, string $shipment)
    {
        $d = $this->driver($request);
        $in = $request->validate(['code' => 'required|string|max:80', 'stage' => 'required|in:pickup,dropoff']);
        abort_unless($this->drivers->job($d->id, $shipment), 404);
        $r = app(\App\Modules\Tracking\DriverSyncService::class)->apply($request->user()->id, $d->id, [
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'package.scan', 'at' => now()->toIso8601String(), 'data' => ['code' => $in['code'], 'stage' => $in['stage']]],
        ])[0];
        if ($r['status'] !== 'accepted') {
            return back()->with('error', $r['message']);
        }

        return back()->with('success', "Parcel {$r['result']['seq']} scanned ({$r['result']['scanned']} of {$r['result']['of']}).");
    }

    public function stopException(Request $request, string $shipment, int $stop)
    {
        $d = $this->driver($request);
        $job = $this->drivers->job($d->id, $shipment);
        abort_unless($job && collect($job['stops'])->contains('id', $stop), 404);

        return $this->back($this->jobs->requestException($request, $stop, app(\App\Modules\Verification\ExceptionService::class)), 'Sent to your dispatcher. You can finish once they approve it.');
    }

    public function release(Request $request, string $shipment)
    {
        $res = $this->work->release($request, $shipment);
        if ($res->getStatusCode() >= 400) {
            return $this->back($res, '');
        }

        return redirect()->route('driver.home')->with('success', 'The job has gone back to your dispatcher.');
    }

    public function fail(Request $request, string $shipment)
    {
        $res = $this->work->fail($request, $shipment, app(\App\Modules\Marketplace\FailedDeliveryService::class));
        if ($res->getStatusCode() >= 400) {
            return $this->back($res, '');
        }
        $d = $res->getData(true);

        return redirect()->route($d['returning'] ? 'driver.job' : 'driver.home', $d['returning'] ? [$shipment] : [])
            ->with('success', $d['returning'] ? 'Noted. Take the parcel back to the pickup address.' : 'Noted. The job is closed as a failed delivery.');
    }

    public function issue(Request $request, string $shipment)
    {
        return $this->back($this->work->issue($request, $shipment), 'Your company has been told.');
    }

    /** Browser location pings, posted every few seconds while a job is live. */
    public function location(Request $request): JsonResponse
    {
        $this->driver($request);

        return $this->jobs->location($request, app(LocationIngestService::class));
    }

    public function history(Request $request)
    {
        $d = $this->driver($request);

        return view('driver.history', ['rows' => $this->drivers->history($d->id, 50), 'earn' => $this->drivers->earnings($d->id), 'pagetitle' => 'Past jobs']);
    }

    // ----------------------------------------------------------------

    private function driver(Request $request): object
    {
        $d = $this->drivers->profileFor($request->user()->id, (int) $request->user()->current_operator_id);
        abort_unless($d, 403, 'You do not have an active driver profile.');

        return $d;
    }

    private function back(JsonResponse $res, string $ok)
    {
        if ($res->getStatusCode() >= 400) {
            return back()->withInput()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }

        return back()->with('success', $ok);
    }
}
