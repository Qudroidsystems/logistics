<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->foreignId('payer_id')->nullable()->constrained('users');
            // paystack | opay | flutterwave | wallet | bank_transfer | cash
            $table->string('gateway', 16);
            $table->bigInteger('amount');
            $table->bigInteger('fee')->default(0);
            $table->char('currency', 3)->default('NGN');
            $table->string('reference', 80)->unique();
            // initiated | pending | succeeded | failed | abandoned | reversed
            $table->string('status', 16)->default('initiated');
            $table->string('authorization_code', 80)->nullable();
            $table->string('channel', 24)->nullable();
            $table->jsonb('raw')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestampTz('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('gateway_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 16);
            $table->string('event_id', 120);
            $table->string('type', 64);
            $table->jsonb('payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestampTz('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('gateway', 16);
            $table->string('token');
            $table->string('brand', 24)->nullable();
            $table->string('last4', 4)->nullable();
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->foreignId('payment_intent_id')->constrained('payment_intents');
            $table->bigInteger('amount');
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('ledger_tx_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->bigInteger('amount');
            $table->bigInteger('fee')->default(0);
            // requested | approved | processing | paid | failed | cancelled
            $table->string('status', 16)->default('requested');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('gateway_transfer_ref', 80)->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedBigInteger('ledger_tx_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('payment_intent_id')->nullable()->constrained('payment_intents');
            $table->bigInteger('amount');
            $table->string('reason_code', 40);
            $table->foreignId('initiated_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('status', 16)->default('requested');
            $table->unsignedBigInteger('ledger_tx_id')->nullable();
            $table->string('gateway_ref', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->operatorId();
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules');
            $table->jsonb('rule_snapshot')->nullable();
            $table->bigInteger('gross_amount');
            $table->bigInteger('platform_fee');
            $table->bigInteger('operator_net');
            $table->bigInteger('driver_net')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->unsignedBigInteger('ledger_tx_id')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamps();

            $table->unique('shipment_id');
        });

        Schema::create('driver_earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->money('base');
            $table->money('distance');
            $table->money('time');
            $table->money('wait');
            $table->money('tip');
            $table->money('incentive');
            $table->money('deductions');
            $table->bigInteger('total');
            // pending | available | paid
            $table->string('status', 12)->default('pending');
            $table->timestampTz('available_at')->nullable();
            $table->unsignedBigInteger('ledger_tx_id')->nullable();
            $table->timestamps();

            $table->index(['driver_profile_id', 'status']);
        });

        Schema::create('cod_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->operatorId();
            $table->bigInteger('amount');
            $table->timestampTz('collected_at');
            $table->timestampTz('remit_due_at');
            $table->timestampTz('remitted_at')->nullable();
            // wallet_deduction | bank_deposit | hub_deposit
            $table->string('remit_method', 20)->nullable();
            // collected | overdue | remitted | written_off
            $table->string('status', 16)->default('collected');
            $table->string('cash_proof')->nullable();
            $table->timestamps();

            $table->index(['status', 'remit_due_at']);
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('gross')->default(0);
            $table->bigInteger('commission')->default(0);
            $table->bigInteger('adjustments')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('net')->default(0);
            // draft | approved | paid
            $table->string('status', 12)->default('draft');
            $table->string('statement_path')->nullable();
            $table->timestamps();

            $table->unique(['operator_id', 'period_start', 'period_end']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('commission_id')->nullable()->constrained('commissions');
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->bigInteger('amount');
        });

        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 16);
            $table->date('run_date');
            $table->bigInteger('expected_total');
            $table->bigInteger('settled_total');
            $table->bigInteger('fees')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->string('status', 12)->default('open');
            $table->timestamps();

            $table->unique(['gateway', 'run_date']);
        });

        Schema::create('reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('reconciliation_runs')->cascadeOnDelete();
            $table->string('gateway_ref', 80);
            $table->foreignId('payment_intent_id')->nullable()->constrained('payment_intents');
            // matched | missing_internal | missing_gateway | amount_mismatch
            $table->string('match_status', 20);
            $table->bigInteger('gateway_amount')->nullable();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->unsignedBigInteger('business_account_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('users');
            $table->string('number', 32);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->jsonb('lines');
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total');
            $table->string('status', 12)->default('draft');
            $table->date('due_on')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['operator_id', 'number']);
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->string('type', 16)->default('VAT');
            $table->unsignedSmallInteger('rate_bp');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['tax_rates', 'invoices', 'reconciliation_items', 'reconciliation_runs', 'settlement_items',
                  'settlements', 'cod_collections', 'driver_earnings', 'commissions', 'refunds', 'payout_requests',
                  'wallet_topups', 'payment_methods', 'gateway_events', 'payment_intents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
