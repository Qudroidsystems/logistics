<?php

namespace App\Http\Controllers;

use App\Models\CalendarAttachment;
use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarRsvp;
use App\Services\Calendar\CalendarFeeSyncService;
use App\Services\Calendar\CalendarService;
use App\Services\Parents\ParentAccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CalendarController extends Controller
{
    public function __construct(protected CalendarService $svc)
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage school calendar')->only([
            'store', 'update', 'destroy', 'storeCategory', 'updateCategory', 'destroyCategory',
            'uploadAttachment', 'deleteAttachment', 'syncFees',
        ]);
    }

    protected function canManage(Request $request): bool
    {
        return $request->user()->can('Manage school calendar');
    }

    public function index(Request $request)
    {
        $anchor = $this->anchor($request);
        $manage = $this->canManage($request);
        $groups = $this->svc->viewerGroups($request->user());

        $filter = [
            'audiences'     => $manage ? null : $groups,
            'category_id'   => $request->query('category'),
            'session_id'    => $request->query('session'),
            'term_id'       => $request->query('term'),
            'with_holidays' => $request->query('holidays', '1') !== '0',
        ];

        $weeks = $this->svc->monthGrid($anchor, $filter);
        $upcoming = $this->svc->occurrences(Carbon::today(), Carbon::today()->addDays(45), $filter)
            ->where('start', '>=', Carbon::today()->startOfDay())->take(15)->values();

        return view('calendar.index', [
            'pagetitle'  => 'School Calendar',
            'anchor'     => $anchor,
            'weeks'      => $weeks,
            'upcoming'   => $upcoming,
            'manage'     => $manage,
            'categories' => $this->svc->categories(false),
            'sessions'   => DB::table('schoolsession')->orderByDesc('id')->pluck('session', 'id'),
            'terms'      => DB::table('schoolterm')->pluck('term', 'id'),
            'audiences'  => CalendarEvent::AUDIENCES,
            'events'     => $manage
                ? CalendarEvent::with('category')->orderByDesc('start_date')->limit(300)->get()
                : collect(),
        ]);
    }

    public function show(Request $request, CalendarEvent $event)
    {
        $groups = $this->svc->viewerGroups($request->user());
        abort_unless($this->canManage($request) || array_intersect($groups, $event->audienceList()), 403);
        $event->load('category', 'attachments');

        $myRsvp = CalendarRsvp::where('event_id', $event->id)->where('user_id', $request->user()->id)->first();
        $counts = CalendarRsvp::where('event_id', $event->id)->selectRaw('response, COUNT(*) c')->groupBy('response')->pluck('c', 'response');

        return response()->json([
            'event' => [
                'id' => $event->id, 'title' => $event->title, 'description' => $event->description,
                'color' => $event->displayColor(), 'category' => $event->category->name ?? null,
                'location' => $event->location, 'all_day' => $event->all_day,
                'start_date' => $event->start_date->toDateString(), 'end_date' => $event->end_date->toDateString(),
                'start_time' => $event->start_time, 'end_time' => $event->end_time,
                'audiences' => $event->audienceList(), 'is_public' => $event->is_public,
                'rsvp_enabled' => $event->rsvp_enabled, 'source' => $event->source,
                'attachments' => $event->attachments->map(fn ($a) => [
                    'id' => $a->id, 'name' => $a->name, 'url' => route('calendar.attachment', $a),
                ]),
                'reminders' => $event->reminders ?? [], 'recurrence' => $event->recurrence ?? [],
            ],
            'my_rsvp' => $myRsvp->response ?? null,
            'rsvp_counts' => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->validated($request);
        $d['created_by'] = $request->user()->id;
        $event = CalendarEvent::create($d);
        $this->saveAttachment($request, $event);
        return back()->with('success', 'Event added to the calendar.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $event->update($this->validated($request));
        $this->saveAttachment($request, $event);
        return back()->with('success', 'Event updated.');
    }

    public function destroy(CalendarEvent $event)
    {
        foreach ($event->attachments as $a) { Storage::disk('local')->delete($a->path); $a->delete(); }
        $event->delete();
        return back()->with('success', 'Event removed from the calendar.');
    }

    protected function validated(Request $request): array
    {
        $d = $request->validate([
            'title'       => 'required|string|max:180',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|integer|exists:calendar_categories,id',
            'color'       => 'nullable|string|max:20',
            'session_id'  => 'nullable|integer',
            'term_id'     => 'nullable|integer',
            'location'    => 'nullable|string|max:160',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'all_day'     => 'nullable|boolean',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'audiences'   => 'nullable|array',
            'audiences.*' => 'in:staff,parents,students,public',
            'rsvp_enabled'=> 'nullable|boolean',
            'rec_freq'    => 'nullable|in:none,daily,weekly,monthly,yearly',
            'rec_interval'=> 'nullable|integer|min:1|max:52',
            'rec_until'   => 'nullable|date',
            'rec_byday'   => 'nullable|array',
            'rec_byday.*' => 'integer|min:0|max:6',
            'reminders'   => 'nullable|array',
        ]);

        $audiences = $d['audiences'] ?? [];
        $recurrence = null;
        if (!empty($d['rec_freq']) && $d['rec_freq'] !== 'none') {
            $recurrence = [
                'freq' => $d['rec_freq'],
                'interval' => (int) ($d['rec_interval'] ?? 1),
                'until' => $d['rec_until'] ?? null,
                'byday' => $d['rec_freq'] === 'weekly' ? array_map('intval', $d['rec_byday'] ?? []) : [],
            ];
        }

        return [
            'title' => $d['title'], 'description' => $d['description'] ?? null,
            'category_id' => $d['category_id'] ?? null, 'color' => $d['color'] ?? null,
            'session_id' => $d['session_id'] ?? null, 'term_id' => $d['term_id'] ?? null,
            'location' => $d['location'] ?? null,
            'start_date' => $d['start_date'], 'end_date' => $d['end_date'],
            'all_day' => $request->boolean('all_day', empty($d['start_time'])),
            'start_time' => $d['start_time'] ?? null, 'end_time' => $d['end_time'] ?? null,
            'audiences' => $audiences, 'is_public' => in_array('public', $audiences, true),
            'recurrence' => $recurrence, 'reminders' => $this->cleanReminders($d['reminders'] ?? []),
            'rsvp_enabled' => $request->boolean('rsvp_enabled'),
            'source' => 'manual',
        ];
    }

    protected function cleanReminders(array $raw): array
    {
        $out = [];
        foreach ($raw as $r) {
            $days = isset($r['days_before']) ? (int) $r['days_before'] : null;
            $channels = array_values(array_intersect((array) ($r['channels'] ?? []), ['portal', 'email', 'sms', 'whatsapp']));
            if ($days === null || $days < 0 || !$channels) continue;
            $out[] = ['days_before' => $days, 'channels' => $channels];
        }
        return $out;
    }

    // ── Categories ───────────────────────────────────────────────────────

    public function storeCategory(Request $request)
    {
        $d = $request->validate(['name' => 'required|string|max:80', 'color' => 'nullable|string|max:20', 'icon' => 'nullable|string|max:40']);
        CalendarCategory::create([
            'name' => $d['name'], 'slug' => CalendarCategory::slugFor($d['name']),
            'color' => $d['color'] ?? '#0f766e', 'icon' => $d['icon'] ?? null, 'is_active' => true,
        ]);
        return back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, CalendarCategory $category)
    {
        $d = $request->validate(['name' => 'required|string|max:80', 'color' => 'nullable|string|max:20', 'icon' => 'nullable|string|max:40', 'is_active' => 'nullable|boolean']);
        $category->update(['name' => $d['name'], 'color' => $d['color'] ?? $category->color, 'icon' => $d['icon'] ?? $category->icon, 'is_active' => $request->boolean('is_active', true)]);
        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(CalendarCategory $category)
    {
        CalendarEvent::where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    // ── Attachments ──────────────────────────────────────────────────────

    protected function saveAttachment(Request $request, CalendarEvent $event): void
    {
        if (!$request->hasFile('attachment')) return;
        $request->validate(['attachment' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:8192']);
        $file = $request->file('attachment');
        $path = $file->store('calendar', 'local');
        $event->attachments()->create([
            'path' => $path, 'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(), 'size' => $file->getSize(),
        ]);
    }

    public function uploadAttachment(Request $request, CalendarEvent $event)
    {
        $this->saveAttachment($request, $event);
        return back()->with('success', 'Attachment added.');
    }

    public function attachment(Request $request, CalendarAttachment $attachment)
    {
        $event = $attachment->event;
        $groups = $this->svc->viewerGroups($request->user());
        abort_unless($this->canManage($request) || ($event && array_intersect($groups, $event->audienceList())), 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
        return Storage::disk('local')->response($attachment->path, $attachment->name);
    }

    public function deleteAttachment(CalendarAttachment $attachment)
    {
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();
        return back()->with('success', 'Attachment removed.');
    }

    // ── RSVP ─────────────────────────────────────────────────────────────

    public function rsvp(Request $request, CalendarEvent $event)
    {
        abort_unless($event->rsvp_enabled, 404);
        $groups = $this->svc->viewerGroups($request->user());
        abort_unless(array_intersect($groups, $event->audienceList()) || $this->canManage($request), 403);
        $d = $request->validate(['response' => 'required|in:going,maybe,no', 'note' => 'nullable|string|max:255', 'student_id' => 'nullable|integer']);

        CalendarRsvp::updateOrCreate(
            ['event_id' => $event->id, 'occurrence_date' => null, 'user_id' => $request->user()->id, 'student_id' => $d['student_id'] ?? null],
            ['response' => $d['response'], 'note' => $d['note'] ?? null]
        );
        return back()->with('success', 'Your response has been recorded.');
    }

    // ── Manual fee sync ──────────────────────────────────────────────────

    public function syncFees(CalendarFeeSyncService $fees)
    {
        $r = $fees->sync();
        return back()->with('success', "Fee deadlines synced: {$r['created']} new, {$r['updated']} updated, {$r['removed']} removed.");
    }

    protected function anchor(Request $request): Carbon
    {
        $m = (string) $request->query('month', '');
        try {
            return $m ? Carbon::createFromFormat('Y-m', $m)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }
}
