<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Concerns\HandlesCampaigns;
use App\Modules\Marketing\ReviewRequestService;
use Illuminate\Http\Request;

/** A provider's campaigns to its own customers, and Google review requests. Run by owners and admins. */
class MarketingController extends WorkspaceController
{
    use HandlesCampaigns;

    public function index(Request $request, ReviewRequestService $reviews)
    {
        [$op] = $this->ctx($request, 'marketing');

        return $this->view('marketing.campaigns', $request, $this->campaignData((int) $op->id, $request) + [
            'prefix' => 'provider.campaigns', 'reviews' => ReviewRequestService::settings((int) $op->id), 'reviewStats' => $reviews->stats((int) $op->id),
            'scope' => 'your customers who agreed to offers',
        ], 'marketing');
    }

    public function store(Request $request)
    {
        [$op] = $this->ctx($request, 'marketing');

        return $this->saveCampaign((int) $op->id, $request, 'provider.campaigns');
    }

    public function update(Request $request, int $campaign)
    {
        [$op] = $this->ctx($request, 'marketing');

        return $this->saveCampaign((int) $op->id, $request, 'provider.campaigns', $campaign);
    }

    public function action(Request $request, int $campaign, string $action)
    {
        [$op] = $this->ctx($request, 'marketing');

        return $this->campaignAction((int) $op->id, $request, $campaign, $action);
    }

    public function saveReviews(Request $request)
    {
        [$op] = $this->ctx($request, 'marketing');
        $d = $request->validate([
            'enabled' => 'required|in:0,1', 'url' => 'nullable|required_if:enabled,1|url:https|max:300',
            'delay_hours' => 'required|integer|min:0|max:168', 'sms' => 'nullable|in:0,1',
        ], ['url.required_if' => 'Paste your Google review link to switch this on.']);
        ReviewRequestService::save((int) $op->id, ['enabled' => $d['enabled'] === '1', 'url' => (string) ($d['url'] ?? ''), 'delay_hours' => (int) $d['delay_hours'], 'sms' => ($d['sms'] ?? '0') === '1'], $request->user()->id);

        return back()->with('success', $d['enabled'] === '1' ? 'Customers will be asked for a Google review after each finished delivery.' : 'Review requests are off.');
    }
}
