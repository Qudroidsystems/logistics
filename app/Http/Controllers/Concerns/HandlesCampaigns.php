<?php

namespace App\Http\Controllers\Concerns;

use App\Modules\Marketing\CampaignService;
use Illuminate\Http\Request;
use RuntimeException;

/** Campaign pages shared by the platform (ops) and providers; the operator id scopes everything (null = platform). */
trait HandlesCampaigns
{
    protected function campaignData(?int $operatorId, Request $request): array
    {
        $svc = app(CampaignService::class);
        $edit = $request->query('edit') ? collect($svc->list($operatorId))->firstWhere('id', (int) $request->query('edit')) : null;
        $counts = [];
        foreach (array_keys(CampaignService::AUDIENCES) as $a) {
            $counts[$a] = ['email' => $svc->count($operatorId, $a, 'email'), 'sms' => $svc->count($operatorId, $a, 'sms')];
        }

        return ['campaigns' => $svc->list($operatorId), 'audiences' => CampaignService::AUDIENCES, 'counts' => $counts, 'edit' => $edit, 'pagetitle' => 'Campaigns'];
    }

    protected function saveCampaign(?int $operatorId, Request $request, string $route, ?int $id = null)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120', 'channel' => 'required|in:email,sms', 'subject' => 'nullable|string|max:150',
            'body' => 'required|string|max:'.($request->input('channel') === 'sms' ? 480 : 10000), 'audience' => 'required|string', 'promo_code' => 'nullable|string|max:32',
        ]);
        try {
            app(CampaignService::class)->save($operatorId, $d, $request->user()->id, $id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route($route)->with('success', 'Campaign saved as a draft. Send yourself a test, then send it.');
    }

    protected function campaignAction(?int $operatorId, Request $request, int $id, string $action)
    {
        $svc = app(CampaignService::class);
        try {
            $msg = match ($action) {
                'test' => 'Test sent to '.$svc->test($operatorId, $id, $request->user()).'.',
                'send' => $this->scheduleCampaign($svc, $operatorId, $request, $id),
                'cancel' => tap('Campaign stopped.', fn () => $svc->cancel($operatorId, $id)),
            };
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $msg);
    }

    private function scheduleCampaign(CampaignService $svc, ?int $operatorId, Request $request, int $id): string
    {
        $d = $request->validate(['send_at' => 'nullable|date|after:now']);
        $svc->schedule($operatorId, $id, $d['send_at'] ?? null);

        return empty($d['send_at']) ? 'Sending now. The numbers below update as it goes.' : 'Scheduled for '.\Illuminate\Support\Carbon::parse($d['send_at'])->format('d M H:i').'.';
    }
}
