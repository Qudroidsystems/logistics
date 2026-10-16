<?php

namespace App\Http\Controllers;

use App\Modules\Marketing\CampaignService;

/** The link at the foot of every campaign email: one click and no more offers. */
class MarketingUnsubscribeController extends Controller
{
    public function show(int $user)
    {
        CampaignService::setOptIn($user, false);

        return view('marketing.unsubscribed');
    }
}
