<?php

namespace App\Http\Controllers;

use App\Models\BrandSetting;
use App\Modules\DropOff\DropOffService;
use App\Support\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Become a partner": a store applies to be a drop-off point. Staff review it under Operations > Drop-off centres. */
class PartnerApplicationController extends Controller
{
    public const BUSINESS_TYPES = ['convenience' => 'Convenience store', 'pharmacy' => 'Pharmacy', 'print_ship' => 'Print or shipping shop', 'grocery' => 'Grocery', 'cafe' => 'Café', 'other' => 'Other'];

    public function show()
    {
        return view('public.partners', ['brand' => BrandSetting::forApp('customer'), 'types' => self::BUSINESS_TYPES, 'subdivisions' => Region::subdivisions(), 'sent' => session('partner_sent')]);
    }

    public function store(Request $request, DropOffService $dropoffs)
    {
        // A filled-in hidden field is a bot.
        if (filled($request->input('website'))) {
            return redirect()->route('partners')->with('partner_sent', true);
        }
        $d = $request->validate([
            'business_name' => 'required|string|max:120', 'contact_name' => 'required|string|max:120', 'email' => 'required|email|max:160',
            'phone' => 'required|string|max:24', 'line1' => 'required|string|max:200', 'city' => 'required|string|max:80',
            'subdivision' => ['nullable', Rule::in(array_keys(Region::subdivisions()))], 'postal_code' => 'nullable|string|max:12',
            'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180',
            'opening_hours' => 'nullable|string|max:160', 'business_type' => ['nullable', Rule::in(array_keys(self::BUSINESS_TYPES))],
            'message' => 'nullable|string|max:1000', 'consent' => 'accepted',
        ], ['consent.accepted' => 'Tick the box so we can contact you about your application.']);
        $dropoffs->apply($d);

        return redirect()->route('partners')->with('partner_sent', true);
    }
}
