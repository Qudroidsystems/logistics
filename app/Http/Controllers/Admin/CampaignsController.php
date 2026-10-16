<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesCampaigns;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** The platform's own campaigns, to every customer who agreed to offers. */
class CampaignsController extends Controller
{
    use HandlesCampaigns;

    public function index(Request $request)
    {
        return view('marketing.campaigns', $this->campaignData(null, $request) + ['prefix' => 'ops.campaigns', 'reviews' => null, 'scope' => 'customers who agreed to offers']);
    }

    public function store(Request $request)
    {
        return $this->saveCampaign(null, $request, 'ops.campaigns');
    }

    public function update(Request $request, int $campaign)
    {
        return $this->saveCampaign(null, $request, 'ops.campaigns', $campaign);
    }

    public function action(Request $request, int $campaign, string $action)
    {
        return $this->campaignAction(null, $request, $campaign, $action);
    }
}
