<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Modules\Booking\InstantQuoteService;
use App\Modules\Recurring\RecurringDeliveryService as Recurring;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Booking from the account (business portal): the instant price form, and recurring deliveries. */
class BusinessBookingController extends Controller
{
    public function book(InstantQuoteService $instant)
    {
        return view('account.book', ['brand' => BrandSetting::forApp('customer'), 'options' => $instant->options(), 'embed' => false, 'pagetitle' => 'Book a delivery']);
    }

    public function recurring(Request $request, Recurring $recurring, InstantQuoteService $instant)
    {
        return view('account.recurring', [
            'rows' => $recurring->list($request->user()->id), 'options' => $instant->options(), 'patterns' => Recurring::PATTERNS, 'days' => Recurring::DAYS,
            'me' => $request->user(), 'pagetitle' => 'Recurring deliveries',
        ]);
    }

    public function store(Request $request, Recurring $recurring)
    {
        $d = $request->validate([
            'name' => 'required|string|max:80', 'from' => 'required|in:address,store',
            'dropoff_centre' => 'required_if:from,store|nullable|string|size:26',
            'pickup_lat' => 'required_if:from,address|nullable|numeric|between:-90,90', 'pickup_lng' => 'required_if:from,address|nullable|numeric|between:-180,180',
            'pickup_line1' => 'required_if:from,address|nullable|string|max:200', 'pickup_name' => 'required_if:from,address|nullable|string|max:120',
            'pickup_phone' => 'required_if:from,address|nullable|string|max:24', 'pickup_instructions' => 'nullable|string|max:300',
            'dropoff_lat' => 'required|numeric|between:-90,90', 'dropoff_lng' => 'required|numeric|between:-180,180', 'dropoff_line1' => 'required|string|max:200',
            'dropoff_name' => 'required|string|max:120', 'dropoff_phone' => 'required|string|max:24', 'dropoff_instructions' => 'nullable|string|max:300',
            'size' => ['required', Rule::in(array_keys(InstantQuoteService::sizes()))], 'vehicle' => 'nullable|string|max:24', 'fragile' => 'nullable|boolean',
            'description' => 'required|string|max:200', 'pattern' => ['required', Rule::in(array_keys(Recurring::PATTERNS))],
            'days' => 'required_if:pattern,weekly|nullable|array', 'days.*' => 'integer|between:1,7', 'time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'starts_on' => 'required|date|after_or_equal:today', 'ends_on' => 'nullable|date|after_or_equal:starts_on',
        ], ['pickup_lat.required_if' => 'Choose the pickup address from the list.', 'dropoff_lat.required' => 'Choose the drop-off address from the list.']);
        $store = $d['from'] === 'store';
        try {
            $recurring->save($request->user()->id, [
                'name' => $d['name'], 'dropoff_centre' => $store ? $d['dropoff_centre'] : null,
                'pickup' => $store ? null : ['lat' => (float) $d['pickup_lat'], 'lng' => (float) $d['pickup_lng'], 'line1' => $d['pickup_line1'], 'contact_name' => $d['pickup_name'],
                    'contact_phone' => $d['pickup_phone'], 'instructions' => $d['pickup_instructions'] ?? null],
                'dropoff' => ['lat' => (float) $d['dropoff_lat'], 'lng' => (float) $d['dropoff_lng'], 'line1' => $d['dropoff_line1'], 'contact_name' => $d['dropoff_name'],
                    'contact_phone' => $d['dropoff_phone'], 'instructions' => $d['dropoff_instructions'] ?? null],
                'size' => $d['size'], 'vehicle' => $d['vehicle'] ?? null, 'fragile' => (bool) ($d['fragile'] ?? false), 'description' => $d['description'],
                'pattern' => $d['pattern'], 'days' => $d['days'] ?? [], 'time' => $d['time'], 'starts_on' => $d['starts_on'], 'ends_on' => $d['ends_on'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('account.recurring')->with('success', 'Schedule saved. We book each trip the day before and pay it from your wallet (or your plan).');
    }

    public function status(Request $request, string $schedule, Recurring $recurring)
    {
        $d = $request->validate(['status' => 'required|in:active,paused,ended']);
        try {
            $recurring->setStatus($request->user()->id, $schedule, $d['status']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', ['active' => 'Schedule on.', 'paused' => 'Schedule paused.', 'ended' => 'Schedule deleted.'][$d['status']]);
    }
}
