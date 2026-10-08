<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Api\Provider\ProviderOnboardingController as Api;
use App\Modules\Providers\ProviderOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Getting a provider account ready: sign up as a provider, then profile, documents, vehicles, rate cards,
 * service areas and submitting for review. Pages call the same code as the provider JSON API.
 * Only the owner and admins may change these (same as the API).
 */
class SetupController extends WorkspaceController
{
    private function api(): Api
    {
        return app(Api::class);
    }

    // ---------------------------------------------------------------- sign up as a provider

    public function start(Request $request)
    {
        if ($this->isProvider($request)) {
            return redirect()->route('provider.dashboard');
        }

        return view('provider.start', [
            'types' => ['company' => 'A delivery company', 'independent_driver' => 'An independent rider or driver', 'market_shopper' => 'A market shopper'],
            'cities' => DB::table('cities')->orderBy('name')->get(['id', 'name']),
            'nav' => [], 'role' => 'member', 'pagetitle' => 'Become a provider',
        ]);
    }

    public function register(Request $request)
    {
        $res = $this->api()->register($request);
        if ($res->getStatusCode() >= 400) {
            return back()->withInput()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }
        // The service makes the user the owner and points their account at the new provider.
        return redirect()->route('provider.onboarding')->with('success', 'Your provider account is created. Finish the steps below and submit it for review.');
    }

    // ---------------------------------------------------------------- the setup page

    public function onboarding(Request $request, ProviderOnboardingService $svc)
    {
        [$op, $role] = $this->ctx($request);
        abort_unless(in_array($role, ['owner', 'admin'], true), 403, 'Only the owner or an admin can change the setup.');

        $app = DB::table('provider_applications')->where('operator_id', $op->id)->first(['status', 'review_note', 'submitted_at']);
        $cat = $this->api()->catalog()->getData(true);
        $profile = DB::table('provider_profiles')->where('operator_id', $op->id)->first();
        $zones = $this->api()->zones($request)->getData(true);

        return $this->view('provider.onboarding', $request, [
            'app' => $app, 'missing' => $svc->missing($op->id),
            'required' => ProviderOnboardingService::REQUIRED_DOCS[$op->type] ?? [],
            'docs' => $svc->documents($op->id), 'profile' => $profile,
            'vehicles' => $this->api()->vehicles($request)->getData(true),
            'cards' => $this->api()->rateCards($request)->getData(true),
            'serviceTypes' => collect($cat['service_types'])->keyBy('id'), 'vehicleTypes' => $cat['vehicle_types'], 'cities' => $cat['cities'], 'zones' => $zones,
            'locked' => in_array($app->status ?? 'draft', ['submitted', 'approved'], true),
            'pagetitle' => 'Profile and setup',
        ]);
    }

    public function saveProfile(Request $request)
    {
        $this->ctx($request);
        // Languages and coverage arrive as comma lists in the form.
        foreach (['languages', 'coverage'] as $k) {
            if ($request->has($k)) {
                $request->merge([$k => array_values(array_filter(array_map('trim', explode(',', (string) $request->input($k)))))]);
            }
        }
        if ($request->filled('min_job_value_naira')) {
            $request->merge(['min_job_value' => (int) round(((float) $request->input('min_job_value_naira')) * 100)]);
        }

        return $this->back($this->api()->updateProfile($request), 'Profile saved.');
    }

    public function availability(Request $request)
    {
        $this->ctx($request);

        return $this->back($this->api()->availability($request), 'Availability updated.');
    }

    public function uploadDocument(Request $request)
    {
        $this->ctx($request);

        return $this->back($this->api()->uploadDocument($request), 'Document uploaded. Our team will review it.');
    }

    public function addVehicle(Request $request)
    {
        $this->ctx($request);

        return $this->back($this->api()->addVehicle($request), 'Vehicle added. Now upload its registration under Documents.');
    }

    public function saveRateCard(Request $request)
    {
        $this->ctx($request);
        // The form speaks naira; the API only ever sees whole kobo.
        $kobo = fn ($k) => $request->filled($k) ? (int) round(((float) $request->input($k)) * 100) : null;
        $request->merge([
            'base' => $kobo('base_naira') ?? 0, 'per_km' => $kobo('per_km_naira') ?? 0, 'per_kg' => $kobo('per_kg_naira'), 'min_fee' => $kobo('min_fee_naira'),
            'negotiable' => $request->boolean('negotiable'), 'instant_book' => $request->boolean('instant_book'), 'active' => true,
        ]);

        return $this->back($this->api()->saveRateCard($request, null), 'Rate card saved.');
    }

    public function deleteRateCard(Request $request, int $card)
    {
        $this->ctx($request);

        return $this->back($this->api()->deleteRateCard($request, $card), 'Rate card removed.');
    }

    public function saveAreas(Request $request)
    {
        $this->ctx($request);
        $request->merge(['zone_ids' => array_map('intval', (array) $request->input('zone_ids', []))]);

        return $this->back($this->api()->setServiceAreas($request), 'Service areas saved.');
    }

    public function submit(Request $request)
    {
        $this->ctx($request);

        return $this->back($this->api()->submit($request), 'Submitted. We will review your application and email you.', route('provider.dashboard'));
    }
}
