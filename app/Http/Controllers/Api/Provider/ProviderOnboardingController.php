<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Concerns\ResolvesOperator;
use App\Http\Controllers\Controller;
use App\Modules\Providers\ProviderOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class ProviderOnboardingController extends Controller
{
    use ResolvesOperator;

    private const WRITERS = ['owner', 'admin'];

    public function __construct(private ProviderOnboardingService $onboarding)
    {
    }

    /** Service types, vehicle types and cities a provider can pick from while setting up. */
    public function catalog()
    {
        return response()->json([
            'provider_types' => ProviderOnboardingService::TYPES,
            'required_documents' => ProviderOnboardingService::REQUIRED_DOCS,
            'service_types' => DB::table('service_types')->where('active', true)->orderBy('id')->get(['id', 'code', 'name']),
            'vehicle_types' => DB::table('vehicle_types')->where('active', true)->orderBy('id')->get(['id', 'code', 'name', 'max_weight_g']),
            'cities' => DB::table('cities')->orderBy('name')->get(['id', 'name', 'lat', 'lng']),
        ]);
    }

    public function register(Request $request)
    {
        $d = $request->validate([
            'type' => ['required', Rule::in(ProviderOnboardingService::TYPES)],
            'legal_name' => 'required|string|max:160',
            'display_name' => 'required|string|max:120',
            'city_id' => 'nullable|integer|exists:cities,id',
        ]);

        try {
            $op = $this->onboarding->register($request->user()->id, $d['type'], $d['legal_name'], $d['display_name'], $d['city_id'] ?? null);
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }

        return response()->json($this->status($op), 201);
    }

    public function onboarding(Request $request)
    {
        return response()->json($this->status($this->operatorId($request, self::WRITERS)));
    }

    public function profile(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return response()->json(DB::table('provider_profiles as p')->join('operators as o', 'o.id', '=', 'p.operator_id')->where('p.operator_id', $op)
            ->first(['o.display_name', 'o.legal_name', 'o.type', 'o.status', 'p.public_slug', 'p.headline', 'p.about', 'p.years_operating', 'p.fleet_size', 'p.service_types',
                'p.vehicle_types', 'p.coverage', 'p.languages', 'p.min_job_value', 'p.availability_status', 'p.tier', 'p.listed', 'p.rating_avg', 'p.rating_count']));
    }

    public function updateProfile(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $d = $request->validate([
            'display_name' => 'sometimes|string|max:120',
            'headline' => 'sometimes|nullable|string|max:140',
            'about' => 'sometimes|nullable|string|max:3000',
            'years_operating' => 'sometimes|nullable|integer|min:0|max:100',
            'min_job_value' => 'sometimes|nullable|integer|min:0',
            'languages' => 'sometimes|array|max:10',
            'languages.*' => 'string|max:30',
            'coverage' => 'sometimes|array|max:30',
            'coverage.*' => 'string|max:80',
        ]);

        return $this->run(function () use ($op, $d) {
            $this->onboarding->updateProfile($op, $d);

            return $this->status($op);
        });
    }

    public function availability(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $d = $request->validate(['status' => ['required', Rule::in(['accepting', 'busy', 'away'])]]);
        $this->onboarding->setAvailability($op, $d['status']);

        return response()->json(['availability_status' => $d['status']]);
    }

    public function documents(Request $request)
    {
        return response()->json($this->onboarding->documents($this->operatorId($request, self::WRITERS)));
    }

    public function uploadDocument(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $d = $request->validate([
            'doc_type' => 'required|string|max:32',
            'number' => 'nullable|string|max:40',
            'expires_on' => 'nullable|date|after:today',
            'vehicle_id' => 'nullable|string|size:26',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        return $this->run(fn () => ['document' => $this->onboarding->addDocument($op, $d['doc_type'], $d['number'] ?? null, $request->file('file'), $d['expires_on'] ?? null, $d['vehicle_id'] ?? null)], 201);
    }

    public function vehicles(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return response()->json(DB::table('vehicles as v')->join('vehicle_types as t', 't.id', '=', 'v.vehicle_type_id')->where('v.operator_id', $op)->whereNull('v.deleted_at')
            ->orderBy('v.id')->get(['v.public_id', 't.code as vehicle_type', 'v.plate', 'v.make', 'v.model', 'v.year', 'v.colour', 'v.status', 'v.insurance_expires_on', 'v.roadworthy_expires_on']));
    }

    public function addVehicle(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $d = $request->validate([
            'vehicle_type_id' => 'required|integer|exists:vehicle_types,id',
            'plate' => 'required|string|max:24',
            'make' => 'nullable|string|max:40', 'model' => 'nullable|string|max:40',
            'year' => 'nullable|integer|min:1980|max:'.((int) date('Y') + 1), 'colour' => 'nullable|string|max:24',
            'insurance_expires_on' => 'nullable|date', 'roadworthy_expires_on' => 'nullable|date',
        ]);

        return $this->run(fn () => ['vehicle' => $this->onboarding->addVehicle($op, $d)], 201);
    }

    public function rateCards(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return response()->json(DB::table('provider_rate_cards')->where('operator_id', $op)->orderBy('id')
            ->get(['id', 'service_type_id', 'vehicle_type_id', 'city_id', 'base', 'per_km', 'per_kg', 'min_fee', 'negotiable', 'instant_book', 'active']));
    }

    public function saveRateCard(Request $request, ?int $card = null)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $d = $request->validate([
            'service_type_id' => 'required|integer|exists:service_types,id',
            'vehicle_type_id' => 'nullable|integer|exists:vehicle_types,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'base' => 'required|integer|min:0', 'per_km' => 'required|integer|min:0', 'per_kg' => 'nullable|integer|min:0', 'min_fee' => 'nullable|integer|min:0',
            'negotiable' => 'boolean', 'instant_book' => 'boolean', 'active' => 'boolean',
        ]);

        return $this->run(fn () => ['rate_card_id' => $this->onboarding->saveRateCard($op, $d, $card)], $card ? 200 : 201);
    }

    public function deleteRateCard(Request $request, int $card)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return $this->run(function () use ($op, $card) {
            $this->onboarding->deleteRateCard($op, $card);

            return ['deleted' => true];
        });
    }

    public function zones(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $chosen = DB::table('operator_service_areas')->where('operator_id', $op)->pluck('zone_id')->all();

        return response()->json(DB::table('zones')->where('active', true)->where('type', 'service')->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', $op))
            ->orderBy('name')->get(['id', 'city_id', 'name'])->map(fn ($z) => (array) $z + ['selected' => in_array($z->id, $chosen)]));
    }

    public function setServiceAreas(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);
        $d = $request->validate(['zone_ids' => 'required|array|min:1|max:200', 'zone_ids.*' => 'integer']);

        return $this->run(function () use ($op, $d) {
            $this->onboarding->setServiceAreas($op, $d['zone_ids']);

            return $this->status($op);
        });
    }

    public function submit(Request $request)
    {
        $op = $this->operatorId($request, self::WRITERS);

        return $this->run(function () use ($op) {
            $this->onboarding->submit($op);

            return $this->status($op);
        });
    }

    // ----------------------------------------------------------------

    private function status(int $op): array
    {
        $app = DB::table('provider_applications')->where('operator_id', $op)->first(['public_id', 'status', 'submitted_at', 'review_note', 'submissions']);
        $operator = DB::table('operators')->where('id', $op)->first(['public_id', 'type', 'display_name', 'status']);

        return [
            'operator' => $operator,
            'application' => $app,
            'missing' => in_array($app->status, ['draft', 'needs_changes'], true) ? $this->onboarding->missing($op) : [],
            'listed' => (bool) DB::table('provider_profiles')->where('operator_id', $op)->value('listed'),
        ];
    }

    private function run(callable $fn, int $code = 200)
    {
        try {
            return response()->json($fn(), $code);
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }
    }

    private function fail(RuntimeException $e)
    {
        return response()->json(['error' => 'cannot_complete', 'message' => $e->getMessage()], 422);
    }
}
