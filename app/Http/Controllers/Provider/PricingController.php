<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Pricing\CostCalculator;
use App\Modules\Pricing\ProviderCostingService;
use App\Modules\Tracking\ParcelCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

/**
 * The pricing calculator in the provider workspace: cost profiles in naira, a distance calculator on the map,
 * and printable parcel labels. Figures always come from ProviderCostingService, the same code the apps call.
 */
class PricingController extends WorkspaceController
{
    private const WRITERS = ['owner', 'admin'];

    /** Naira fields on the profile form and their kobo columns. */
    private const NAIRA = [
        'base_fee', 'rate_per_km', 'min_fee', 'fuel_price_per_litre', 'labour_per_job', 'labour_per_hour', 'maintenance_per_km',
        'other_per_job', 'loading_fee', 'waiting_per_minute', 'fragile_surcharge', 'rounding_kobo',
    ];

    /** Percent fields on the form and their basis-point columns. */
    private const PERCENT = ['insurance_bp', 'return_trip_bp', 'markup_bp'];

    public function index(Request $request, ProviderCostingService $costing)
    {
        [$op, $role] = $this->ctx($request, 'requests');
        $city = $op->id ? DB::table('operators')->where('id', $op->id)->value('home_city_id') : null;
        $centre = $city ? DB::selectOne('SELECT ST_Y(centre::geometry) AS lat, ST_X(centre::geometry) AS lng FROM cities WHERE id = ?', [$city]) : null;

        return $this->view('provider.pricing', $request, [
            'profiles' => $costing->profiles($op->id),
            'editing' => $request->query('edit') ? DB::table('provider_cost_profiles')->where(['public_id' => $request->query('edit'), 'operator_id' => $op->id, 'active' => true])->first() : null,
            'canEdit' => in_array($role, self::WRITERS, true),
            'vehicleTypes' => DB::table('vehicle_types')->where('active', true)->orderBy('id')->get(['id', 'name']),
            'recent' => $costing->recent($op->id, 10),
            'centre' => $centre, 'methods' => CostCalculator::METHODS,
            'pagetitle' => 'Pricing calculator',
        ], 'requests');
    }

    public function save(Request $request, ProviderCostingService $costing, ?string $profile = null)
    {
        [$op, $role] = $this->ctx($request, 'requests');
        abort_unless(in_array($role, self::WRITERS, true), 403, 'Only an owner or admin can change cost profiles.');
        $d = $request->validate([
            'name' => 'required|string|max:80',
            'vehicle_type_id' => 'nullable|integer',
            'pricing_method' => ['required', Rule::in(CostCalculator::METHODS)],
            'fuel_l_per_100km' => 'nullable|numeric|min:0|max:200',
            'is_default' => 'nullable|boolean',
        ] + array_fill_keys(self::NAIRA, 'nullable|numeric|min:0|max:1000000000') + array_fill_keys(self::PERCENT, 'nullable|numeric|min:0|max:1000'));

        foreach (self::NAIRA as $f) {
            $d[$f] = (int) round(((float) ($d[$f] ?? 0)) * 100);
        }
        foreach (self::PERCENT as $f) {
            $d[$f] = (int) round(((float) ($d[$f] ?? 0)) * 100);
        }
        $d['rounding_kobo'] = max(1, $d['rounding_kobo']);
        $d['is_default'] = $request->boolean('is_default');

        try {
            $costing->saveProfile($op->id, $d, $profile);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.pricing')->with('success', $profile ? 'Cost profile updated. New estimates use the new figures; saved ones keep theirs.' : 'Cost profile saved.');
    }

    public function retire(Request $request, ProviderCostingService $costing, string $profile)
    {
        [$op, $role] = $this->ctx($request, 'requests');
        abort_unless(in_array($role, self::WRITERS, true), 403);
        try {
            $costing->retireProfile($op->id, $profile);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cost profile removed.');
    }

    /** JSON for the calculator on the pricing page and the request page. Amounts in the reply are kobo. */
    public function estimate(Request $request, ProviderCostingService $costing)
    {
        [$op] = $this->ctx($request, 'requests');
        $d = $request->validate([
            'profile' => 'nullable|string|max:26',
            'request' => 'nullable|string|max:26',
            'stops' => 'nullable|array|max:'.ProviderCostingService::MAX_STOPS,
            'stops.*.lat' => 'required_with:stops|numeric|between:-90,90',
            'stops.*.lng' => 'required_with:stops|numeric|between:-180,180',
            'distance_km' => 'nullable|numeric|min:0.1|max:5000',
            'duration_min' => 'nullable|numeric|min:1|max:10000',
            'round_trip' => 'nullable|boolean',
            'wait_minutes' => 'nullable|integer|min:0|max:1440',
            'fragile' => 'nullable|boolean',
            'loading' => 'nullable|boolean',
            'declared_value_naira' => 'nullable|numeric|min:0',
            'job_expenses_naira' => 'nullable|numeric|min:0',
        ]);
        $job = array_filter([
            'profile' => $d['profile'] ?? null, 'stops' => $d['stops'] ?? null, 'distance_km' => $d['distance_km'] ?? null, 'duration_min' => $d['duration_min'] ?? null,
            'round_trip' => $request->boolean('round_trip'), 'wait_minutes' => (int) ($d['wait_minutes'] ?? 0), 'fragile' => $request->boolean('fragile'), 'loading' => $request->boolean('loading'),
            'declared_value' => (int) round(((float) ($d['declared_value_naira'] ?? 0)) * 100), 'job_expenses' => (int) round(((float) ($d['job_expenses_naira'] ?? 0)) * 100),
        ], fn ($v) => $v !== null && $v !== []);

        try {
            $res = ! empty($d['request'])
                ? $costing->estimateForRequest($op->id, $request->user()->id, $d['request'], array_diff_key($job, ['stops' => 1, 'distance_km' => 1, 'duration_min' => 1]))
                : $costing->estimate($op->id, $request->user()->id, $job);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($res);
    }

    /** Printable labels: one per parcel, with its code and QR. */
    public function labels(Request $request, ParcelCodes $codes, string $shipment)
    {
        [$op] = $this->ctx($request, 'jobs');
        $s = DB::table('shipments')->where('public_id', $shipment)->where('operator_id', $op->id)->first();
        abort_unless($s, 404);
        $dropoff = DB::table('shipment_stops')->where('shipment_id', $s->id)->where('type', 'dropoff')->first(['line1', 'landmark', 'contact_name']);

        return view('provider.labels', [
            's' => $s, 'op' => $op, 'dropoff' => $dropoff,
            'packages' => array_map(fn ($p) => $p + ['qr_svg' => $this->qr($p['qr_payload'])], $codes->forShipment((int) $s->id, true)),
        ]);
    }

    private function qr(string $payload): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(160, 1), new \BaconQrCode\Renderer\Image\SvgImageBackEnd());

        return (new \BaconQrCode\Writer($renderer))->writeString($payload);
    }
}
