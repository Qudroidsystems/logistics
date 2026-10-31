<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Support\SupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Operations > Support: customers' help requests. */
class SupportInboxController extends Controller
{
    public function __construct(private SupportService $support) {}

    private function check(Request $request, string $perm = 'View support ticket'): void
    {
        abort_unless($request->user()->canAny([$perm, 'Reply support ticket']), 403);
    }

    public function index(Request $request)
    {
        $this->check($request);
        $view = in_array($request->query('view'), ['open', 'mine', 'unassigned', 'waiting', 'resolved', 'all'], true) ? $request->query('view') : 'open';

        return view('ops.support', [
            'rows' => $this->support->inbox($view, $request->user()->id, $request->query('q')), 'counts' => $this->support->counts($request->user()->id),
            'view' => $view, 'q' => $request->query('q'), 'statuses' => SupportService::STATUSES, 'pagetitle' => 'Support',
        ]);
    }

    public function show(Request $request, string $ticket)
    {
        $this->check($request);
        try {
            $t = $this->support->thread($ticket);
        } catch (RuntimeException) {
            abort(404);
        }
        DB::table('support_tickets')->where('public_id', $ticket)->where('assignee_id', $request->user()->id)->update(['staff_unread' => false]);
        $others = DB::table('support_tickets')->where('requester_id', $t['ticket']->requester_id)->where('public_id', '<>', $ticket)->orderByDesc('created_at')->limit(10)
            ->get(['public_id', 'number', 'subject', 'status', 'created_at']);

        return view('ops.support-thread', $t + [
            'statuses' => SupportService::STATUSES, 'others' => $others, 'categories' => SupportService::CATEGORIES,
            'canReply' => $request->user()->can('Reply support ticket'), 'canClose' => $request->user()->canAny(['Close support ticket', 'Reply support ticket']),
            'pagetitle' => 'Support · '.$t['ticket']->number,
        ]);
    }

    public function reply(Request $request, string $ticket)
    {
        $this->check($request, 'Reply support ticket');
        abort_unless($request->user()->can('Reply support ticket'), 403);
        $d = $request->validate(['body' => 'required|string|max:4000', 'internal' => 'nullable|boolean', 'status' => 'nullable|string|max:20']);
        try {
            $this->support->staffReply($ticket, $request->user()->id, $d['body'], (bool) ($d['internal'] ?? false), $d['status'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', ($d['internal'] ?? false) ? 'Note added.' : 'Reply sent to the customer.');
    }

    public function status(Request $request, string $ticket)
    {
        abort_unless($request->user()->canAny(['Close support ticket', 'Reply support ticket']), 403);
        $d = $request->validate(['status' => 'required|in:'.implode(',', array_keys(SupportService::STATUSES))]);
        $this->support->setStatus($ticket, $d['status']);

        return back()->with('success', 'Status changed.');
    }

    public function assign(Request $request, string $ticket)
    {
        abort_unless($request->user()->canAny(['Assign support ticket', 'Reply support ticket']), 403);
        $d = $request->validate(['to' => 'required|in:me,nobody']);
        $this->support->assign($ticket, $d['to'] === 'me' ? $request->user()->id : null);

        return back()->with('success', $d['to'] === 'me' ? 'Assigned to you.' : 'Unassigned.');
    }
}
