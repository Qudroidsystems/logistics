<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sending report cards to parents (email attachment, WhatsApp document,
 * SMS secure link).
 *
 * result_sends            one batch (session, term, classes, channels, message)
 * result_send_items       one student in the batch: readiness, PDF, secure link
 * result_send_deliveries  one message to one parent contact on one channel
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_sends')) {
            Schema::create('result_sends', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('term_id');
                $table->string('report_type', 20)->default('terminal');
                $table->json('class_ids')->nullable();
                $table->json('channels');
                $table->text('message');
                $table->string('sms_text', 612)->nullable();
                $table->boolean('include_owing')->default(false);
                $table->boolean('require_vetted')->default(true);
                $table->unsignedSmallInteger('link_days')->default(14);
                $table->string('status', 20)->default('queued')->index(); // queued|sending|sent|cancelled
                $table->unsignedInteger('students')->default(0);
                $table->unsignedInteger('skipped')->default(0);
                $table->unsignedInteger('messages_sent')->default(0);
                $table->unsignedInteger('messages_failed')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('result_send_items')) {
            Schema::create('result_send_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('result_send_id')->index();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                // pending | generating | sending | done | skipped | failed
                $table->string('status', 20)->default('pending')->index();
                $table->string('skip_reason')->nullable();
                $table->string('pdf_path')->nullable();
                $table->string('token', 64)->nullable()->unique();
                $table->timestamp('link_expires_at')->nullable();
                $table->unsignedInteger('downloads')->default(0);
                $table->timestamp('last_downloaded_at')->nullable();
                $table->string('wa_media_id')->nullable();
                $table->string('error', 500)->nullable();
                $table->timestamps();

                $table->unique(['result_send_id', 'student_id']);
            });
        }

        if (!Schema::hasTable('result_send_deliveries')) {
            Schema::create('result_send_deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('result_send_item_id');
                $table->unsignedBigInteger('result_send_id')->index();
                $table->string('channel', 20);
                $table->string('recipient');
                $table->string('recipient_name')->nullable();
                $table->string('status', 20)->default('queued')->index(); // queued|sending|sent|failed|skipped
                $table->string('error', 500)->nullable();
                $table->string('provider_message_id')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['result_send_item_id', 'channel', 'recipient'], 'uq_rsd_item_channel_recipient');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('result_send_deliveries');
        Schema::dropIfExists('result_send_items');
        Schema::dropIfExists('result_sends');
    }
};
