<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduler — needs ONE cron job running every minute:
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
*/

// Keep a year of platform activity.
Schedule::command('activity:prune --days=365')->dailyAt('02:30');

// Keep two years of data changes (who changed what).
Schedule::command('audit:prune --days=730')->dailyAt('02:40');

// Housekeeping for old in-app notifications.
Schedule::command('app:cleanup-old-notifications')->dailyAt('03:15');

// Database backup: the command checks the admin's schedule and runs when due.
Schedule::command('backup:run')->everyFifteenMinutes()->withoutOverlapping(30);

// Pull module feature flags from the remote control portal (only if auto-pull is on).
Schedule::command('features:pull')->hourly()->withoutOverlapping(10);

// Queued jobs on hosts without a permanent queue worker.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5);

// Logistics: keep time partitions ahead of the data, and drop expired raw GPS points.
Schedule::command('partitions:maintain')->dailyAt('01:10')->withoutOverlapping(30);

// Marketplace: release escrow once the customer's confirmation window has passed with no objection.
Schedule::command('escrow:auto-release')->everyFiveMinutes()->withoutOverlapping(10);

// Marketplace: weekly provider statements, and housekeeping for agreements nobody paid.
Schedule::command('settlements:generate')->weeklyOn(1, '03:00')->withoutOverlapping(60);
Schedule::command('agreements:expire-unpaid')->hourly()->withoutOverlapping(10);

// Business accounts: bill last month's finished deliveries on the 1st.
Schedule::command('invoices:monthly')->monthlyOn(1, '06:00')->withoutOverlapping(60);

// Providers: refresh ratings, completion and dispute rates, score and tier every night.
Schedule::command('providers:refresh-scores')->dailyAt('04:00')->withoutOverlapping(30);

// Payments: confirm online payments whose webhook never arrived.
Schedule::command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping(10);

// Bookings for later: offer them to drivers shortly before the customer's pickup time.
Schedule::command('dispatch:scheduled')->everyMinute()->withoutOverlapping(5);

// Cash on delivery: tell providers when a rider has held collected cash past the hand-in time.
Schedule::command('cod:flag-overdue')->hourly()->withoutOverlapping(10);

// Accounting: send yesterday's wallet journal to each provider's QuickBooks or Xero.
Schedule::command('accounting:sync')->dailyAt('02:30')->withoutOverlapping(60);

// Marketing: send campaigns in batches, and ask for Google reviews after finished deliveries.
Schedule::command('campaigns:send')->everyMinute()->withoutOverlapping(10);
Schedule::command('reviews:send')->everyTenMinutes()->withoutOverlapping(10);

// Insurance, roadworthiness, servicing and driver documents coming due (30 days, 7 days, on the day, once overdue).
Schedule::command('fleet:reminders')->dailyAt('07:10')->withoutOverlapping(30);

// Interac e-Transfers that never arrived within the hold time (Payment Gateways > Interac e-Transfer).
Schedule::command('etransfer:expire')->hourly()->withoutOverlapping(10);
