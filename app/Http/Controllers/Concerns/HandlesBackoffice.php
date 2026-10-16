<?php

namespace App\Http\Controllers\Concerns;

use App\Modules\Backoffice\JobEditService;
use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Backoffice\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Form handling shared by the staff console and the provider workspace for manual bookings, job edits and reports.
 * Amounts are typed in naira and weights in kilograms; the services only ever see kobo and grams.
 */
trait HandlesBackoffice
{
    protected function bookingFormData(): array
    {
        return [
            'serviceTypes' => DB::table('service_types')->where('active', true)->orderBy('id')->get(['id', 'name']),
            'cities' => DB::table('cities')->orderBy('name')->selectRaw('id, name, ST_Y(centre::geometry) AS lat, ST_X(centre::geometry) AS lng')->get(),
            'vehicleTypes' => DB::table('vehicle_types')->where('active', true)->orderBy('id')->get(['id', 'name']),
            'payments' => ManualBookingService::PAYMENTS,
        ];
    }

    /** Validates the booking form and turns it into what ManualBookingService::book expects. */
    protected function bookingInput(Request $request, bool $forPrice = false): array
    {
        $stop = fn (string $k) => [
            "{$k}.line1" => 'required|string|max:255', "{$k}.lat" => 'required|numeric|between:-90,90', "{$k}.lng" => 'required|numeric|between:-180,180',
            "{$k}.landmark" => 'nullable|string|max:255', "{$k}.contact_name" => 'nullable|string|max:120', "{$k}.contact_phone" => 'nullable|string|max:24',
            "{$k}.instructions" => 'nullable|string|max:500',
        ];
        $rules = $stop('pickup') + $stop('dropoff') + ['dropoff.pickup_point' => 'nullable|string|max:40'] + [
            'service_type_id' => 'required|integer|exists:service_types,id', 'city_id' => 'required|integer|exists:cities,id',
            'vehicle_type_id' => 'nullable|integer|exists:vehicle_types,id', 'weight_kg' => 'nullable|numeric|min:0|max:50000',
            'cod_naira' => 'nullable|numeric|min:0|max:100000000',
        ];
        if (! $forPrice) {
            $rules += [
                'customer.name' => 'nullable|string|max:120', 'customer.phone' => 'nullable|string|max:24', 'customer.email' => 'nullable|email:rfc|max:190',
                'items' => 'nullable|string|max:2000', 'price_naira' => 'nullable|numeric|min:1|max:100000000',
                'payment' => 'required|in:'.implode(',', array_keys(ManualBookingService::PAYMENTS)),
                'driver_user_id' => 'nullable|integer', 'note' => 'nullable|string|max:500', 'business_account' => 'nullable|required_if:payment,account|string|max:40',
            ];
        }
        $d = $request->validate($rules, [
            'pickup.lat.required' => 'Mark the pickup on the map.', 'dropoff.lat.required' => 'Mark the drop-off on the map.',
            'pickup.lng.required' => 'Mark the pickup on the map.', 'dropoff.lng.required' => 'Mark the drop-off on the map.',
        ]);

        $weight = isset($d['weight_kg']) && $d['weight_kg'] !== null ? (int) round(((float) $d['weight_kg']) * 1000) : 0;
        $items = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($d['items'] ?? '')))));
        $packages = array_map(fn ($t) => ['description' => mb_substr($t, 0, 255)], $items ?: ['Parcel']);
        if ($weight > 0) {
            // The weight typed is for the whole booking; it is spread over the parcels so the labels add up.
            $each = intdiv($weight, count($packages));
            foreach ($packages as $i => $p) {
                $packages[$i]['weight_g'] = $i === 0 ? $weight - $each * (count($packages) - 1) : $each;
            }
        }

        return [
            'customer' => $d['customer'] ?? [], 'service_type_id' => (int) $d['service_type_id'], 'city_id' => (int) $d['city_id'],
            'vehicle_type_id' => isset($d['vehicle_type_id']) ? (int) $d['vehicle_type_id'] : null, 'weight_g' => $weight,
            'pickup' => $d['pickup'], 'dropoff' => $d['dropoff'], 'packages' => $packages,
            'price' => isset($d['price_naira']) && $d['price_naira'] !== null ? (int) round(((float) $d['price_naira']) * 100) : null,
            'payment' => $d['payment'] ?? 'link', 'driver_user_id' => isset($d['driver_user_id']) ? (int) $d['driver_user_id'] : null,
            'note' => $d['note'] ?? null, 'business_account' => $d['business_account'] ?? null,
            'cod_amount' => \App\Modules\Cod\CodService::kobo($d['cod_naira'] ?? null),
        ];
    }

    /** JSON for the "work out the price" button. */
    protected function priceJson(Request $request, int $operatorId)
    {
        try {
            $p = app(ManualBookingService::class)->price($operatorId, $this->bookingInput($request, true));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['total' => $p['total'], 'naira' => round($p['total'] / 100, 2), 'distance_km' => round($p['distance_m'] / 1000, 1), 'minutes' => (int) ceil($p['duration_s'] / 60)]);
    }

    protected function editInput(Request $request): array
    {
        $rules = [];
        foreach (['pickup', 'dropoff'] as $k) {
            $rules += [
                "{$k}.line1" => 'sometimes|required|string|max:255', "{$k}.landmark" => 'nullable|string|max:255', "{$k}.contact_name" => 'nullable|string|max:120',
                "{$k}.contact_phone" => 'nullable|string|max:24', "{$k}.instructions" => 'nullable|string|max:500',
                "{$k}.lat" => 'nullable|numeric|between:-90,90', "{$k}.lng" => 'nullable|numeric|between:-180,180',
            ];
        }
        $rules += ['packages' => 'nullable|array', 'packages.*.description' => 'nullable|string|max:255', 'packages.*.weight_kg' => 'nullable|numeric|min:0|max:50000'];

        return $request->validate($rules);
    }

    /** Runs a job edit and turns the result into a redirect with a message. */
    protected function saveEdit(Request $request, int $shipmentId, string $actorType, string $to)
    {
        try {
            $changes = app(JobEditService::class)->update($shipmentId, $this->editInput($request), $request->user()->id, $actorType);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect($to)->with('success', $changes ? 'Job updated. The driver has been told.' : 'Nothing was changed.');
    }

    /** Date range from the report filters: from/to as dates, defaulting to the last 30 days. @return array{0:\Carbon\CarbonImmutable,1:\Carbon\CarbonImmutable} */
    protected function reportRange(Request $request): array
    {
        $d = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $tz = config('app.timezone', 'Africa/Lagos');
        $to = isset($d['to']) ? \Carbon\CarbonImmutable::parse($d['to'], $tz)->endOfDay() : \Carbon\CarbonImmutable::now($tz)->endOfDay();
        $from = isset($d['from']) ? \Carbon\CarbonImmutable::parse($d['from'], $tz)->startOfDay() : $to->subDays(29)->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }
        if ($from->diffInDays($to) > 731) {
            $from = $to->subDays(731)->startOfDay();
        }

        return [$from, $to];
    }

    /** Streams one report as Excel or PDF. */
    protected function exportReport(string $report, string $format, ReportService $reports, ?int $operatorId, array $range, string $scopeLabel)
    {
        abort_unless(isset(ReportService::REPORTS[$report]) && in_array($format, ['xlsx', 'pdf'], true), 404);
        $table = $reports->table($report, $operatorId, $range[0], $range[1]);
        $name = str_replace('_', '-', $report).'-'.$range[0]->format('Ymd').'-'.$range[1]->format('Ymd');

        if ($format === 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Modules\Backoffice\ReportExport($table), "{$name}.xlsx");
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('backoffice.report-pdf', [
            'table' => $table, 'title' => ReportService::REPORTS[$report], 'scope' => $scopeLabel, 'from' => $range[0], 'to' => $range[1],
        ])->setPaper('a4', count($table['headings']) > 6 ? 'landscape' : 'portrait')->download("{$name}.pdf");
    }
}
