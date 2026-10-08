<?php

namespace Tests\Feature\Logistics;

use App\Modules\Payments\BankDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BankDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.paystack' => ['mode' => 'test', 'test_secret_key' => 'sk_test_x', 'secret_key' => 'sk_test_x']]);
    }

    public function test_uses_the_paystack_list_and_caches_it(): void
    {
        Http::fake(['api.paystack.co/bank*' => Http::response(['status' => true, 'data' => [
            ['name' => 'Zed Bank', 'code' => '999', 'active' => true, 'currency' => 'NGN'],
            ['name' => 'Alpha Bank', 'code' => '111', 'active' => true, 'currency' => 'NGN'],
            ['name' => 'Closed Bank', 'code' => '222', 'active' => false, 'currency' => 'NGN'],
            ['name' => 'Dollar Bank', 'code' => '333', 'active' => true, 'currency' => 'USD'],
        ]])]);

        $dir = app(BankDirectory::class);
        $this->assertSame(['111' => 'Alpha Bank', '999' => 'Zed Bank'], $dir->all());
        $this->assertSame('Alpha Bank', $dir->name('111'));
        $this->assertNull($dir->name('222'));
        $dir->all();
        Http::assertSentCount(1);
    }

    public function test_falls_back_when_paystack_is_down_or_unconfigured(): void
    {
        Http::fake(['api.paystack.co/bank*' => Http::response([], 500)]);
        $this->assertSame(BankDirectory::FALLBACK, app(BankDirectory::class)->all());

        Cache::flush();
        config(['services.paystack' => ['mode' => 'test']]);
        $this->assertSame(BankDirectory::FALLBACK, app(BankDirectory::class)->all());
    }
}
