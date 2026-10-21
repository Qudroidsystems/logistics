<?php

namespace Tests\Unit;

use App\Modules\Tracking\FleetService;
use App\Modules\Tracking\LocationIngestService;
use PHPUnit\Framework\TestCase;

/** How the live fleet map reads a driver, and when a speed counts as speeding. Pure rules, no database. */
class FleetTest extends TestCase
{
    private function row(array $over = []): object
    {
        return (object) ($over + [
            'public_id' => '01JDRIVER00000000000000000', 'name' => 'Ada Rider', 'handle' => 'ada', 'phone' => '08012345678',
            'company' => 'Swift', 'operator_id' => '01JOPERATOR000000000000000', 'status' => 'active', 'availability' => 'online',
            'lat' => 6.5, 'lng' => 3.38, 'last_seen_at' => date(DATE_ATOM, 1_000_000 - 30), 'speed_mps' => 10.0, 'heading' => 90.0,
            'battery_pct' => 64, 'plate' => 'LAG-123', 'vehicle_type' => 'motorbike', 'rating_avg' => '4.60',
            'job_id' => null, 'job_status' => null, 'job_order' => null, 'job_order_id' => null,
        ]);
    }

    public function test_a_fresh_online_driver(): void
    {
        $d = FleetService::present($this->row(), 80, 1_000_000);

        $this->assertSame('online', $d['state']);
        $this->assertSame(36, $d['speed_kmh']);
        $this->assertFalse($d['speeding']);
        $this->assertSame(30, $d['seen_ago_s']);
        $this->assertSame('LAG-123 (motorbike)', $d['vehicle']);
        $this->assertNull($d['job']);
    }

    public function test_speeding_needs_a_fresh_position(): void
    {
        $this->assertTrue(FleetService::present($this->row(['speed_mps' => 25.0]), 80, 1_000_000)['speeding'], '90 km/h is over 80');

        $old = FleetService::present($this->row(['speed_mps' => 25.0, 'last_seen_at' => date(DATE_ATOM, 1_000_000 - 600)]), 80, 1_000_000);
        $this->assertSame('no_signal', $old['state'], 'online but silent for 10 minutes');
        $this->assertFalse($old['speeding'], 'an old speed is not shown as live');
        $this->assertNull($old['speed_kmh']);
    }

    public function test_states(): void
    {
        $this->assertSame('offline', FleetService::state('offline', true));
        $this->assertSame('offline', FleetService::state('offline', false), 'offline drivers are never "no signal"');
        $this->assertSame('on_job', FleetService::state('on_job', true));
        $this->assertSame('no_signal', FleetService::state('on_job', false));
        $this->assertSame('break', FleetService::state('break', true));
    }

    public function test_a_driver_who_never_sent_a_position(): void
    {
        $d = FleetService::present($this->row(['lat' => null, 'lng' => null, 'last_seen_at' => null, 'speed_mps' => null]), 80, 1_000_000);

        $this->assertNull($d['lat']);
        $this->assertSame('no_signal', $d['state']);
        $this->assertNull($d['seen_ago_s']);
    }

    public function test_a_driver_on_a_job_links_to_it(): void
    {
        $d = FleetService::present($this->row(['availability' => 'on_job', 'job_id' => '01JSHIP', 'job_status' => 'in_transit', 'job_order' => 'QL-1001', 'job_order_id' => '01JORDER']), 80, 1_000_000);

        $this->assertSame(['id' => '01JSHIP', 'status' => 'in_transit', 'order' => 'QL-1001', 'order_id' => '01JORDER'], $d['job']);
    }

    public function test_over_the_limit(): void
    {
        $this->assertNull(LocationIngestService::overLimit(null, 80));
        $this->assertNull(LocationIngestService::overLimit(22.2, 80), '79.9 km/h is within 80');
        $this->assertSame(90, LocationIngestService::overLimit(25.0, 80));
    }
}
