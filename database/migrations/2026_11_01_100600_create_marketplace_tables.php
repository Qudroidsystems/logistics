<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('operator_id')->unique()->constrained('operators');
            $table->string('public_slug', 80)->unique();
            $table->string('headline', 140)->nullable();
            $table->text('about')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->unsignedSmallInteger('years_operating')->nullable();
            $table->unsignedInteger('fleet_size')->default(0);
            $table->jsonb('service_types')->nullable();
            $table->jsonb('vehicle_types')->nullable();
            $table->jsonb('coverage')->nullable();
            $table->jsonb('languages')->nullable();
            // kyc_complete | insured | licensed
            $table->jsonb('verified_badges')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('completion_rate', 5, 2)->nullable();
            $table->decimal('on_time_rate', 5, 2)->nullable();
            $table->unsignedInteger('response_time_median_s')->nullable();
            $table->unsignedInteger('jobs_completed')->default(0);
            $table->bigInteger('min_job_value')->nullable();
            $table->bigInteger('starting_price_hint')->nullable();
            // accepting | busy | away
            $table->string('availability_status', 16)->default('accepting');
            // new | verified | trusted | preferred
            $table->string('tier', 16)->default('new');
            $table->boolean('listed')->default(false);
            $table->timestampTz('featured_until')->nullable();
            $table->timestamps();

            $table->index(['listed', 'availability_status', 'tier']);
        });

        Schema::create('provider_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->operatorId();
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types');
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->money('base');
            $table->money('per_km');
            $table->money('per_kg');
            $table->money('min_fee');
            $table->boolean('negotiable')->default(true);
            $table->boolean('instant_book')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['operator_id', 'service_type_id', 'active']);
        });

        Schema::create('provider_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->unique()->constrained('operators');
            $table->decimal('completion_rate', 5, 2)->nullable();
            $table->decimal('dispute_rate', 5, 2)->nullable();
            $table->decimal('on_time_rate', 5, 2)->nullable();
            $table->decimal('response_rate', 5, 2)->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestampTz('computed_at')->nullable();
        });

        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->foreignId('place_id')->nullable()->constrained('places');
            $table->string('name');
            $table->jsonb('opening_hours')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ---- Requests and negotiation -------------------------------------------------
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('customer_id')->constrained('users');
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            // parcel | freight | errand | shopping | moving | bulk
            $table->string('type', 16);
            $table->foreignId('service_type_id')->nullable()->constrained('service_types');
            $table->jsonb('stops');
            $table->jsonb('packages')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->bigInteger('budget_min')->nullable();
            $table->bigInteger('budget_max')->nullable();
            $table->timestampTz('needed_by')->nullable();
            // direct | invited | open
            $table->string('visibility', 12)->default('direct');
            // open | negotiating | agreed | cancelled | expired
            $table->string('status', 16)->default('open');
            $table->boolean('is_test')->default(false);
            $table->timestampTz('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['status', 'visibility', 'created_at']);
        });

        Schema::create('request_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->operatorId();
            // sent | viewed | declined | countered | accepted
            $table->string('status', 16)->default('sent');
            $table->timestamps();

            $table->unique(['request_id', 'operator_id']);
        });

        Schema::create('negotiation_threads', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->operatorId();
            $table->foreignId('customer_id')->constrained('users');
            $table->string('status', 16)->default('open');
            $table->timestamps();

            $table->unique(['request_id', 'operator_id']);
        });

        Schema::create('negotiation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('negotiation_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users');
            // text | counter_offer | accept | reject | attachment
            $table->string('kind', 16);
            $table->text('body')->nullable();
            $table->jsonb('terms')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['thread_id', 'created_at']);
        });

        // ---- Merchants (ecommerce partners) -------------------------------------------
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('operator_id')->unique()->constrained('operators');
            // The public registered ID shown in partner apps, e.g. GM-000123. Never reused.
            $table->string('merchant_code', 24)->unique();
            $table->string('display_name');
            $table->string('website')->nullable();
            $table->string('industry', 60)->nullable();
            $table->string('logo')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 24)->nullable();
            $table->unsignedBigInteger('pickup_address_id')->nullable();
            // pending | verified | restricted | suspended
            $table->string('status', 16)->default('pending');
            // prepaid_wallet | pay_at_checkout | monthly_invoice
            $table->string('settlement_mode', 20)->default('pay_at_checkout');
            $table->timestampTz('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('api_clients', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->nullOnDelete();
        });
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->nullOnDelete();
        });

        Schema::create('merchant_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->unique()->constrained('merchants')->cascadeOnDelete();
            $table->string('marketplace_display_name');
            $table->string('badge_text')->nullable();
            $table->string('badge_logo')->nullable();
            $table->boolean('show_merchant_code')->default(true);
            $table->boolean('show_provider_name')->default(true);
            $table->jsonb('theme')->nullable();
            $table->string('tracking_subdomain')->nullable()->unique();
            $table->string('sender_name', 24)->nullable();
            $table->jsonb('email_template_overrides')->nullable();
            $table->timestamps();
        });

        Schema::create('merchant_provider_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->unique()->constrained('merchants')->cascadeOnDelete();
            // auto | preferred_list | customer_choice | contracted
            $table->string('mode', 20)->default('auto');
            $table->jsonb('preferred_operator_ids')->nullable();
            $table->jsonb('exclude_operator_ids')->nullable();
            $table->bigInteger('max_price')->nullable();
            $table->decimal('min_rating', 3, 2)->nullable();
            $table->boolean('fallback_to_auto')->default(true);
            $table->timestamps();
        });

        Schema::create('standing_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('provider_operator_id')->constrained('operators');
            $table->foreignId('rate_card_id')->nullable()->constrained('provider_rate_cards');
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_to')->nullable();
            $table->unsignedInteger('volume_commitment')->nullable();
            $table->string('payment_terms', 40)->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // ---- Agreements and escrow ----------------------------------------------------
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('number', 24)->unique();
            $table->foreignId('request_id')->nullable()->constrained('service_requests');
            $table->foreignId('thread_id')->nullable()->constrained('negotiation_threads');
            $table->foreignId('standing_agreement_id')->nullable()->constrained('standing_agreements');
            $table->foreignId('customer_id')->constrained('users');
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->foreignId('provider_operator_id')->constrained('operators');
            $table->jsonb('terms');
            $table->unsignedInteger('terms_version')->default(1);
            $table->unsignedInteger('distance_m')->nullable();
            $table->money('price');
            // Goods money a shopper may spend; held in escrow but never commissionable.
            $table->money('goods_budget');
            $table->money('tip');
            $table->money('platform_fee');
            $table->foreignId('fee_rule_id')->nullable()->constrained('commission_rules');
            $table->money('provider_net');
            $table->jsonb('cancellation_policy')->nullable();
            $table->unsignedSmallInteger('confirmation_window_hours')->default(24);
            // draft | awaiting_customer | awaiting_provider | locked | paid | in_progress | delivered |
            // completed | disputed | cancelled
            $table->string('status', 24)->default('draft')->index();
            $table->timestampTz('customer_signed_at')->nullable();
            $table->timestampTz('provider_signed_at')->nullable();
            $table->timestampTz('locked_at')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['provider_operator_id', 'status']);
        });

        Schema::create('agreement_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->jsonb('terms');
            $table->bigInteger('price');
            $table->foreignId('proposed_by')->nullable()->constrained('users');
            $table->timestampTz('accepted_by_customer_at')->nullable();
            $table->timestampTz('accepted_by_provider_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['agreement_id', 'version']);
        });

        Schema::create('escrow_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->unique()->constrained('agreements');
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts');
            $table->bigInteger('amount');
            $table->bigInteger('released_amount')->default(0);
            $table->bigInteger('refunded_amount')->default(0);
            // held | partially_released | released | refunded | frozen
            $table->string('status', 20)->default('held');
            $table->timestampTz('release_after')->nullable();
            $table->string('frozen_reason')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->index(['status', 'release_after']);
        });

        // ---- Shopping errands ---------------------------------------------------------
        Schema::create('shopping_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->unique()->constrained('agreements');
            $table->foreignId('market_id')->nullable()->constrained('markets');
            $table->jsonb('list');
            $table->bigInteger('budget_cap');
            $table->string('substitution_policy', 16)->default('ask');
            $table->boolean('receipt_required')->default(true);
            $table->bigInteger('actual_spend')->nullable();
            $table->string('status', 16)->default('open');
            $table->timestamps();
        });

        Schema::create('shopper_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->unsignedBigInteger('ledger_transaction_id')->nullable();
            $table->bigInteger('amount');
            $table->string('status', 16)->default('issued');
            $table->timestampTz('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shopping_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_request_id')->constrained('shopping_requests')->cascadeOnDelete();
            $table->string('vendor_name');
            $table->jsonb('items')->nullable();
            $table->bigInteger('amount');
            $table->string('photo_path');
            $table->boolean('verified_by_customer')->default(false);
            $table->timestampTz('uploaded_at')->useCurrent();
        });

        Schema::create('budget_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_request_id')->constrained('shopping_requests')->cascadeOnDelete();
            $table->bigInteger('extra_amount');
            $table->string('reason')->nullable();
            $table->timestampTz('approved_by_customer_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', fn (Blueprint $t) => $t->dropForeign(['merchant_id']));
        Schema::table('service_requests', fn (Blueprint $t) => $t->dropForeign(['merchant_id']));
        foreach ([
            'budget_amendments', 'shopping_receipts', 'shopper_advances', 'shopping_requests', 'escrow_holds',
            'agreement_versions', 'agreements', 'standing_agreements', 'merchant_provider_preferences',
            'merchant_branding', 'merchants', 'negotiation_messages', 'negotiation_threads',
            'request_invitations', 'service_requests', 'markets', 'provider_scores',
            'provider_rate_cards', 'provider_profiles',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
