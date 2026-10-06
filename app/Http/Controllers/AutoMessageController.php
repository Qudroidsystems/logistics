<?php

namespace App\Http\Controllers;

use App\Services\Messaging\AutoMessageService;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Notices › Automatic messages: absence alerts, fee reminders, birthday wishes. */
class AutoMessageController extends Controller
{
    public function __construct(protected AutoMessageService $auto, protected MessagingService $messaging)
    {
        $this->middleware('permission:Manage notification settings');
    }

    public function index()
    {
        $settings = collect(AutoMessageService::TYPES)->mapWithKeys(fn ($t) => [$t => AutoMessageService::settings($t)]);
        $has = Schema::hasTable('auto_messages');

        $recent = $has ? DB::table('auto_messages')->orderByDesc('id')->limit(25)->get() : collect();
        $stats  = $has ? DB::table('auto_messages')->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('type, status, COUNT(*) n')->groupBy('type', 'status')->get()
            ->groupBy('type')->map(fn ($g) => $g->pluck('n', 'status')) : collect();

        return view('notices.automations', [
            'pagetitle'    => 'Automatic Messages',
            'settings'     => $settings,
            'placeholders' => AutoMessageService::PLACEHOLDERS,
            'recent'       => $recent,
            'stats'        => $stats,
            'channels'     => collect(['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'])
                ->map(fn ($l, $c) => ['label' => $l, 'enabled' => $this->messaging->enabled($c)]),
        ]);
    }

    public function update(Request $request, string $type)
    {
        abort_unless(in_array($type, AutoMessageService::TYPES, true), 404);

        $rules = [
            'is_active'  => 'nullable|boolean',
            'channels'   => 'nullable|array',
            'channels.*' => 'in:sms,whatsapp,email',
            'message'    => 'required|string|max:2000',
            'sms_text'   => 'nullable|string|max:459',
        ];
        $rules += match ($type) {
            'absence'  => ['time' => 'required|date_format:H:i', 'days' => 'nullable|array', 'days.*' => 'integer|between:1,7',
                           'statuses' => 'required|array|min:1', 'statuses.*' => 'in:absent,late,sick_leave,excused', 'period' => 'required|in:morning,any'],
            'fees'     => ['min_balance' => 'nullable|numeric|min:0', 'include_arrears' => 'nullable|boolean',
                           'dates' => 'nullable|array', 'dates.*.date' => 'nullable|date', 'dates.*.time' => 'nullable|date_format:H:i'],
            'birthday' => ['time' => 'required|date_format:H:i', 'audience' => 'required|in:parents,students,both'],
        };
        $d = $request->validate($rules);

        $s = AutoMessageService::settings($type);
        $config = $s->config;
        $config['channels'] = array_values(array_unique($d['channels'] ?? []));
        $config['message']  = $d['message'];
        $config['sms_text'] = $d['sms_text'] ?? null;

        if ($type === 'absence') {
            $config['time'] = $d['time'];
            $config['days'] = array_map('intval', $d['days'] ?? []);
            $config['statuses'] = $d['statuses'];
            $config['period'] = $d['period'];
        } elseif ($type === 'fees') {
            $config['min_balance'] = (float) ($d['min_balance'] ?? 0);
            $config['include_arrears'] = $request->boolean('include_arrears');
            $config['dates'] = collect($d['dates'] ?? [])->filter(fn ($x) => !empty($x['date']))
                ->map(fn ($x) => ['date' => date('Y-m-d', strtotime($x['date'])), 'time' => $x['time'] ?? '08:00'])
                ->unique(fn ($x) => $x['date'] . $x['time'])->sortBy(fn ($x) => $x['date'] . $x['time'])->values()->all();
        } else {
            $config['time'] = $d['time'];
            $config['audience'] = $d['audience'];
        }

        $s->config = $config;
        $s->is_active = $request->boolean('is_active');
        if ($s->is_active && !$config['channels']) {
            return back()->with('error', 'Pick at least one channel.')->withFragment($type);
        }
        $s->save();

        return back()->with('success', ucfirst($type === 'fees' ? 'fee reminder' : $type) . ' settings saved.')->withFragment($type);
    }

    /** Who would receive it right now (count, sample, SMS cost). */
    public function preview(Request $request, string $type)
    {
        abort_unless(in_array($type, AutoMessageService::TYPES, true), 404);
        @set_time_limit(300);
        return response()->json($this->auto->preview($type, $this->ctx($request, $type)));
    }

    /** Send now (ignores the schedule; anything already sent is skipped). */
    public function run(Request $request, string $type)
    {
        abort_unless(in_array($type, AutoMessageService::TYPES, true), 404);
        $ctx = $this->ctx($request, $type);
        dispatch(fn () => app(AutoMessageService::class)->send($type, $ctx))->afterResponse();
        return back()->with('success', 'Sending started. Results appear in "Recent messages" below in a minute.')->withFragment($type);
    }

    protected function ctx(Request $request, string $type): array
    {
        return match ($type) {
            'fees'  => ['run' => 'manual ' . now()->format('Y-m-d')], // one manual run per day per student
            default => ['date' => now()->toDateString()],
        };
    }
}
