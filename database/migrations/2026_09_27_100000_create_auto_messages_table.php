<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log of automatic messages (absence alerts, fee reminders, birthday wishes).
 * (type, dedupe_key, channel, recipient) is unique, so nothing is sent twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('auto_messages')) {
            Schema::create('auto_messages', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->index();          // absence | fees | birthday
                $table->string('dedupe_key', 120);             // e.g. absence:2026-10-02:123
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->string('channel', 20);
                $table->string('recipient');
                $table->string('recipient_name')->nullable();
                $table->text('body');
                $table->string('status', 20)->default('queued')->index(); // sent | failed | skipped
                $table->string('error', 500)->nullable();
                $table->string('provider_message_id')->nullable();
                $table->timestamps();

                $table->unique(['type', 'dedupe_key', 'channel', 'recipient'], 'uq_am_type_key_channel_recipient');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_messages');
    }
};
