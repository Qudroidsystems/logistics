<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online school-fee payments (Paystack).
 *
 * online_fee_payments      one row per checkout (one Paystack transaction)
 * online_fee_payment_items the bills paid in that checkout and how much of
 *                          each; posted to the fee ledger once Paystack
 *                          confirms the money.
 *
 * Amounts are stored in kobo (integers) so nothing is lost to rounding.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('online_fee_payments')) {
            Schema::create('online_fee_payments', function (Blueprint $table) {
                $table->id();
                $table->string('reference', 60)->unique();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('payer_user_id')->nullable();
                $table->string('payer_type', 20)->default('student');      // student | staff
                $table->string('email');
                $table->unsignedBigInteger('class_id')->nullable();        // class of the target term
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->unsignedBigInteger('amount_kobo');                  // what the payer is charged
                $table->unsignedBigInteger('arrears_kobo')->default(0);
                $table->unsignedBigInteger('current_kobo')->default(0);
                $table->string('currency', 3)->default('NGN');
                $table->string('gateway', 20)->default('paystack');
                $table->string('mode', 10)->default('sandbox');
                // pending | success | failed | abandoned | amount_mismatch
                $table->string('status', 20)->default('pending')->index();
                $table->string('channel', 30)->nullable();
                $table->unsignedBigInteger('gateway_fee_kobo')->default(0);
                $table->unsignedBigInteger('paid_kobo')->default(0);        // confirmed by Paystack
                $table->unsignedBigInteger('applied_kobo')->default(0);     // posted to bills
                $table->unsignedBigInteger('unapplied_kobo')->default(0);   // paid but bill already settled
                $table->boolean('needs_review')->default(false)->index();
                $table->string('access_code')->nullable();
                $table->text('authorization_url')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('last_verified_at')->nullable();
                $table->string('failure_reason')->nullable();
                $table->json('gateway_response')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'status']);
            });
        }

        if (!Schema::hasTable('online_fee_payment_items')) {
            Schema::create('online_fee_payment_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('online_fee_payment_id')->index();
                $table->unsignedBigInteger('school_bill_id');
                $table->string('title')->nullable();
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('session_id');
                $table->boolean('is_arrear')->default(false);
                $table->unsignedBigInteger('payable_kobo');      // bill amount after scholarship/discount
                $table->unsignedBigInteger('balance_kobo');      // outstanding when the checkout started
                $table->unsignedBigInteger('amount_kobo');       // chosen amount
                $table->unsignedBigInteger('applied_kobo')->default(0);
                $table->unsignedBigInteger('student_bill_payment_record_id')->nullable();
                $table->timestamps();

                $table->index(['school_bill_id', 'class_id', 'term_id', 'session_id'], 'idx_ofpi_bill');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('online_fee_payment_items');
        Schema::dropIfExists('online_fee_payments');
    }
};
