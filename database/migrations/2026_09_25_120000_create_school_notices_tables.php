<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School notices (CA tests, exams, holidays, midterm, meetings…) sent to
 * parents/staff by SMS, WhatsApp and email.
 *
 * messaging_settings  provider settings per channel (entered by the admin)
 * school_notices      the notice itself: content, audience, channels, timing
 * notice_dispatches   one scheduled "send" of a notice (initial or reminder)
 * notice_deliveries   one message to one contact on one channel (the log)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('messaging_settings')) {
            Schema::create('messaging_settings', function (Blueprint $table) {
                $table->id();
                $table->string('channel', 20)->unique();     // sms | whatsapp | email
                $table->string('driver', 30)->default('log');
                $table->boolean('is_active')->default(false);
                $table->json('config')->nullable();          // secrets encrypted
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('school_notices')) {
            Schema::create('school_notices', function (Blueprint $table) {
                $table->id();
                $table->string('type', 30)->default('general');
                $table->string('title');
                $table->text('message');                      // full text (email / WhatsApp)
                $table->string('sms_text', 918)->nullable();  // short version (≤ 6 SMS pages)
                $table->date('event_date')->nullable();
                $table->date('event_end_date')->nullable();
                $table->string('event_time', 20)->nullable();
                $table->json('audience');                     // {scope, class_ids, category_ids, student_ids, include_staff}
                $table->json('channels');                     // ["sms","whatsapp","email"]
                $table->json('reminders')->nullable();        // [{"days":3,"time":"08:00"}, …]
                $table->string('status', 20)->default('draft')->index(); // draft|scheduled|sending|sent|cancelled
                $table->timestamp('send_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notice_dispatches')) {
            Schema::create('notice_dispatches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_notice_id')->index();
                $table->string('kind', 20)->default('initial');   // initial | reminder | test | resend
                $table->string('label')->nullable();               // "3 days before"
                $table->timestamp('run_at')->index();
                $table->string('status', 20)->default('pending')->index(); // pending|processing|done|cancelled|failed
                $table->unsignedInteger('total')->default(0);
                $table->unsignedInteger('sent')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('skipped')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->string('error')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notice_deliveries')) {
            Schema::create('notice_deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('notice_dispatch_id');
                $table->unsignedBigInteger('school_notice_id')->index();
                $table->string('channel', 20);
                $table->string('recipient');                        // phone (234…) or email
                $table->string('recipient_name')->nullable();
                $table->string('audience_type', 10)->default('parent'); // parent | staff | test
                $table->json('student_ids')->nullable();
                $table->text('body');                               // exact text sent
                $table->string('status', 20)->default('queued')->index(); // queued|sent|failed|skipped
                $table->string('error', 500)->nullable();
                $table->string('provider_message_id')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['notice_dispatch_id', 'channel', 'recipient'], 'uq_nd_dispatch_channel_recipient');
                $table->index(['notice_dispatch_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_deliveries');
        Schema::dropIfExists('notice_dispatches');
        Schema::dropIfExists('school_notices');
        Schema::dropIfExists('messaging_settings');
    }
};
