<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AuthController;
use App\Modules\Notifications\Sms\SmsService;
use App\Support\Money;
use App\Support\Phone;
use App\Support\Region;
use Tests\TestCase;

/** Money, phone numbers and tax under the Nigerian and Canadian presets (config/region-presets.php). No database. */
class RegionTest extends TestCase
{
    private function useRegion(string $code): void
    {
        $p = (require base_path('config/region-presets.php'))[$code];
        config(['region' => [
            'country' => $p['country'], 'country_name' => $p['country_name'], 'currency' => $p['currency'], 'symbol' => $p['symbol'],
            'locale' => $p['locale'], 'calling_code' => $p['calling_code'], 'trunk_prefix' => $p['trunk_prefix'], 'store_local' => $p['store_local'],
            'phone_example' => $p['phone_example'], 'map' => $p['map'], 'subdivision_label' => $p['subdivision_label'],
            'subdivisions' => $p['subdivisions'], 'tax' => $p['tax'],
        ]]);
    }

    public function test_money_in_canadian_dollars(): void
    {
        $this->useRegion('CA');
        $this->assertSame('$12', Money::format(1200));
        $this->assertSame('$12.50', Money::format(1250));
        $this->assertSame('$1,234.05', Money::format(123405));
        $this->assertSame('$12.00', Money::format(1200, true));
        $this->assertSame('-$3.50', Money::format(-350));
        $this->assertSame('$1,235', Money::whole(123450));
        $this->assertSame(1250, Money::toMinor('$12.50'));
        $this->assertSame(123400, Money::toMinor('1,234'));
        $this->assertNull(Money::toMinor('abc'));
        $this->assertSame('CAD', Money::forClient()['currency']);
    }

    public function test_money_in_naira_is_unchanged(): void
    {
        $this->useRegion('NG');
        $this->assertSame('₦12,500', Money::format(1_250_000));
        $this->assertSame('₦12,500.50', Money::format(1_250_050));
    }

    public function test_canadian_phone_numbers(): void
    {
        $this->useRegion('CA');
        foreach (['+1 (226) 339-8404', '226-339-8404', '2263398404', '1 226 339 8404', '(226) 339 8404'] as $raw) {
            $this->assertSame('12263398404', Phone::international($raw), $raw);
            $this->assertSame('+12263398404', Phone::store($raw), $raw);
            $this->assertSame('12263398404', SmsService::normalize($raw), $raw);
        }
        $this->assertSame('+12263398404', AuthController::normalizePhone('226.339.8404'));
        $this->assertNull(Phone::international('123 456 7890'), 'area codes never start with 1');
        $this->assertNull(Phone::international('339-8404'), 'too short');
        $this->assertSame('2348031234567', Phone::international('+234 803 123 4567'), 'a Nigerian number written with + still works');
        $this->assertSame('+1 (226) 339-8404', Phone::display('+12263398404'));
    }

    public function test_nigerian_phone_numbers_keep_their_old_form(): void
    {
        $this->useRegion('NG');
        foreach (['0803 123 4567', '8031234567', '+2348031234567', '2348031234567'] as $raw) {
            $this->assertSame('2348031234567', Phone::international($raw), $raw);
            $this->assertSame('08031234567', Phone::store($raw), $raw);
        }
        $this->assertSame('08012345678', AuthController::normalizePhone('+234 801 234 5678'));
        $this->assertSame('08012345678', AuthController::normalizePhone('0801-234-5678'));
        $this->assertSame('12263398404', Phone::international('+1 226 339 8404'), 'a Canadian number written with + still works');
        $this->assertSame('+12263398404', Phone::store('+1 226 339 8404'));
    }

    public function test_sales_tax_by_province(): void
    {
        $this->useRegion('CA');
        $this->assertSame(['name' => 'HST', 'rate_bp' => 1300], Region::taxFor('Ontario'));
        $this->assertSame(['name' => 'HST', 'rate_bp' => 1300], Region::taxFor('ON'));
        $this->assertSame(['name' => 'HST', 'rate_bp' => 1400], Region::taxFor('Nova Scotia'));
        $this->assertSame(['name' => 'GST', 'rate_bp' => 500], Region::taxFor('Alberta'));
        $this->assertSame(['name' => 'HST', 'rate_bp' => 1300], Region::taxFor(null), 'unknown: the region default');
        $this->assertSame('ON', Region::subdivisionCode('ontario'));
    }
}
