<?php

namespace App\Http\Controllers;

use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Services\Reporting\ManagementDashboardService;
use Illuminate\Http\Request;

/** One-page overview for the principal / proprietor. */
class ManagementDashboardController extends Controller
{
    public function __construct(protected ManagementDashboardService $svc)
    {
        $this->middleware('permission:View management dashboard');
    }

    public function index(Request $request)
    {
        $sessions  = Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']);
        $terms     = Schoolterm::orderBy('id')->get(['id', 'term']);
        $sessionId = (int) ($request->get('session_id') ?: (Schoolsession::where('status', 'Current')->value('id') ?? $sessions->first()?->id));
        $termId    = (int) ($request->get('term_id') ?: (Schoolterm::where('status', true)->value('id') ?? $terms->first()?->id));

        $att  = $this->svc->attendanceToday($sessionId, $termId);
        $fees = $this->svc->feeSnapshot($termId, $sessionId, $request->boolean('refresh'));
        $res  = $this->svc->results($termId, $sessionId);
        $msg  = $this->svc->messaging();

        if ($request->boolean('refresh')) {
            return redirect()->route('management.dashboard', ['term_id' => $termId, 'session_id' => $sessionId])
                ->with('success', 'Recalculating fee figures — refresh in a minute.');
        }

        return view('management.dashboard', [
            'pagetitle'   => 'Management Dashboard',
            'sessions'    => $sessions, 'terms' => $terms, 'sessionId' => $sessionId, 'termId' => $termId,
            'enrol'       => $this->svc->enrolment($sessionId),
            'att'         => $att,
            'fees'        => $fees,
            'collections' => $this->svc->collections(14),
            'res'         => $res,
            'msg'         => $msg,
            'actions'     => $this->svc->actions($att, $fees, $res, $msg),
        ]);
    }

    /** JSON: is the fee snapshot ready? (polled by the page) */
    public function feeStatus(Request $request)
    {
        $ready = (bool) \Illuminate\Support\Facades\Cache::get('mgmt:fees:' . (int) $request->get('term_id') . ':' . (int) $request->get('session_id'));
        return response()->json(['ready' => $ready]);
    }
}
