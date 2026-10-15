<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Api\Customer\BookingController as Api;
use App\Http\Controllers\Controller;
use App\Modules\Booking\AgreementDiscounts;
use App\Modules\Booking\Receipts;
use App\Modules\Booking\RequestAttachments;
use App\Modules\Booking\SavedAddresses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Web pages for the booking extras; each action reuses the JSON API so the rules live in one place. */
class BookingPagesController extends Controller
{
    public function __construct(private Api $api)
    {
    }

    public function addresses(Request $request, SavedAddresses $svc)
    {
        return view('account.addresses', [
            'addresses' => $svc->list($request->user()->id),
            'cities' => DB::table('cities')->orderBy('name')->selectRaw('id, name, ST_Y(centre::geometry) AS lat, ST_X(centre::geometry) AS lng')->get(),
            'pagetitle' => 'Saved addresses',
        ]);
    }

    public function saveAddress(Request $request, SavedAddresses $svc)
    {
        return $this->done($this->api->saveAddress($request, $svc), 'Address saved.');
    }

    public function deleteAddress(Request $request, SavedAddresses $svc, string $address)
    {
        return $this->done($this->api->deleteAddress($request, $svc, $address), 'Address removed.');
    }

    public function upload(Request $request, RequestAttachments $svc, string $serviceRequest)
    {
        return $this->done($this->api->upload($request, $svc, $serviceRequest), 'File added. Providers can see it now.');
    }

    public function deleteFile(Request $request, RequestAttachments $svc, string $attachment)
    {
        return $this->done($this->api->deleteAttachment($request, $svc, $attachment), 'File removed.');
    }

    public function applyPromo(Request $request, AgreementDiscounts $svc, string $agreement)
    {
        return $this->done($this->api->applyPromo($request, $svc, $agreement), 'Promo code applied.');
    }

    public function removePromo(Request $request, AgreementDiscounts $svc, string $agreement)
    {
        return $this->done($this->api->removePromo($request, $svc, $agreement), 'Promo code removed.');
    }

    public function receipt(Request $request, Receipts $svc, string $shipment)
    {
        $res = $this->api->receipt($request, $svc, $shipment);

        return $res->getStatusCode() >= 400 ? back()->with('error', $res->getData(true)['message'] ?? 'No receipt yet.') : $res;
    }

    private function done($res, string $ok)
    {
        if ($res->getStatusCode() >= 400) {
            return back()->withInput()->with('error', $res->getData(true)['message'] ?? 'That could not be done.');
        }

        return back()->with('success', $ok);
    }
}
