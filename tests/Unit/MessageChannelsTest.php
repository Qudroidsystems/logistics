<?php

namespace Tests\Unit;

use App\Modules\Notifications\Sms\SmsService;
use PHPUnit\Framework\TestCase;

/** Which channels a phone notice goes out on, depending on SMS_WHATSAPP and whether WhatsApp is set up. */
class MessageChannelsTest extends TestCase
{
    public function test_texts_only_until_whatsapp_is_set_up(): void
    {
        foreach (['instead', 'also', 'off'] as $mode) {
            $this->assertSame(['sms'], SmsService::channels($mode, false), $mode);
        }
    }

    public function test_once_whatsapp_is_set_up(): void
    {
        $this->assertSame(['whatsapp'], SmsService::channels('instead', true));
        $this->assertSame(['sms', 'whatsapp'], SmsService::channels('also', true));
        $this->assertSame(['sms'], SmsService::channels('off', true));
        $this->assertSame(['sms'], SmsService::channels('nonsense', true), 'an unknown setting keeps plain texts');
    }
}
