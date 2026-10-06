<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Providers\ProviderOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Staff side of provider onboarding. Permissions are enforced on the routes. */
class AdminProviderController extends Controller
{
    public function __construct(private ProviderOnboardingService $onboarding)
    {
    }

    public function applications(Request $request)
    {
        $status = $request->query('status', 'submitted');

        return response()->json(DB::table('provider_applications as a')->join('operators as o', 'o.id', '=', 'a.operator_id')
            ->when($status !== 'all', fn ($q) => $q->where('a.status', $status))
            ->orderBy('a.submitted_at')->limit(100)
            ->get(['o.public_id as operator_id', 'o.type', 'o.display_name', 'o.legal_name', 'o.status as operator_status', 'a.status', 'a.submitted_at', 'a.submissions']));
    }

    public function application(string $operator)
    {
        $op = $this->operator($operator);
        $docs = DB::table('kyc_documents')->where('operator_id', $op->id)->orderBy('id')
            ->get(['public_id', 'subject_type', 'subject_id', 'doc_type', 'number_last4', 'expires_on', 'status', 'rejection_reason', 'created_at']);

        return response()->json([
            'operator' => ['public_id' => $op->public_id, 'type' => $op->type, 'display_name' => $op->display_name, 'legal_name' => $op->legal_name, 'status' => $op->status],
            'application' => DB::table('provider_applications')->where('operator_id', $op->id)->first(['status', 'submitted_at', 'review_note', 'submissions']),
            'owner' => DB::table('operator_members as m')->join('users as u', 'u.id', '=', 'm.user_id')->where('m.operator_id', $op->id)->where('m.role', 'owner')->first(['u.name', 'u.email']),
            'documents' => $docs,
            'vehicles' => DB::table('vehicles as v')->join('vehicle_types as t', 't.id', '=', 'v.vehicle_type_id')->where('v.operator_id', $op->id)->whereNull('v.deleted_at')
                ->get(['v.id', 'v.public_id', 't.code as vehicle_type', 'v.plate', 'v.make', 'v.model', 'v.year', 'v.status']),
            'rate_cards' => DB::table('provider_rate_cards')->where('operator_id', $op->id)->get(),
            'service_areas' => DB::table('operator_service_areas as s')->join('zones as z', 'z.id', '=', 's.zone_id')->where('s.operator_id', $op->id)->pluck('z.name'),
            'missing_approvals' => $this->unapproved($op),
        ]);
    }

    public function document(string $doc)
    {
        $row = DB::table('kyc_documents')->where('public_id', $doc)->first();
        abort_unless($row && Storage::disk('local')->exists($row->file_path), 404);

        return Storage::disk('local')->download($row->file_path, $row->doc_type.'-'.$row->public_id.'.'.pathinfo($row->file_path, PATHINFO_EXTENSION));
    }

    public function approveDocument(Request $request, string $doc)
    {
        return $this->run(fn () => $this->onboarding->approveDocument($doc, $request->user()->id));
    }

    public function rejectDocument(Request $request, string $doc)
    {
        $d = $request->validate(['reason' => 'required|string|max:300']);

        return $this->run(fn () => $this->onboarding->rejectDocument($doc, $request->user()->id, $d['reason']));
    }

    public function approve(Request $request, string $operator)
    {
        $d = $request->validate(['note' => 'nullable|string|max:500']);
        $op = $this->operator($operator);

        return $this->run(fn () => $this->onboarding->approve($op->id, $request->user()->id, $d['note'] ?? null));
    }

    public function requestChanges(Request $request, string $operator)
    {
        $d = $request->validate(['note' => 'required|string|max:500']);
        $op = $this->operator($operator);

        return $this->run(fn () => $this->onboarding->requestChanges($op->id, $request->user()->id, $d['note']));
    }

    public function reject(Request $request, string $operator)
    {
        $d = $request->validate(['note' => 'required|string|max:500']);
        $op = $this->operator($operator);

        return $this->run(fn () => $this->onboarding->reject($op->id, $request->user()->id, $d['note']));
    }

    public function suspend(Request $request, string $operator)
    {
        $d = $request->validate(['reason' => 'required|string|max:500']);
        $op = $this->operator($operator);

        return $this->run(fn () => $this->onboarding->suspend($op->id, $request->user()->id, $d['reason']));
    }

    public function reinstate(string $operator)
    {
        $op = $this->operator($operator);

        return $this->run(fn () => $this->onboarding->reinstate($op->id));
    }

    // ----------------------------------------------------------------

    private function operator(string $publicId): object
    {
        $op = DB::table('operators')->where('public_id', $publicId)->whereIn('type', ProviderOnboardingService::TYPES)->first();
        abort_unless($op, 404);

        return $op;
    }

    /** Required documents that are not approved yet, so the reviewer knows what is left. */
    private function unapproved(object $op): array
    {
        [$st, $sid] = $this->onboarding->subject($op->id);
        $approved = DB::table('kyc_documents')->where(['operator_id' => $op->id, 'subject_type' => $st, 'subject_id' => $sid, 'status' => 'approved'])->pluck('doc_type')->all();

        return array_values(array_diff(ProviderOnboardingService::REQUIRED_DOCS[$op->type] ?? [], $approved));
    }

    private function run(callable $fn)
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'cannot_complete', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }
}
