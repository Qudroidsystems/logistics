<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Eta\ArrivalAlerts;
use Illuminate\Http\Request;

/**
 * When the job giver and the receiver hear that the rider is close: minutes before arrival, a distance, on arrival,
 * and when the rider is running late. Each fires once per stop.
 */
class AlertsController extends WorkspaceController
{
    private const WRITERS = ['owner', 'admin'];

    public function index(Request $request, ArrivalAlerts $alerts)
    {
        [$op, $role] = $this->ctx($request, 'jobs');

        return $this->view('provider.alerts', $request, [
            'cfg' => $alerts->settings($op->id),
            'choices' => ArrivalAlerts::ETA_CHOICES,
            'canEdit' => in_array($role, self::WRITERS, true),
            'pagetitle' => 'Arrival alerts',
        ], 'jobs');
    }

    public function save(Request $request, ArrivalAlerts $alerts)
    {
        [$op, $role] = $this->ctx($request, 'jobs');
        abort_unless(in_array($role, self::WRITERS, true), 403, 'Only an owner or admin can change arrival alerts.');
        $d = $request->validate([
            'eta_minutes' => 'nullable|array', 'eta_minutes.*' => 'integer|in:'.implode(',', ArrivalAlerts::ETA_CHOICES),
            'near_m' => 'nullable|integer|min:100|max:2000', 'delay_minutes' => 'nullable|integer|min:5|max:60',
        ]);
        $alerts->save($op->id, $d['eta_minutes'] ?? [], $request->boolean('near') ? (int) ($d['near_m'] ?? 500) : null,
            $request->boolean('arrived'), $request->boolean('delay') ? (int) ($d['delay_minutes'] ?? 10) : null, $request->boolean('sms'), $request->user()->id);

        return redirect()->route('provider.alerts')->with('success', 'Saved. Deliveries on the way now use these alerts.');
    }
}
