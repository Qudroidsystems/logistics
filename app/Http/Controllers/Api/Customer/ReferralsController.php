<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\ReferralService;
use Illuminate\Http\Request;

/** The customer's invite code and how their invites went. */
class ReferralsController extends Controller
{
    public function show(Request $request, ReferralService $referrals)
    {
        return response()->json($referrals->forCustomer($request->user()->id));
    }
}
