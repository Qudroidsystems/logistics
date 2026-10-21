<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Drivers\DriverApplicationService as Apps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Operations > Driver applications: people who applied on "Drive with us". Check their documents and approve or decline. */
class DriverApplicationsController extends Controller
{
    private function check(Request $request, bool $decide = false): void
    {
        abort_unless($request->user()->canAny($decide ? ['Approve driver'] : ['Approve driver', 'View driver']), 403);
    }

    public function index(Request $request)
    {
        $this->check($request);
        $status = in_array($request->query('status'), ['new', 'approved', 'declined'], true) ? $request->query('status') : 'new';

        return view('ops.driver-applications', [
            'rows' => DB::table('driver_applications')->where('status', $status)->orderBy($status === 'new' ? 'created_at' : 'decided_at', $status === 'new' ? 'asc' : 'desc')->limit(200)->get(),
            'counts' => DB::table('driver_applications')->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'status' => $status, 'canDecide' => $request->user()->can('Approve driver'), 'ready' => Apps::operatorId() !== null,
            'fleet' => Apps::operatorId() ? DB::table('operators')->where('id', Apps::operatorId())->value('display_name') : null,
            'pagetitle' => 'Driver applications',
        ]);
    }

    public function file(Request $request, int $application, string $kind, Apps $apps)
    {
        $this->check($request);
        abort_unless(array_key_exists($kind, Apps::FILES), 404);
        $a = DB::table('driver_applications')->where('id', $application)->first();
        abort_unless($a && ($path = $apps->file($a, $kind)), 404);

        return Storage::disk('local')->response($path, $kind.'-'.$a->public_id.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store']);
    }

    public function approve(Request $request, int $application, Apps $apps)
    {
        $this->check($request, true);
        $d = $request->validate(['checks' => 'nullable|array', 'checks.*' => 'string', 'note' => 'nullable|string|max:500']);
        $missing = array_diff(array_keys(Apps::CHECKS), $d['checks'] ?? []);
        $a = DB::table('driver_applications')->where('id', $application)->first();
        abort_unless($a, 404);
        // Insurance and registration checks only apply to someone driving their own vehicle.
        if (! $a->plate) {
            $missing = array_diff($missing, ['insurance', 'registration']);
        }
        if ($missing) {
            return back()->with('error', 'Tick every check before approving: '.implode(', ', array_map(fn ($k) => strtolower(Apps::CHECKS[$k]), $missing)).'.');
        }
        try {
            $apps->approve($application, $request->user()->id, $d['checks'] ?? [], $d['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Approved. '.$a->name.' has been emailed and can sign in to the driver app.');
    }

    public function decline(Request $request, int $application, Apps $apps)
    {
        $this->check($request, true);
        $d = $request->validate(['note' => 'required|string|max:500', 'tell' => 'nullable|boolean']);
        try {
            $apps->decline($application, $request->user()->id, $d['note'], (bool) ($d['tell'] ?? true));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Declined.');
    }
}
