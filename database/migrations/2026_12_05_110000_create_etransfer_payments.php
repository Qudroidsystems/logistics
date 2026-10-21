<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What happened to each Interac e-Transfer payment: the customer said they sent it, staff confirmed or rejected it. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etransfer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_intent_id')->unique()->constrained('payment_intents')->cascadeOnDelete();
            $table->timestampTz('instructions_sent_at')->nullable();
            // The customer tapped "I've sent it", with the name they sent it from.
            $table->timestampTz('customer_sent_at')->nullable();
            $table->string('sender_name', 120)->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->unsignedBigInteger('received_amount')->nullable();
            // paid (the order went ahead) | wallet (it came too late and was put in the customer's wallet)
            $table->string('applied_to', 10)->nullable();
            $table->string('interac_ref', 60)->nullable();
            $table->timestampTz('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->string('reason', 300)->nullable();
            $table->timestamps();

            $table->index(['confirmed_at', 'rejected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etransfer_payments');
    }
};
