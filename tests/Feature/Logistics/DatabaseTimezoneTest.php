<?php

namespace Tests\Feature\Logistics;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** The database session runs on Lagos time, so a time Laravel saves is the moment it means. */
class DatabaseTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_saved_time_is_the_real_moment(): void
    {
        $this->assertSame('Africa/Lagos', DB::selectOne('SHOW TIME ZONE')->TimeZone);

        Schema::create('tz_probe', function ($t) {
            $t->id();
            $t->timestampTz('at');
        });
        $now = CarbonImmutable::now();
        DB::table('tz_probe')->insert(['at' => $now]);

        $row = DB::selectOne('SELECT extract(epoch FROM at)::bigint AS at, extract(epoch FROM now())::bigint AS db, at::text AS text FROM tz_probe');
        $this->assertSame($now->timestamp, (int) $row->at);
        $this->assertLessThan(5, abs($row->db - $now->timestamp));
        $this->assertStringEndsWith('+01', $row->text);
    }

    public function test_the_repair_moves_old_times_back_an_hour_and_leaves_the_rest(): void
    {
        Schema::create('tz_probe', function ($t) {
            $t->id();
            $t->timestampTz('at');
            $t->timestampTz('device_time')->nullable();
        });
        // Saved the old way: Lagos 10:00 stored as 10:00 UTC. And one saved after the fix.
        DB::insert("INSERT INTO tz_probe (at, device_time) VALUES ('2026-10-01 10:00:00+00', '2026-10-01 10:00:00+00'), ('2026-10-20 10:00:00+01', NULL)");

        $this->artisan('db:repair-lagos-times', ['--before' => '2026-10-16 09:00'])->assertSuccessful();
        $this->assertSame(1, DB::table('tz_probe')->where('at', '2026-10-01 10:00:00+00')->count(), 'dry run changes nothing');

        $this->artisan('db:repair-lagos-times', ['--before' => '2026-10-16 09:00', '--force' => true])->assertSuccessful();
        $at = DB::table('tz_probe')->orderBy('id')->pluck('at')->map(fn ($v) => CarbonImmutable::parse($v)->toIso8601String())->all();
        $this->assertSame(['2026-10-01T10:00:00+01:00', '2026-10-20T10:00:00+01:00'], $at);
        $this->assertSame('2026-10-01T10:00:00+00:00', CarbonImmutable::parse(DB::table('tz_probe')->orderBy('id')->value('device_time'))->utc()->toIso8601String());
    }
}
