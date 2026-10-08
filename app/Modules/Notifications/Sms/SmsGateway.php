<?php

namespace App\Modules\Notifications\Sms;

/** One way of sending a text message. Swap the provider in config/services.php (sms.driver) without touching callers. */
interface SmsGateway
{
    /** @return array{ok:bool,ref:?string,error:?string,provider:string} */
    public function send(string $phone, string $message): array;
}
