<?php

namespace App\Http\Controllers;

use App\Services\Calendar\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * No-login school calendar: only events marked "public". Also serves a stable
 * iCal feed parents can subscribe to in Google/Apple Calendar.
 */
class PublicCalendarController extends Controller
{
    public function __construct(protected CalendarService $svc) {}

    /** Stable feed token derived from the app key (no DB needed). */
    public static function feedToken(): string
    {
        return substr(hash_hmac('sha256', 'school-calendar-feed', (string) config('app.key')), 0, 32);
    }

    public function index(Request $request)
    {
        $anchor = $this->anchor($request);
        $filter = ['public_only' => true, 'with_holidays' => true];

        return view('calendar.public', [
            'pagetitle' => 'School Calendar',
            'anchor'    => $anchor,
            'weeks'     => $this->svc->monthGrid($anchor, $filter),
            'upcoming'  => $this->svc->occurrences(Carbon::today(), Carbon::today()->addDays(60), $filter)
                ->where('start', '>=', Carbon::today()->startOfDay())->take(20)->values(),
            'categories'=> $this->svc->categories(),
            'feedUrl'   => route('calendar.ical', ['token' => self::feedToken()]),
        ]);
    }

    public function ical(Request $request, string $token)
    {
        abort_unless(hash_equals(self::feedToken(), $token), 404);
        $from = Carbon::today()->subMonths(2);
        $to   = Carbon::today()->addMonths(12);
        $occ = $this->svc->occurrences($from, $to, ['public_only' => true, 'with_holidays' => true]);
        $body = $this->svc->ical($occ, 'School Calendar');

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="school-calendar.ics"',
        ]);
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
