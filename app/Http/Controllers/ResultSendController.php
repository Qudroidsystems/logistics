<?php

namespace App\Http\Controllers;

use App\Models\ResultSend;
use App\Models\ResultSendDelivery;
use App\Models\ResultSendItem;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\ResultSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Send report cards to parents by email (PDF attached), WhatsApp (PDF or
 * link) and SMS (secure link).
 */
class ResultSendController extends Controller
{
    public function __construct(protected ResultSendService $service, protected MessagingService $messaging)
    {
        $this->middleware('permission:View result-sends')->only(['index', 'show', 'pdf']);
        $this->middleware('permission:Create result-sends')->only(['create', 'candidates', 'store', 'cancel', 'resend']);
    }

    public function index()
    {
        $sends = ResultSend::with(['term', 'session', 'creator'])->latest()->paginate(20);

        return view('result-sends.index', [
            'pagetitle' => 'Send Results to Parents',
            'sends'     => $sends,
            'stats'     => [
                'batches'   => ResultSend::count(),
                'delivered' => ResultSendDelivery::where('status', 'sent')->count(),
                'failed'    => ResultSendDelivery::where('status', 'failed')->count(),
                'downloads' => (int) ResultSendItem::sum('downloads'),
            ],
        ]);
    }

    public function create()
    {
        $sessions = Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']);
        $classes  = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get(['schoolclass.id', DB::raw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as name")]);

        return view('result-sends.create', [
            'pagetitle'    => 'Send Results',
            'sessions'     => $sessions,
            'terms'        => Schoolterm::orderBy('id')->get(['id', 'term']),
            'classes'      => $classes,
            'channels'     => collect(['email' => 'Email', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS'])->map(fn ($l, $c) => [
                'label' => $l, 'enabled' => $this->messaging->enabled($c), 'live' => $this->messaging->setting($c)->isLive(),
                'doc'   => $c === 'whatsapp' ? (bool) trim((string) $this->messaging->setting('whatsapp')->value('document_template_name')) : null,
            ])->all(),
            'placeholders' => ResultSendService::PLACEHOLDERS,
            'defaultMsg'   => ResultSendService::DEFAULT_MESSAGE,
            'defaultSms'   => ResultSendService::DEFAULT_SMS,
            'currentSessionId' => $sessions->firstWhere('status', 'Current')->id ?? $sessions->first()->id ?? null,
        ]);
    }

    /** The check table: every student with results, readiness, fees and contacts. */
    public function candidates(Request $request)
    {
        $d = $request->validate([
            'session_id'     => 'required|integer',
            'term_id'        => 'required|integer',
            'class_ids'      => 'required|array|min:1',
            'class_ids.*'    => 'integer',
            'require_vetted' => 'nullable|boolean',
        ]);

        $rows = $this->service->candidates((int) $d['session_id'], (int) $d['term_id'], array_map('intval', $d['class_ids']), $request->boolean('require_vetted'));
        return response()->json(['students' => $rows->values()]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'session_id'     => 'required|integer|exists:schoolsession,id',
            'term_id'        => 'required|integer|exists:schoolterm,id',
            'class_ids'      => 'required|array|min:1',
            'class_ids.*'    => 'integer',
            'student_ids'    => 'required|array|min:1',
            'student_ids.*'  => 'integer',
            'channels'       => 'required|array|min:1',
            'channels.*'     => 'in:email,whatsapp,sms',
            'message'        => 'required|string|max:3000',
            'sms_text'       => 'nullable|string|max:612',
            'include_owing'  => 'nullable|boolean',
            'require_vetted' => 'nullable|boolean',
            'link_days'      => 'nullable|integer|min:1|max:90',
        ], ['student_ids.required' => 'Tick at least one student to send to.']);

        $channels = array_values(array_filter($d['channels'], fn ($c) => $this->messaging->enabled($c)));
        if (!$channels) {
            return back()->withInput()->with('error', 'None of the selected channels is switched on in Notification settings.');
        }

        $send = $this->service->create([
            'session_id'     => (int) $d['session_id'],
            'term_id'        => (int) $d['term_id'],
            'report_type'    => 'terminal',
            'class_ids'      => array_map('intval', $d['class_ids']),
            'channels'       => $channels,
            'message'        => $d['message'],
            'sms_text'       => $d['sms_text'] ?? null,
            'include_owing'  => $request->boolean('include_owing'),
            'require_vetted' => $request->boolean('require_vetted'),
            'link_days'      => (int) ($d['link_days'] ?? 14),
        ], array_map('intval', $d['student_ids']), (int) auth()->id());

        $id = $send->id;
        // Start right away (the scheduler finishes large batches).
        dispatch(fn () => app(ResultSendService::class)->process($id, 40))->afterResponse();

        return redirect()->route('result-sends.show', $send)
            ->with('success', 'Sending started for ' . ($send->students - $send->skipped) . ' student(s). Progress updates on this page.');
    }

    public function show(Request $request, ResultSend $send)
    {
        $send->load(['term', 'session', 'creator']);

        $items = ResultSendItem::with(['student:id,firstname,lastname,othername,admissionNo', 'deliveries'])
            ->where('result_send_id', $send->id)
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("FIELD(status, 'failed', 'sending', 'generating', 'pending', 'done', 'skipped')")
            ->orderBy('id')->paginate(50)->withQueryString();

        $byChannel = ResultSendDelivery::where('result_send_id', $send->id)
            ->selectRaw('channel, status, COUNT(*) n')->groupBy('channel', 'status')->get()
            ->groupBy('channel')->map(fn ($g) => $g->pluck('n', 'status'));
        $itemCounts = ResultSendItem::where('result_send_id', $send->id)->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        $classNames = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->whereIn('schoolclass.id', $send->class_ids ?? [])
            ->selectRaw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as class_label")->pluck('class_label');

        return view('result-sends.show', [
            'pagetitle'  => 'Result sending',
            'send'       => $send,
            'items'      => $items,
            'byChannel'  => $byChannel,
            'itemCounts' => $itemCounts,
            'classNames' => $classNames,
            'busy'       => in_array($send->status, ['queued', 'sending'], true),
        ]);
    }

    public function resend(ResultSend $send)
    {
        $n = $this->service->resendFailed($send);
        if (!$n) return back()->with('error', 'There is nothing to resend.');

        $id = $send->id;
        dispatch(fn () => app(ResultSendService::class)->process($id, 40))->afterResponse();
        return back()->with('success', 'Resending failed messages.');
    }

    public function cancel(ResultSend $send)
    {
        ResultSendItem::where('result_send_id', $send->id)->where('status', 'pending')
            ->update(['status' => 'skipped', 'skip_reason' => 'Cancelled']);
        $send->update(['status' => 'cancelled', 'finished_at' => now()]);
        return back()->with('success', 'Sending stopped. Messages already sent are not affected.');
    }

    /** Admin preview/download of a generated report card. */
    public function pdf(ResultSendItem $item)
    {
        abort_unless($item->pdf_path && Storage::disk('local')->exists($item->pdf_path), 404);
        return response()->file(Storage::disk('local')->path($item->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="report-card-' . $item->student_id . '.pdf"',
        ]);
    }
}
