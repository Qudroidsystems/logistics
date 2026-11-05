<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cards a customer chose to keep (ticked "Save this card" on a wallet top-up), and every off-session charge made on
 * one. The card itself stays with the gateway: we keep only its token (Stripe payment method + customer, Paystack
 * authorization code), encrypted, and what to show (brand, last 4, expiry).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_cards', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('gateway', 16);
            $table->text('token');
            $table->text('customer_ref')->nullable();
            $table->string('email', 160)->nullable();
            $table->string('brand', 24)->nullable();
            $table->string('last4', 4)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->string('fingerprint', 64)->nullable();
            // Top the wallet up from this card when a plan renewal would fail.
            $table->boolean('auto_topup')->default(true);
            // active | removed | failing (declined last time; still tried next month)
            $table->string('status', 10)->default('active');
            $table->timestampsTz();

            $table->index(['user_id', 'status']);
        });

        Schema::create('card_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('saved_card_id')->constrained('saved_cards');
            $table->foreignId('payment_intent_id')->nullable()->constrained('payment_intents');
            // One charge per reason: "plan-<subscription>-<period end>" is never charged twice.
            $table->string('idempotency_key', 120)->unique();
            $table->string('purpose', 24);
            $table->bigInteger('amount');
            // succeeded | failed | pending (no clear answer from the gateway; payments:reconcile settles it)
            $table->string('status', 10);
            $table->string('message', 300)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_charges');
        Schema::dropIfExists('saved_cards');
    }
};
