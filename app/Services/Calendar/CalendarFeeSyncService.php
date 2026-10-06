<?php

namespace App\Services\Calendar;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns fee due-dates into calendar events so they show on the calendar and
 * can carry reminders. Sources:
 *   - school_bill.due_date            -> source_ref "bill:{id}"
 *   - fee_instalment_plans.schedule[] -> source_ref "plan:{planId}:{index}"
 * Idempotent: existing fee events are updated in place; ones whose source no
 * longer exists are removed. Only auto-fills date/title/scope — never touches
 * manually edited reminders on an event that an admin later customised.
 */
class CalendarFeeSyncService
{
    /** @return array{created:int,updated:int,removed:int} */
    public function sync(): array
    {
        if (!Schema::hasTable('calendar_events')) return ['created' => 0, 'updated' => 0, 'removed' => 0];

        $cat = $this->category();
        $seen = [];
        $created = 0; $updated = 0;

        // Default reminders for fee deadlines: portal + email a week and a day before.
        $defaultReminders = [
            ['days_before' => 7, 'channels' => ['portal', 'email']],
            ['days_before' => 1, 'channels' => ['portal', 'email']],
        ];

        // 1) school_bill.due_date
        if (Schema::hasTable('school_bill') && Schema::hasColumn('school_bill', 'due_date')) {
            $bills = DB::table('school_bill')->whereNotNull('due_date')->get();
            foreach ($bills as $b) {
                $ref = 'bill:' . $b->id;
                $seen[] = $ref;
                $title = 'Fee due: ' . ($b->title ?? 'School bill');
                [$c, $u] = $this->upsert($ref, [
                    'title' => $title, 'description' => $b->description ?? null,
                    'category_id' => $cat?->id, 'start_date' => Carbon::parse($b->due_date)->toDateString(),
                    'end_date' => Carbon::parse($b->due_date)->toDateString(),
                ], $defaultReminders);
                $created += $c; $updated += $u;
            }
        }

        // 2) fee_instalment_plans.schedule
        if (Schema::hasTable('fee_instalment_plans')) {
            $plans = DB::table('fee_instalment_plans')->where('is_active', true)->get();
            foreach ($plans as $p) {
                $schedule = json_decode($p->schedule ?? '[]', true) ?: [];
                foreach ($schedule as $i => $item) {
                    if (empty($item['due_date'])) continue;
                    $ref = 'plan:' . $p->id . ':' . $i;
                    $seen[] = $ref;
                    $label = $item['label'] ?? ('Instalment ' . ($i + 1));
                    [$c, $u] = $this->upsert($ref, [
                        'title' => 'Fee instalment: ' . $p->name . ' — ' . $label,
                        'description' => isset($item['percent']) ? ($item['percent'] . '% due') : null,
                        'category_id' => $cat?->id,
                        'session_id' => $p->session_id ?? null, 'term_id' => $p->term_id ?? null,
                        'start_date' => Carbon::parse($item['due_date'])->toDateString(),
                        'end_date' => Carbon::parse($item['due_date'])->toDateString(),
                    ], $defaultReminders);
                    $created += $c; $updated += $u;
                }
            }
        }

        // 3) remove fee events that no longer have a source
        $removed = 0;
        $stale = CalendarEvent::where('source', 'fee')
            ->when($seen, fn ($q) => $q->whereNotIn('source_ref', $seen))
            ->get();
        foreach ($stale as $e) { $e->delete(); $removed++; }

        return ['created' => $created, 'updated' => $updated, 'removed' => $removed];
    }

    /** @return array{0:int,1:int} [created, updated] */
    protected function upsert(string $ref, array $attrs, array $defaultReminders): array
    {
        $existing = CalendarEvent::where('source', 'fee')->where('source_ref', $ref)->first();
        if ($existing) {
            // Preserve any admin customisation of reminders/audiences; refresh the facts.
            $existing->fill($attrs);
            $existing->save();
            return [0, 1];
        }
        CalendarEvent::create(array_merge($attrs, [
            'all_day' => true,
            'audiences' => ['parents', 'students'],
            'is_public' => false,
            'reminders' => $defaultReminders,
            'source' => 'fee', 'source_ref' => $ref, 'is_active' => true,
        ]));
        return [1, 0];
    }

    protected function category(): ?CalendarCategory
    {
        if (!Schema::hasTable('calendar_categories')) return null;
        return CalendarCategory::firstOrCreate(
            ['slug' => 'fee-deadline'],
            ['name' => 'Fee deadline', 'color' => '#b45309', 'icon' => 'ri-money-dollar-circle-line', 'is_active' => true, 'sort' => 60]
        );
    }
}
