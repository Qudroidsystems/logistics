<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Pricing\CostCalculator;
use App\Modules\Pricing\ProviderCostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

/**
 * The provider's pricing calculator over JSON. Money is kobo, rates are basis points, fuel use is litres per 100 km.
 * Owners and admins set the cost profiles; anyone who prices requests may run an estimate.
 */
class CostingController extends Controller
{
    use ResolvesOperator;

    private const WRITERS = ['owner', 'admin'];
    private const PRICERS = ['owner', 'admin', 'dispatcher', 'finance'];

    public function __construct(private ProviderCostingService $costing)
    {
    }

    public function profiles(Request $request)
    {
        return response()->json(['profiles' => $this->costing->profiles($this->operatorId($request, self::PRICERS)), 'methods' => CostCalculator::METHODS]);
    }

    public function saveProfile(Request $request, ?string $profile = null)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return $this->run(fn () => $this->costing->saveProfile($op, $this->profileInput($request), $profile), $profile ? 200 : 201);
    }

    public function retireProfile(Request $request, string $profile)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return $this->run(function () use ($op, $profile) {
            $this->costing->retireProfile($op, $profile);

            return ['retired' => true];
        });
    }

    public function estimate(Request $request)
    {
        $op = $this->operatorId($request, self::PRICERS);
        $d = $request->validate($this->jobRules() + [
            'stops' => 'required_without:distance_km|array|min:2|max:'.ProviderCostingService::MAX_STOPS,
            'stops.*.lat' => 'required_with:stops|numeric|between:-90,90',
            'stops.*.lng' => 'required_with:stops|numeric|between:-180,180',
            'distance_km' => 'required_without:stops|nullable|numeric|min:0.1|max:5000',
            'duration_min' => 'nullable|numeric|min:1|max:10000',
            'service_type_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
        ]);

        return $this->run(fn () => $this->costing->estimate($op, $request->user()->id, $d), 201);
    }

    public function estimateRequest(Request $request, string $serviceRequest)
    {
        $op = $this->operatorId($request, self::PRICERS);
        $d = $request->validate($this->jobRules());

        return $this->run(fn () => $this->costing->estimateForRequest($op, $request->user()->id, $serviceRequest, $d), 201);
    }

    public function estimates(Request $request)
    {
        return response()->json(['estimates' => $this->costing->recent($this->operatorId($request, self::PRICERS))]);
    }

    public function show(Request $request, string $estimate)
    {
        $e = $this->costing->find($this->operatorId($request, self::PRICERS), $estimate);
        abort_unless($e, 404);

        return response()->json($e);
    }

    // ---------------------------------------------------------------- helpers

    private function jobRules(): array
    {
        return [
            'profile' => 'nullable|string|max:26',
            'round_trip' => 'nullable|boolean',
            'wait_minutes' => 'nullable|integer|min:0|max:1440',
            'fragile' => 'nullable|boolean',
            'loading' => 'nullable|boolean',
            'declared_value' => 'nullable|integer|min:0',
            'job_expenses' => 'nullable|integer|min:0',
        ];
    }

    private function profileInput(Request $request): array
    {
        $money = 'nullable|integer|min:0|max:100000000000';

        return $request->validate([
            'name' => 'required|string|max:80',
            'vehicle_type_id' => 'nullable|integer',
            'pricing_method' => ['nullable', Rule::in(CostCalculator::METHODS)],
            'fuel_l_per_100km' => 'nullable|numeric|min:0|max:200',
            'insurance_bp' => 'nullable|integer|min:0|max:10000',
            'return_trip_bp' => 'nullable|integer|min:0|max:10000',
            'markup_bp' => 'nullable|integer|min:0|max:100000',
            'rounding_kobo' => 'nullable|integer|min:1|max:10000000',
            'is_default' => 'nullable|boolean',
        ] + array_fill_keys(['base_fee', 'rate_per_km', 'min_fee', 'fuel_price_per_litre', 'labour_per_job', 'labour_per_hour', 'maintenance_per_km', 'other_per_job', 'loading_fee', 'waiting_per_minute', 'fragile_surcharge'], $money));
    }

    private function run(callable $fn, int $status = 200)
    {
        try {
            return response()->json($fn(), $status);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
