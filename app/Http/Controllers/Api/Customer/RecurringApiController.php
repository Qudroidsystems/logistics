<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Booking\InstantQuoteService;
use App\Modules\Recurring\RecurringDeliveryService as Recurring;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Recurring deliveries for the customer app: the same schedules as Account > Recurring deliveries on the website. */
class RecurringApiController extends Controller
{
    public function __construct(private Recurring $recurring) {}

    public function index(Request $request)
    {
        return response()->json([
            'schedules' => $this->recurring->list($request->user()->id),
            'patterns' => Recurring::PATTERNS,
            'days' => Recurring::DAYS,
            'sizes' => collect(InstantQuoteService::sizes())->map(fn ($s, $k) => ['code' => $k, 'label' => $s['label'] ?? ucfirst($k), 'hint' => $s['hint'] ?? null])->values(),
            'lead_hours' => Recurring::LEAD_HOURS,
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $schedule)
    {
        return $this->save($request, $schedule);
    }

    public function status(Request $request, string $schedule)
    {
        $d = $request->validate(['status' => 'required|in:active,paused,ended']);
        try {
            $this->recurring->setStatus($request->user()->id, $schedule, $d['status']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    private function save(Request $request, ?string $id)
    {
        $stop = fn (string $k, bool $required) => [
            "$k" => $required ? 'required|array' : 'nullable|array',
            "$k.lat" => ($required ? 'required' : "required_with:$k").'|numeric|between:-90,90',
            "$k.lng" => ($required ? 'required' : "required_with:$k").'|numeric|between:-180,180',
            "$k.line1" => ($required ? 'required' : "required_with:$k").'|string|max:200',
            "$k.contact_name" => ($required ? 'required' : "required_with:$k").'|string|max:120',
            "$k.contact_phone" => ($required ? 'required' : "required_with:$k").'|string|max:24',
            "$k.instructions" => 'nullable|string|max:300',
        ];
        $d = $request->validate(array_merge([
            'name' => 'required|string|max:80',
            'dropoff_centre' => 'nullable|string|size:26',
            'size' => ['required', Rule::in(array_keys(InstantQuoteService::sizes()))], 'vehicle' => 'nullable|string|max:24', 'fragile' => 'nullable|boolean',
            'description' => 'required|string|max:200', 'pattern' => ['required', Rule::in(array_keys(Recurring::PATTERNS))],
            'days' => 'required_if:pattern,weekly|nullable|array', 'days.*' => 'integer|between:1,7', 'time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'starts_on' => 'required|date|after_or_equal:today', 'ends_on' => 'nullable|date|after_or_equal:starts_on',
        ], $stop('pickup', false), $stop('dropoff', true)));
        $only = fn (?array $s) => $s ? ['lat' => (float) $s['lat'], 'lng' => (float) $s['lng'], 'line1' => $s['line1'], 'contact_name' => $s['contact_name'],
            'contact_phone' => $s['contact_phone'], 'instructions' => $s['instructions'] ?? null] : null;
        try {
            $pid = $this->recurring->save($request->user()->id, [
                'name' => $d['name'], 'dropoff_centre' => $d['dropoff_centre'] ?? null,
                'pickup' => empty($d['dropoff_centre']) ? $only($d['pickup'] ?? null) : null, 'dropoff' => $only($d['dropoff']),
                'size' => $d['size'], 'vehicle' => $d['vehicle'] ?? null, 'fragile' => (bool) ($d['fragile'] ?? false), 'description' => $d['description'],
                'pattern' => $d['pattern'], 'days' => $d['days'] ?? [], 'time' => $d['time'], 'starts_on' => $d['starts_on'], 'ends_on' => $d['ends_on'] ?? null,
            ], $id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $row = collect($this->recurring->list($request->user()->id))->firstWhere('id', $pid);

        return response()->json(['schedule' => $row], $id ? 200 : 201);
    }
}
