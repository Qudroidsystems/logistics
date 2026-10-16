<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Email and SMS campaigns to customers who agreed to offers, and Google review requests after a delivery. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            // Null: the platform's own campaign. Otherwise a provider writing to its own customers.
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('name', 120);
            // email | sms
            $table->string('channel', 8);
            $table->string('subject', 150)->nullable();
            $table->text('body');
            // all | recent | lapsed | new
            $table->string('audience', 12)->default('all');
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes');
            // draft | scheduled | sending | sent | cancelled
            $table->string('status', 10)->default('draft');
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->unsignedInteger('recipients')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('operator_id');
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('destination', 190);
            // queued | sent | failed
            $table->string('status', 8)->default('queued');
            $table->timestampTz('sent_at')->nullable();
            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('review_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('operators');
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('shipment_id')->unique()->constrained('shipments');
            $table->timestampTz('send_after');
            // waiting | sent | skipped
            $table->string('status', 8)->default('waiting');
            $table->timestampTz('sent_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'send_after']);
            $table->index(['operator_id', 'customer_id']);
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->timestampTz('marketing_changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', fn (Blueprint $table) => $table->dropColumn('marketing_changed_at'));
        Schema::dropIfExists('review_requests');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('marketing_campaigns');
    }
};
