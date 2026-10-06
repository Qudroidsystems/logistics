<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\NoticeAudienceService;
use App\Services\Messaging\PortalNotifier;
use App\Services\Parents\ParentAccountService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sends calendar reminders. For every active event with reminders configured,
 * each upcoming occurrence is checked: if today is (occurrence - days_before),
 * a reminder goes out on each configured channel to the event's audience.
 * Idempotent via calendar_reminder_logs (one row per event/occurrence/lead/channel).
 */
class CalendarReminderService
{
    public function __construct(
        protected CalendarService $calendar,
        protected NoticeAudienceService $audience,
        protected MessagingService $messaging,
    ) {}

    public static function available(): bool
    {
        return Schema::hasTable('calendar_events') && Schema::hasTable('calendar_reminder_logs');
    }

    /** Run reminders for a given "today" (defaults to today). Returns a summary. */
    public function run(?Carbon $today = null): array
    {
        $today = ($today ?: Carbon::today())->startOfDay();
        $sent = ['portal' => 0, 'email' => 0, 'sms' => 0, 'whatsapp' => 0];
        $events = 0;

        if (!self::available()) return ['events' => 0, 'sent' => $sent];

        // Look ahead far enough to cover the largest lead time.
        $maxLead = 60;
        $windowEnd = $today->copy()->addDays($maxLead);

        $rows = CalendarEvent::where('is_active', true)->whereNotNull('reminders')->get();

        foreach ($rows as $e) {
            $reminders = is_array($e->reminders) ? $e->reminders : [];
            if (!$reminders) continue;

            foreach ($e->occurrenceStarts($today->copy(), $windowEnd) as $occ) {
                foreach ($reminders as $r) {
                    $lead = max(0, (int) ($r['days_before'] ?? 0));
                    $fireOn = $occ->copy()->subDays($lead);
                    if (!$fireOn->isSameDay($today)) continue;

                    $channels = array_values(array_intersect(
                        (array) ($r['channels'] ?? []), ['portal', 'email', 'sms', 'whatsapp']
                    ));
                    if (!$channels) continue;

                    foreach ($channels as $ch) {
                        if ($this->alreadyLogged($e->id, $occ, $lead, $ch)) continue;
                        $n = $this->dispatch($e, $occ, $lead, $ch);
                        $this->log($e->id, $occ, $lead, $ch, $n);
                        $sent[$ch] = ($sent[$ch] ?? 0) + $n;
                    }
                }
            }
            $events++;
        }

        return ['events' => $events, 'sent' => $sent];
    }

    protected function message(CalendarEvent $e, Carbon $occ, int $lead): array
    {
        $when = $occ->format('l, d M Y');
        $lead0 = $lead === 0 ? 'today' : ($lead === 1 ? 'tomorrow' : "in {$lead} days");
        $title = "Reminder: {$e->title}";
        $body = "{$e->title} is {$lead0} ({$when})."
            . ($e->location ? " Venue: {$e->location}." : '')
            . ($e->description ? ' ' . \Illuminate\Support\Str::limit(strip_tags($e->description), 200) : '');
        return [$title, $body];
    }

    protected function dispatch(CalendarEvent $e, Carbon $occ, int $lead, string $channel): int
    {
        [$title, $body] = $this->message($e, $occ, $lead);
        $groups = $e->audienceList();

        if ($channel === 'portal') {
            return $this->portal($e, $groups, $title, $body);
        }

        // sms / whatsapp / email use the notice audience resolver.
        $wantsParentsOrStudents = array_intersect(['parents', 'students'], $groups);
        $audiencePayload = [
            'scope' => $wantsParentsOrStudents ? 'school' : 'none',
            'include_staff' => in_array('staff', $groups, true),
        ];
        $resolved = $this->audience->resolve($audiencePayload, [$channel]);
        $contacts = $resolved['contacts'][$channel] ?? [];
        $count = 0;
        foreach ($contacts as $c) {
            try {
                $res = $this->messaging->send($channel, $c['to'], $body, ['title' => $title, 'subject' => $title]);
                if (($res['status'] ?? '') !== 'failed') $count++;
            } catch (\Throwable $ex) {}
        }
        return $count;
    }

    /** In-portal notifications to the relevant user ids. */
    protected function portal(CalendarEvent $e, array $groups, string $title, string $body): int
    {
        if (!class_exists(PortalNotifier::class)) return 0;
        $userIds = [];

        if (in_array('staff', $groups, true)) {
            foreach (DB::table('staffbioinfo')->whereNotNull('userid')->pluck('userid') as $id) $userIds[] = (int) $id;
        }
        if (array_intersect(['parents', 'students'], $groups)) {
            $studentIds = DB::table('studentRegistration')->pluck('id')->map(fn ($v) => (int) $v)->all();
            if (in_array('students', $groups, true)) {
                foreach (DB::table('studentRegistration')->whereNotNull('userid')->pluck('userid') as $id) $userIds[] = (int) $id;
            }
            if (in_array('parents', $groups, true) && $studentIds) {
                foreach (ParentAccountService::parentUserIds($studentIds) as $id) $userIds[] = (int) $id;
            }
        }

        $userIds = array_values(array_unique(array_filter($userIds)));
        if (!$userIds) return 0;
        $url = \Illuminate\Support\Facades\Route::has('calendar.index') ? route('calendar.index') : url('/calendar');
        try {
            PortalNotifier::toUsers($userIds, $title, $body, $url, 'system', 'cal:' . $e->id . ':' . $body);
        } catch (\Throwable $ex) { return 0; }
        return count($userIds);
    }

    protected function alreadyLogged(int $eventId, Carbon $occ, int $lead, string $channel): bool
    {
        return DB::table('calendar_reminder_logs')
            ->where('event_id', $eventId)->where('occurrence_date', $occ->toDateString())
            ->where('days_before', $lead)->where('channel', $channel)->exists();
    }

    protected function log(int $eventId, Carbon $occ, int $lead, string $channel, int $recipients): void
    {
        try {
            DB::table('calendar_reminder_logs')->insert([
                'event_id' => $eventId, 'occurrence_date' => $occ->toDateString(),
                'days_before' => $lead, 'channel' => $channel, 'recipients' => $recipients, 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {} // unique clash = another run already did it
    }
}
