<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customers inviting friends: the friend gets money off their first delivery, the inviter a wallet credit once it is done. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users');
            $table->foreignId('friend_id')->unique()->constrained('users');
            // pending (friend has not finished a first delivery) | rewarded | void
            $table->string('status', 10)->default('pending');
            $table->bigInteger('reward')->default(0);
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->timestampTz('rewarded_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
