<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\PhoneVerificationController;
use Illuminate\Http\Request;

/**
 * "My phone number" for customers, providers and drivers. The same page serves all three areas (route names
 * account.phone, driver.phone) so each keeps its own sidebar (providers use the account one); the rules live in the API controller.
 */
class PhonePageController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        $area = $this->area($request);

        return view('phone.show', [
            'u' => $u, 'area' => $area, 'pagetitle' => 'Phone number',
            'verified' => (bool) $u->phone_verified_at,
            'back' => route($area === 'driver' ? 'driver.home' : 'account.dashboard'),
            'smsLive' => config('services.sms.driver') === 'termii',
        ]);
    }

    public function save(Request $request)
    {
        return $this->reply($request, app(PhoneVerificationController::class)->set($request), 'Number saved. Now send yourself a code to confirm it.');
    }

    public function send(Request $request)
    {
        return $this->reply($request, app(PhoneVerificationController::class)->send($request), 'Code sent. It lasts 10 minutes.');
    }

    public function verify(Request $request)
    {
        return $this->reply($request, app(PhoneVerificationController::class)->verify($request), 'Your phone number is confirmed.');
    }

    private function reply(Request $request, $json, string $ok)
    {
        $back = redirect()->route($this->area($request).'.phone');
        if ($json->getStatusCode() >= 400) {
            $d = $json->getData(true);

            return $back->with('error', $d['message'] ?? 'That did not work. Please try again.');
        }

        return $back->with('success', $ok);
    }

    private function area(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_starts_with($name, 'driver.') ? 'driver' : 'account';
    }
}
