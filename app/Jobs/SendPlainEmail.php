<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/** A plain-text transactional email (invitations, password reset codes). Nothing is logged but the failure. */
class SendPlainEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public string $to, public string $subject, public string $body)
    {
    }

    public function handle(): void
    {
        Mail::raw($this->body."\n\n— ".config('app.name'), fn ($m) => $m->to($this->to)->subject($this->subject));
    }
}
