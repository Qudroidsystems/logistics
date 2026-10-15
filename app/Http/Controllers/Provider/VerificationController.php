<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Verification\VerificationPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The provider's standard proof-of-delivery checks: one default, plus optional ones per service type.
 * Customers see them on every offer, and accepting an offer freezes them into the agreement, so a change here
 * only affects jobs agreed afterwards.
 */
class VerificationController extends WorkspaceController
{
    private const WRITERS = ['owner', 'admin'];

    public function index(Request $request, VerificationPolicy $policy)
    {
        [$op, $role] = $this->ctx($request, 'jobs');

        return $this->view('provider.verification', $request, [
            'saved' => $policy->saved($op->id),
            'default' => $policy->forProvider($op->id),
            'serviceTypes' => DB::table('service_types')->where('active', true)->orderBy('id')->pluck('name', 'id'),
            'pickupOptions' => array_intersect_key(VerificationPolicy::LABELS, array_flip(VerificationPolicy::PICKUP)),
            'dropoffOptions' => array_intersect_key(VerificationPolicy::LABELS, array_flip(VerificationPolicy::DROPOFF)),
            'canEdit' => in_array($role, self::WRITERS, true),
            'pagetitle' => 'Delivery checks',
        ], 'jobs');
    }

    public function save(Request $request, VerificationPolicy $policy)
    {
        [$op, $role] = $this->ctx($request, 'jobs');
        abort_unless(in_array($role, self::WRITERS, true), 403, 'Only an owner or admin can change delivery checks.');
        $d = $request->validate([
            'service_type_id' => 'nullable|integer|exists:service_types,id',
            'pickup' => 'nullable|array', 'pickup.*' => 'string', 'dropoff' => 'nullable|array', 'dropoff.*' => 'string',
        ]);
        try {
            $policy->save($op->id, isset($d['service_type_id']) ? (int) $d['service_type_id'] : null, $d['pickup'] ?? [], $d['dropoff'] ?? [], $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.verification')->with('success', 'Saved. New offers use these checks; jobs already agreed keep theirs.');
    }

    public function remove(Request $request, VerificationPolicy $policy, int $serviceType)
    {
        [$op, $role] = $this->ctx($request, 'jobs');
        abort_unless(in_array($role, self::WRITERS, true), 403, 'Only an owner or admin can change delivery checks.');
        $policy->remove($op->id, $serviceType);

        return back()->with('success', 'Removed. That service now uses your default checks.');
    }
}
