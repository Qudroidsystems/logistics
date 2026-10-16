<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Loyalty points customers earn on finished deliveries and spend at payment, and gift cards bought or issued with a balance. */
return new class extends Migration
{
    public function up(): void
    {
        // One row per change; a customer's balance is the sum.
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('points');
            // What the points were worth in kobo when spent or given back (0 for earned points).
            $table->bigInteger('amount')->default(0);
            // earn | redeem | restore | adjust
            $table->string('kind', 10);
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->unique(['agreement_id', 'kind']);
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            // sha256 of the normalised code; only the last 4 characters are kept in the clear.
            $table->string('code_hash', 64)->unique();
            $table->string('last4', 4);
            $table->bigInteger('initial_value');
            $table->bigInteger('balance');
            // bought (paid from the buyer's wallet) | issued (staff, paid by the platform)
            $table->string('source', 8);
            $table->foreignId('bought_by')->nullable()->constrained('users');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->string('recipient_name', 120)->nullable();
            $table->string('recipient_phone', 24)->nullable();
            $table->string('recipient_email', 190)->nullable();
            $table->string('message', 300)->nullable();
            // active | disabled
            $table->string('status', 10)->default('active');
            $table->timestampTz('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gift_card_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards');
            $table->foreignId('agreement_id')->constrained('agreements');
            $table->foreignId('user_id')->constrained('users');
            // positive: spent on the agreement; negative: given back after a refund
            $table->bigInteger('amount');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('gift_card_id');
        });

        Schema::table('agreements', function (Blueprint $table) {
            // The customer chose to pay part of this job with points, or with a gift card, before paying.
            $table->boolean('use_points')->default(false);
            $table->foreignId('gift_card_id')->nullable()->constrained('gift_cards');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
            $table->dropColumn('use_points');
        });
        Schema::dropIfExists('gift_card_uses');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('loyalty_points');
    }
};
