<?php

namespace App\Http\Controllers;

use App\Models\NoticeDelivery;
use App\Models\SchoolNotice;
use App\Models\Student;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\NoticeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notices to parents/staff (CA tests, exams, holidays, midterm, meetings…)
 * by SMS, WhatsApp and email — send now, schedule, and automatic reminders.
 */
class SchoolNoticeController extends Controller
{
    public function __construct(protected NoticeService $notices, protected MessagingService $messaging)
    {
        $this->middleware('permission:View notices')->only(['index', 'show']);
        $this->middleware('permission:Create notices')->except(['index', 'show']);
    }

    public function index(Request $request)
    {
        $q = SchoolNotice::query()->withCount([
            'deliveries as sent_count'   => fn ($d) => $d->where('status', 'sent'),
            'deliveries as failed_count' => fn ($d) => $d->where('status', 'failed'),
        ]);
        if ($s = $request->get('status')) $q->where('status', $s);
        if ($t = $request->get('type')) $q->where('type', $t);
        if ($term = trim((string) $request->get('q'))) $q->where('title', 'like', "%{$term}%");

        $notices = $q->latest()->paginate(20)->withQueryString();

        $stats = [
            'scheduled' => SchoolNotice::where('status', 'scheduled')->count(),
            'month'     => SchoolNotice::whereIn('status', ['sent', 'sending'])->where('sent_at', '>=', now()->startOfMonth())->count(),
            'delivered' => NoticeDelivery::where('status', 'sent')->where('sent_at', '>=', now()->startOfMonth())->count(),
            'failed'    => NoticeDelivery::where('status', 'failed')->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('notices.index', [
            'pagetitle' => 'School Notices',
            'notices'   => $notices,
            'stats'     => $stats,
            'channels'  => $this->channelStates(),
        ]);
    }

    public function create(Request $request)
    {
        $notice = new SchoolNotice([
            'type'      => $request->get('type', 'general'),
            'audience'  => ['scope' => 'school', 'include_staff' => false],
            'channels'  => collect(['sms', 'email', 'portal'])->filter(fn ($c) => $this->messaging->enabled($c))->values()->all() ?: ['email'],
            'reminders' => [],
        ]);
        return $this->form($notice);
    }

    public function edit(SchoolNotice $notice)
    {
        abort_unless($notice->isEditable(), 403, 'This notice has already been sent.');
        return $this->form($notice);
    }

    protected function form(SchoolNotice $notice)
    {
        $classes = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get(['schoolclass.id', DB::raw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as name")]);
        $categories = Schema::hasTable('classcategories')
            ? DB::table('classcategories')->orderBy('category')->get(['id', 'category as name'])
            : collect();
        $selectedStudents = !empty($notice->audience['student_ids'])
            ? Student::whereIn('id', $notice->audience['student_ids'])->get(['id', 'firstname', 'lastname', 'admissionNo'])
            : collect();

        return view('notices.form', [
            'pagetitle'        => $notice->exists ? 'Edit Notice' : 'New Notice',
            'notice'           => $notice,
            'classes'          => $classes,
            'categories'       => $categories,
            'selectedStudents' => $selectedStudents,
            'templates'        => NoticeService::templates(),
            'placeholders'     => NoticeService::PLACEHOLDERS,
            'channels'         => $this->channelStates(),
        ]);
    }

    /** Save (draft), then optionally send now / schedule. */
    public function store(Request $request)
    {
        return $this->save($request, new SchoolNotice(['created_by' => auth()->id(), 'status' => 'draft']));
    }

    public function update(Request $request, SchoolNotice $notice)
    {
        abort_unless($notice->isEditable(), 403, 'This notice has already been sent.');
        return $this->save($request, $notice);
    }

    protected function save(Request $request, SchoolNotice $notice)
    {
        $data   = $this->validated($request);
        $action = $request->input('action', 'draft'); // draft | send | schedule

        $notice->fill($data);
        if (!$notice->exists) $notice->created_by = auth()->id();
        if ($notice->status === 'scheduled' && $action === 'draft') {
            $notice->dispatches()->where('status', 'pending')->update(['status' => 'cancelled']);
            $notice->status = 'draft';
        }
        $notice->save();

        if ($action === 'draft') {
            return redirect()->route('notices.edit', $notice)->with('success', 'Draft saved.');
        }

        if (!array_filter($notice->channels ?? [], fn ($c) => $this->messaging->enabled($c))) {
            return back()->withInput()->with('error', 'None of the selected channels is switched on. Turn them on in Notification settings first.');
        }

        $sendAt = null;
        if ($action === 'schedule') {
            $sendAt = Carbon::parse($request->input('send_at'));
            if ($sendAt->lte(now()->addMinute())) {
                return back()->withInput()->with('error', 'Choose a send time in the future, or use Send now.');
            }
        }

        $dispatches = $this->notices->schedule($notice, $sendAt);

        if ($action === 'send') {
            $first = $dispatches[0]->id;
            // Send after the page has been returned, so the admin isn't kept waiting.
            dispatch(function () use ($first) {
                app(NoticeService::class)->process($first);
            })->afterResponse();
            return redirect()->route('notices.show', $notice)->with('success', 'Sending has started. This page shows progress; refresh in a moment.');
        }

        return redirect()->route('notices.show', $notice)->with('success', 'Notice scheduled for ' . $sendAt->format('D j M Y, g:i a') . '.');
    }

    protected function validated(Request $request): array
    {
        $v = $request->validate([
            'type'                 => 'required|in:' . implode(',', array_keys(SchoolNotice::TYPES)),
            'title'                => 'required|string|max:150',
            'message'              => 'required|string|max:5000',
            'sms_text'             => 'nullable|string|max:918',
            'event_date'           => 'nullable|date',
            'event_end_date'       => 'nullable|date|after_or_equal:event_date',
            'event_time'           => 'nullable|string|max:20',
            'scope'                => 'required|in:school,classes,categories,students,none',
            'class_ids'            => 'required_if:scope,classes|array',
            'class_ids.*'          => 'integer',
            'category_ids'         => 'required_if:scope,categories|array',
            'category_ids.*'       => 'integer',
            'student_ids'          => 'required_if:scope,students|array',
            'student_ids.*'        => 'integer',
            'include_staff'        => 'nullable|boolean',
            'channels'             => 'required|array|min:1',
            'channels.*'           => 'in:sms,whatsapp,email,portal',
            'reminders'            => 'nullable|array',
            'reminders.*.days'     => 'nullable|integer|min:0|max:30',
            'reminders.*.time'     => 'nullable|date_format:H:i',
            'reminders.*.on'       => 'nullable',
        ], [
            'class_ids.required_if'    => 'Pick at least one class.',
            'category_ids.required_if' => 'Pick at least one class category.',
            'student_ids.required_if'  => 'Pick at least one student.',
        ]);

        if ($v['scope'] === 'none' && empty($v['include_staff'])) {
            abort(back()->withInput()->with('error', 'Choose who should receive the notice.'));
        }

        $reminders = collect($v['reminders'] ?? [])->filter(fn ($r) => !empty($r['on']))
            ->map(fn ($r) => ['days' => (int) ($r['days'] ?? 0), 'time' => $r['time'] ?? '08:00'])
            ->unique(fn ($r) => $r['days'] . $r['time'])->values()->all();

        return [
            'type' => $v['type'], 'title' => $v['title'], 'message' => $v['message'],
            'sms_text' => $v['sms_text'] ?? null,
            'event_date' => $v['event_date'] ?? null, 'event_end_date' => $v['event_end_date'] ?? null,
            'event_time' => $v['event_time'] ?? null,
            'audience' => [
                'scope'         => $v['scope'],
                'class_ids'     => array_map('intval', $v['class_ids'] ?? []),
                'category_ids'  => array_map('intval', $v['category_ids'] ?? []),
                'student_ids'   => array_map('intval', $v['student_ids'] ?? []),
                'include_staff' => (bool) ($v['include_staff'] ?? false),
            ],
            'channels'  => array_values(array_unique($v['channels'])),
            'reminders' => $reminders,
        ];
    }

    /** Live preview for the form (recipient counts, SMS pages, sample text, missing contacts). */
    public function preview(Request $request)
    {
        $notice = new SchoolNotice($this->validatedLoose($request));
        return response()->json($this->notices->preview($notice));
    }

    /** Send only to the admin's own phone/email. */
    public function test(Request $request)
    {
        $notice = new SchoolNotice($this->validatedLoose($request));
        $res = $this->notices->sendTest($notice, $request->user());
        return response()->json(['results' => $res]);
    }

    protected function validatedLoose(Request $request): array
    {
        return [
            'type' => $request->input('type', 'general'),
            'title' => (string) $request->input('title', ''),
            'message' => (string) $request->input('message', ''),
            'sms_text' => $request->input('sms_text'),
            'event_date' => $request->input('event_date') ?: null,
            'event_end_date' => $request->input('event_end_date') ?: null,
            'event_time' => $request->input('event_time'),
            'audience' => [
                'scope' => $request->input('scope', 'school'),
                'class_ids' => array_map('intval', (array) $request->input('class_ids', [])),
                'category_ids' => array_map('intval', (array) $request->input('category_ids', [])),
                'student_ids' => array_map('intval', (array) $request->input('student_ids', [])),
                'include_staff' => $request->boolean('include_staff'),
            ],
            'channels' => array_values(array_intersect((array) $request->input('channels', []), ['sms', 'whatsapp', 'email', 'portal'])),
        ];
    }

    public function show(Request $request, SchoolNotice $notice)
    {
        $notice->load(['dispatches' => fn ($q) => $q->orderBy('run_at'), 'creator']);

        $deliveries = NoticeDelivery::where('school_notice_id', $notice->id)
            ->when($request->get('channel'), fn ($q, $c) => $q->where('channel', $c))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(trim((string) $request->get('q')), fn ($q, $t) => $q->where(fn ($w) => $w->where('recipient', 'like', "%{$t}%")->orWhere('recipient_name', 'like', "%{$t}%")))
            ->orderByDesc('id')->paginate(50)->withQueryString();

        $totals = NoticeDelivery::where('school_notice_id', $notice->id)
            ->selectRaw('channel, status, COUNT(*) as n')->groupBy('channel', 'status')->get()
            ->groupBy('channel')->map(fn ($g) => $g->pluck('n', 'status'));

        return view('notices.show', [
            'pagetitle'  => $notice->title,
            'notice'     => $notice,
            'deliveries' => $deliveries,
            'totals'     => $totals,
            'busy'       => in_array($notice->status, ['sending'], true),
        ]);
    }

    public function cancel(SchoolNotice $notice)
    {
        $this->notices->cancel($notice);
        return back()->with('success', 'Scheduled sends for this notice were cancelled.');
    }

    public function resendFailed(SchoolNotice $notice)
    {
        $d = $this->notices->resendFailed($notice);
        if (!$d) return back()->with('error', 'There are no failed messages to resend.');

        $id = $d->id;
        dispatch(fn () => app(NoticeService::class)->sendQueued($id))->afterResponse();
        return back()->with('success', 'Resending ' . $d->total . ' failed message(s).');
    }

    public function duplicate(SchoolNotice $notice)
    {
        $copy = $notice->replicate(['status', 'send_at', 'sent_at']);
        $copy->fill(['status' => 'draft', 'title' => $notice->title . ' (copy)', 'created_by' => auth()->id()])->save();
        return redirect()->route('notices.edit', $copy)->with('success', 'Copy created. Adjust it and send.');
    }

    public function destroy(SchoolNotice $notice)
    {
        abort_unless(in_array($notice->status, ['draft', 'cancelled'], true), 403, 'Only drafts and cancelled notices can be deleted.');
        $notice->dispatches()->delete();
        $notice->delete();
        return redirect()->route('notices.index')->with('success', 'Notice deleted.');
    }

    public function searchStudents(Request $request)
    {
        $t = trim((string) $request->get('q'));
        if (mb_strlen($t) < 2) return response()->json([]);

        return response()->json(Student::where(fn ($w) => $w->where('admissionNo', 'like', "%{$t}%")
                ->orWhere('firstname', 'like', "%{$t}%")->orWhere('lastname', 'like', "%{$t}%"))
            ->orderBy('lastname')->limit(15)->get(['id', 'firstname', 'lastname', 'admissionNo'])
            ->map(fn ($s) => ['id' => $s->id, 'label' => trim($s->lastname . ' ' . $s->firstname) . ' (' . $s->admissionNo . ')']));
    }

    protected function channelStates(): array
    {
        $out = [];
        foreach (['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $c => $label) {
            $s = $this->messaging->setting($c);
            $out[$c] = ['label' => $label, 'enabled' => $this->messaging->enabled($c), 'live' => $s->isLive(), 'driver' => $s->driver];
        }
        if ($this->messaging->enabled('portal')) {
            $out['portal'] = ['label' => 'In-portal (bell)', 'enabled' => true, 'live' => true, 'driver' => 'portal'];
        }
        return $out;
    }
}
