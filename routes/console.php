<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Scheduler — needs ONE cPanel cron job running every minute:
|   * * * * * cd /path/to/portal && php artisan schedule:run >> /dev/null 2>&1
*/
// School notices: scheduled sends and automatic reminders.
Schedule::command('notices:dispatch')->everyMinute()->withoutOverlapping(15);

// Absence alerts, fee reminders, birthday wishes (each checks its own time).
Schedule::command('messages:auto')->everyFiveMinutes()->withoutOverlapping(30);

// Report cards to parents: generate + send in batches.
Schedule::command('results:send --max=40')->everyMinute()->withoutOverlapping(20);

// Queued jobs (e.g. fee reminders) on hosts without a permanent queue worker.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5);

// Government remittance reminders (PAYE, pension, NHF) — 8am daily
Schedule::command('payroll:remittance-reminders')->dailyAt('08:00')->withoutOverlapping(30);

// Keep a year of staff activity
Schedule::command('activity:prune --days=365')->dailyAt('02:30');

// Leave reminders (starts tomorrow, days left, resume date, not back yet)
Schedule::command('leave:reminders')->dailyAt('07:00')->withoutOverlapping(30);

// Carry unused annual leave into the new year (idempotent; runs once at year start).
Schedule::command('leave:carry-over')->yearlyOn(1, 1, '01:00')->withoutOverlapping(60);

// School calendar: send due reminders daily, and refresh fee-deadline events.
Schedule::command('calendar:sync-fees')->dailyAt('06:30')->withoutOverlapping(30);
Schedule::command('calendar:reminders')->dailyAt('07:15')->withoutOverlapping(30);

// LMS: remind learners of assignments/quizzes due soon and upcoming live classes.
Schedule::command('lms:reminders')->dailyAt('06:45')->withoutOverlapping(30);

// Financial audit digest to auditors each morning.
Schedule::command('financial-audit:digest')->dailyAt('07:45')->withoutOverlapping(30);

// Database backup: the command checks the admin's schedule and runs when due.
Schedule::command('backup:run')->everyFifteenMinutes()->withoutOverlapping(30);

// Post yesterday's school-fee receipts to the general ledger
Schedule::command('accounting:sync-fees')->dailyAt('01:30')->withoutOverlapping(30);

// Monthly depreciation on fixed assets (for the month just ended)
Schedule::command('assets:depreciate')->monthlyOn(1, '03:00')->withoutOverlapping(30);

// Pull module feature flags from the remote control portal (only if auto-pull is on)
Schedule::command('features:pull')->hourly()->withoutOverlapping(10);
