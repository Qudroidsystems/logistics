<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\DropOff\DropOffService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Operations > Drop-off centres: partner applications, the stores, and paying them their commission. */
class DropOffCentresController extends Controller
{
    public function __construct(private DropOffService $dropoffs) {}

    private function check(Request $request): void
    {
        abort_unless($request->user()->can('Manage drop-off centres'), 403, 'You cannot manage drop-off centres.');
    }

    public function index(Request $request)
    {
        $this->check($request);

        return view('ops.dropoff-centres', [
            'applications' => DB::table('partner_applications')->where('status', 'new')->orderBy('created_at')->get(),
            'decided' => DB::table('partner_applications')->where('status', '<>', 'new')->orderByDesc('decided_at')->limit(20)->get(),
            'centres' => DB::table('dropoff_centres as c')->leftJoin('cities as ci', 'ci.id', '=', 'c.city_id')
                ->selectRaw("c.*, ci.name as city, ST_Y(c.point::geometry) as lat, ST_X(c.point::geometry) as lng,
                    (select count(*) from dropoff_parcels p where p.dropoff_centre_id = c.id and p.status = 'expected') as expected,
                    (select count(*) from dropoff_parcels p where p.dropoff_centre_id = c.id and p.status = 'received') as at_counter,
                    (select count(*) from dropoff_parcels p where p.dropoff_centre_id = c.id and p.status = 'collected') as handled,
                    (select coalesce(sum(commission), 0) from dropoff_parcels p where p.dropoff_centre_id = c.id and p.commission_status = 'earned') as owed")
                ->orderBy('c.name')->get(),
            'cities' => DB::table('cities')->orderBy('name')->pluck('name', 'id'),
            'pagetitle' => 'Drop-off centres',
        ]);
    }

    public function approve(Request $request, int $application)
    {
        $this->check($request);
        $d = $request->validate([
            'name' => 'nullable|string|max:120', 'line1' => 'nullable|string|max:200', 'opening_hours' => 'nullable|string|max:160',
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180', 'city_id' => 'nullable|integer|exists:cities,id',
            'commission_flat' => 'nullable|string|max:12', 'commission_pct' => 'nullable|numeric|min:0|max:100',
        ], ['lat.required' => 'Set where the store is (latitude and longitude).']);
        try {
            $this->dropoffs->approve($application, $request->user()->id, [
                'name' => $d['name'] ?? null, 'line1' => $d['line1'] ?? null, 'opening_hours' => $d['opening_hours'] ?? null,
                'lat' => (float) $d['lat'], 'lng' => (float) $d['lng'], 'city_id' => $d['city_id'] ?? null,
                'commission_flat' => (int) (Money::toMinor((string) ($d['commission_flat'] ?? '0')) ?? 0), 'commission_bp' => (int) round(((float) ($d['commission_pct'] ?? 0)) * 100),
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Approved. The store has been emailed a link to sign in and set a password.');
    }

    public function decline(Request $request, int $application)
    {
        $this->check($request);
        $d = $request->validate(['note' => 'required|string|max:300']);
        try {
            $this->dropoffs->decline($application, $request->user()->id, $d['note']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Application declined.');
    }

    public function update(Request $request, int $centre)
    {
        $this->check($request);
        $d = $request->validate([
            'name' => 'required|string|max:120', 'line1' => 'required|string|max:200', 'opening_hours' => 'nullable|string|max:160',
            'phone' => 'nullable|string|max:24', 'status' => 'required|in:active,paused', 'city_id' => 'nullable|integer|exists:cities,id',
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180',
            'commission_flat' => 'nullable|string|max:12', 'commission_pct' => 'nullable|numeric|min:0|max:100',
        ]);
        $n = DB::table('dropoff_centres')->where('id', $centre)->update([
            'name' => $d['name'], 'line1' => $d['line1'], 'opening_hours' => $d['opening_hours'] ?? null,
            'phone' => ! empty($d['phone']) ? (\App\Support\Phone::store($d['phone']) ?? $d['phone']) : null, 'status' => $d['status'], 'city_id' => $d['city_id'] ?? null,
            'point' => DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography', (float) $d['lng'], (float) $d['lat'])),
            'commission_flat' => (int) (Money::toMinor((string) ($d['commission_flat'] ?? '0')) ?? 0), 'commission_bp' => (int) round(((float) ($d['commission_pct'] ?? 0)) * 100),
            'updated_at' => now(),
        ]);
        abort_unless($n, 404);

        return back()->with('success', 'Store updated. New commission applies to parcels booked from now on.');
    }

    public function paid(Request $request, int $centre)
    {
        $this->check($request);
        $d = $request->validate(['reference' => 'required|string|max:60']);
        try {
            $amount = $this->dropoffs->markPaid($centre, $d['reference']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Marked '.Money::format($amount, true).' as paid.');
    }

    public function staff(Request $request, int $centre)
    {
        $this->check($request);
        $d = $request->validate(['email' => 'required|email']);
        try {
            $this->dropoffs->addStaff($centre, $d['email']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Added to the store.');
    }
}
