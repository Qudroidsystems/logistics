<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School calendar: activities/events across a session, term or specific dates,
 * with colour-coded categories, recurrence, per-event reminders (in-portal,
 * email, SMS, WhatsApp), attachments and RSVP. Public events can appear on a
 * no-login page and an iCal feed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('calendar_categories')) {
            Schema::create('calendar_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('slug', 90)->unique();
                $table->string('color', 20)->default('#0f766e');
                $table->string('icon', 40)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('calendar_events')) {
            Schema::create('calendar_events', function (Blueprint $table) {
                $table->id();
                $table->string('title', 180);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('color', 20)->nullable();          // overrides category colour
                $table->unsignedBigInteger('session_id')->nullable()->index();
                $table->unsignedBigInteger('term_id')->nullable()->index();
                $table->string('location', 160)->nullable();
                $table->date('start_date')->index();
                $table->date('end_date')->index();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('all_day')->default(true);
                $table->json('audiences')->nullable();            // ["staff","parents","students","public"]
                $table->boolean('is_public')->default(false)->index();
                $table->json('recurrence')->nullable();           // {freq,interval,byday[],until}
                $table->json('reminders')->nullable();            // [{days_before,channels[]}]
                $table->boolean('rsvp_enabled')->default(false);
                $table->string('source', 16)->default('manual')->index(); // manual | holiday | fee
                $table->string('source_ref', 80)->nullable();     // dedup key for auto-synced events
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['source', 'source_ref']);
            });
        }

        if (!Schema::hasTable('calendar_attachments')) {
            Schema::create('calendar_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->index();
                $table->string('path');
                $table->string('name');
                $table->string('mime', 120)->nullable();
                $table->unsignedInteger('size')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('calendar_rsvps')) {
            Schema::create('calendar_rsvps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->index();
                $table->date('occurrence_date')->nullable();      // for recurring events
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->string('response', 10)->default('going'); // going | maybe | no
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->unique(['event_id', 'occurrence_date', 'user_id', 'student_id'], 'cal_rsvp_unique');
            });
        }

        if (!Schema::hasTable('calendar_reminder_logs')) {
            Schema::create('calendar_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->index();
                $table->date('occurrence_date');
                $table->unsignedSmallInteger('days_before')->default(0);
                $table->string('channel', 12);                    // portal | email | sms | whatsapp
                $table->unsignedInteger('recipients')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->unique(['event_id', 'occurrence_date', 'days_before', 'channel'], 'cal_rem_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_reminder_logs');
        Schema::dropIfExists('calendar_rsvps');
        Schema::dropIfExists('calendar_attachments');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendar_categories');
    }
};
