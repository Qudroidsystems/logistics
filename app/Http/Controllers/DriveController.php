<?php

namespace App\Http\Controllers;

use App\Models\BrandSetting;
use App\Modules\Drivers\DriverApplicationService as Apps;
use App\Support\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/** "Drive with us": the public application form, the terms, and a page to check on an application. */
class DriveController extends Controller
{
    public function show()
    {
        $codes = (array) config('booking.vehicles');

        return view('public.drive', [
            'brand' => BrandSetting::forApp('customer'), 'subdivisions' => Region::subdivisions(), 'experience' => Apps::EXPERIENCE, 'availability' => Apps::AVAILABILITY,
            'vehicles' => DB::table('vehicle_types')->where('active', true)->when($codes, fn ($q) => $q->whereIn('code', $codes))->orderBy('max_weight_g')->pluck('name', 'code'),
            'open' => Apps::operatorId() !== null,
        ]);
    }

    public function terms()
    {
        return view('public.drive-terms', ['brand' => BrandSetting::forApp('customer'), 'version' => Apps::TERMS_VERSION]);
    }

    public function store(Request $request, Apps $apps)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('drive');
        }
        $file = 'file|mimes:jpg,jpeg,png,heic,webp,pdf|max:8192';
        $d = $request->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email|max:160', 'phone' => 'required|string|max:24',
            'city' => 'required|string|max:80', 'subdivision' => ['nullable', Rule::in(array_keys(Region::subdivisions()))], 'postal_code' => 'nullable|string|max:12',
            'experience' => ['required', Rule::in(array_keys(Apps::EXPERIENCE))], 'experience_notes' => 'nullable|string|max:500',
            'availability' => 'nullable|array', 'availability.*' => Rule::in(array_keys(Apps::AVAILABILITY)),
            'has_vehicle' => 'required|in:yes,no',
            'vehicle_type' => 'required_if:has_vehicle,yes|nullable|string|exists:vehicle_types,code', 'vehicle_make' => 'required_if:has_vehicle,yes|nullable|string|max:40',
            'vehicle_model' => 'required_if:has_vehicle,yes|nullable|string|max:40', 'vehicle_year' => 'required_if:has_vehicle,yes|nullable|integer|min:1990|max:'.(date('Y') + 1),
            'plate' => 'required_if:has_vehicle,yes|nullable|string|max:12',
            'licence_class' => 'required|string|max:12', 'licence_number' => 'required|string|max:30', 'licence_expires_on' => 'required|date|after:today',
            'licence_file' => "required|$file",
            'insurance_company' => 'required_if:has_vehicle,yes|nullable|string|max:80', 'insurance_policy' => 'required_if:has_vehicle,yes|nullable|string|max:40',
            'insurance_expires_on' => 'required_if:has_vehicle,yes|nullable|date|after:today', 'insurance_file' => "required_if:has_vehicle,yes|nullable|$file",
            'registration_file' => "required_if:has_vehicle,yes|nullable|$file", 'abstract_file' => "nullable|$file",
            'sms_consent' => 'nullable|boolean', 'background_check_consent' => 'accepted', 'terms' => 'accepted',
        ], [
            'licence_expires_on.after' => 'Your licence has expired.', 'insurance_expires_on.after' => 'Your insurance has expired.',
            'background_check_consent.accepted' => 'We need your permission to check your licence and driving record.',
            'terms.accepted' => 'Please read and accept the driver terms.', '*.required_if' => 'This is needed when you drive your own vehicle.',
        ]);
        if ($d['has_vehicle'] === 'no') {
            $d = array_merge($d, array_fill_keys(['vehicle_type', 'vehicle_make', 'vehicle_model', 'vehicle_year', 'plate', 'insurance_company', 'insurance_policy', 'insurance_expires_on'], null));
        }
        try {
            $id = $apps->apply($d, [
                'licence' => $request->file('licence_file'), 'insurance' => $d['has_vehicle'] === 'yes' ? $request->file('insurance_file') : null,
                'registration' => $d['has_vehicle'] === 'yes' ? $request->file('registration_file') : null, 'abstract' => $request->file('abstract_file'),
            ], $request->ip(), $request->user()?->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('drive.status', $id);
    }

    public function status(string $application)
    {
        $a = DB::table('driver_applications')->where('public_id', $application)->first(['name', 'status', 'created_at', 'decided_at', 'decision_note']);
        abort_unless($a, 404);

        return view('public.drive-status', ['brand' => BrandSetting::forApp('customer'), 'a' => $a]);
    }
}
