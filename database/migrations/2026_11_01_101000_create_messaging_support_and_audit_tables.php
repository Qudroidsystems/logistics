<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('key', 80);
            // push | sms | email | whatsapp | in_app
            $table->string('channel', 10);
            $table->string('locale', 8)->default('en');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->jsonb('variables')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 12)->default('active');
            $table->timestamps();

            $table->unique(['operator_id', 'key', 'channel', 'locale'], 'notification_templates_unique');
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('template_key', 80)->nullable();
            $table->string('channel', 10);
            // queued | sent | delivered | failed | read
            $table->string('status', 10)->default('queued');
            $table->string('provider', 24)->nullable();
            $table->string('provider_ref', 80)->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('cost_kobo')->default(0);
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('event', 64);
            $table->string('audience', 16);
            $table->jsonb('channel_order');
            $table->jsonb('quiet_hours')->nullable();
            $table->string('fallback_channel', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            // customer_driver | customer_support | vendor_driver | dispatcher_driver | negotiation
            $table->string('type', 20);
            $table->string('status', 10)->default('open');
            $table->timestampTz('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestampTz('last_read_at')->nullable();

            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users');
            // text | image | voice | location | system
            $table->string('kind', 10)->default('text');
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->boolean('moderation_flag')->default(false);
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('masked_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->string('proxy_number', 24);
            $table->jsonb('participants');
            $table->timestampTz('expires_at');
        });

        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->string('from_number', 24);
            $table->string('to_number', 24);
            $table->string('provider', 24)->nullable();
            $table->unsignedInteger('duration_s')->default(0);
            $table->string('recording_path')->nullable();
            $table->string('outcome', 16)->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->string('number', 20)->unique();
            $table->foreignId('requester_id')->constrained('users');
            // app | web | whatsapp | phone | email
            $table->string('channel', 10);
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->string('category', 32);
            $table->string('priority', 8)->default('normal');
            // new | open | pending_customer | pending_ops | resolved | closed
            $table->string('status', 16)->default('new');
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->timestampTz('sla_first_response_due')->nullable();
            $table->timestampTz('sla_resolve_due')->nullable();
            $table->timestampTz('first_responded_at')->nullable();
            $table->unsignedSmallInteger('csat_score')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'status', 'priority']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users');
            $table->boolean('internal')->default(false);
            $table->text('body');
            $table->jsonb('attachments')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->string('rater_type', 12);
            $table->unsignedBigInteger('rater_id');
            // driver | operator | customer
            $table->string('ratee_type', 12);
            $table->unsignedBigInteger('ratee_id');
            $table->unsignedSmallInteger('score');
            $table->jsonb('tags')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('moderation_status', 12)->default('approved');
            $table->timestamps();

            $table->unique(['shipment_id', 'rater_type', 'rater_id', 'ratee_type', 'ratee_id'], 'ratings_unique');
            $table->index(['ratee_type', 'ratee_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('action', 64);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id', 40)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['operator_id', 'created_at']);
            $table->index('action');
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('version', 16);
            $table->timestampTz('accepted_at');

            $table->unique(['user_id', 'type', 'version']);
        });

        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type', 40);
            $table->unsignedBigInteger('aggregate_id');
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('event', 64);
            $table->jsonb('payload');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('published_at')->nullable();

            $table->index('published_at');
            $table->index(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120);
            $table->string('scope', 80);
            $table->string('request_hash', 64);
            $table->jsonb('response')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestampTz('expires_at');
            $table->timestamps();

            $table->unique(['scope', 'key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        foreach (['idempotency_keys', 'outbox_events', 'consents', 'audit_logs', 'ratings', 'ticket_messages',
                  'support_tickets', 'call_logs', 'masked_numbers', 'messages', 'conversation_participants',
                  'conversations', 'notification_rules', 'notification_deliveries', 'notification_templates'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
