<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Fleet\VehicleCareService;
use Illuminate\Http\Request;
use RuntimeException;

/** The provider's vehicles: papers and servicing due, odometer, parcel capacity, service and fuel records. */
class VehiclesController extends WorkspaceController
{
    private const VIEWERS = ['owner', 'admin', 'driver_manager', 'dispatcher'];
    private const EDITORS = ['owner', 'admin', 'driver_manager'];

    public function index(Request $request, VehicleCareService $care)
    {
        [$op, $role] = $this->ctx($request);
        abort_unless(in_array($role, self::VIEWERS, true), 403, 'Your role does not include the vehicles page.');

        return $this->view('provider.vehicles', $request, [
            'vehicles' => $care->vehicles($op->id), 'history' => $care->history($op->id),
            'canEdit' => in_array($role, self::EDITORS, true), 'pagetitle' => 'Vehicles',
        ]);
    }

    public function care(Request $request, string $vehicle, VehicleCareService $care)
    {
        $op = $this->editor($request);
        $d = $request->validate([
            'insurance_expires_on' => 'nullable|date', 'roadworthy_expires_on' => 'nullable|date', 'next_service_on' => 'nullable|date',
            'service_interval_days' => 'nullable|integer|min:1|max:3650', 'service_interval_km' => 'nullable|integer|min:100|max:200000',
            'next_service_km' => 'nullable|integer|min:0|max:5000000', 'odometer_km' => 'nullable|integer|min:0|max:5000000',
            'capacity_parcels' => 'nullable|integer|min:1|max:5000',
        ]);

        return $this->done(fn () => $care->saveCare($op->id, $vehicle, $d), 'Saved.');
    }

    public function service(Request $request, string $vehicle, VehicleCareService $care)
    {
        $op = $this->editor($request);
        $d = $request->validate([
            'serviced_on' => 'required|date', 'odometer_km' => 'nullable|integer|min:0|max:5000000',
            'cost_naira' => 'nullable|numeric|min:0|max:100000000', 'description' => 'nullable|string|max:300',
        ]);
        $d['cost_kobo'] = (int) round(((float) ($d['cost_naira'] ?? 0)) * 100);

        return $this->done(fn () => $care->logService($op->id, $vehicle, $d, $request->user()->id), 'Service recorded. The next one is scheduled from it.');
    }

    public function fuel(Request $request, string $vehicle, VehicleCareService $care)
    {
        $op = $this->editor($request);
        $d = $request->validate([
            'filled_on' => 'required|date', 'litres' => 'required|numeric|min:0.1|max:2000', 'cost_naira' => 'required|numeric|min:1|max:100000000',
            'odometer_km' => 'nullable|integer|min:0|max:5000000', 'note' => 'nullable|string|max:200',
        ]);
        $d['cost_kobo'] = (int) round(((float) $d['cost_naira']) * 100);

        return $this->done(fn () => $care->logFuel($op->id, $vehicle, $d, $request->user()->id), 'Fuel recorded.');
    }

    private function editor(Request $request): object
    {
        [$op, $role] = $this->ctx($request);
        abort_unless(in_array($role, self::EDITORS, true), 403, 'Only an owner, admin or driver manager can change vehicles.');

        return $op;
    }

    private function done(callable $fn, string $ok)
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.vehicles')->with('success', $ok);
    }
}
