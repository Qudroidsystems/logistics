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
            'job' => $job, 'next' => collect($job['stops'])->first(fn ($s) => $s['status'] !== 'completed'), 'pagetitle' => 'Job',
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
            'photo' => 'nullable|image|max:6144',
        ], ['lat.required' => 'We could not read your location. Allow location access and try again.']);

        // The stop must belong to this job and this driver.
        $job = $this->drivers->job($d->id, $shipment);
        abort_unless($job && collect($job['stops'])->contains('id', $stop), 404);

        $path = $request->file('photo') ? $request->file('photo')->store('proofs/'.date('Y/m')) : null;
        $request->merge(['proof_type' => $request->filled('otp') ? 'otp' : 'photo', 'file_path' => $path]);

        return $this->back($this->jobs->completeStop($request, $stop, app(StopService::class)), 'Done.');
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

        return view('driver.history', ['rows' => $this->drivers->history($d->id, 50), 'pagetitle' => 'Past jobs']);
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
