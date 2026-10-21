<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring deliveries: the same trip on a schedule (every weekday at 10:00, Mondays and Thursdays at 15:30…).
 * recurring:run books each one ahead of time at the instant price (a business plan covers it when it can) and pays it
 * from the wallet; if the wallet is short the customer is asked to pay. One run per trip and time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_deliveries', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            // {lat, lng, line1, contact_name, contact_phone, instructions}; pickup is null when dropped at a partner store
            $table->jsonb('pickup')->nullable();
            $table->string('dropoff_centre', 26)->nullable();
            $table->jsonb('dropoff');
            $table->string('size', 12);
            $table->string('vehicle', 24)->nullable();
            $table->boolean('fragile')->default(false);
            $table->string('description', 200);
            // daily | weekdays | weekly
            $table->string('pattern', 10);
            $table->jsonb('days')->nullable();
            $table->string('time', 5);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            // active | paused | ended
            $table->string('status', 8)->default('active');
            $table->timestampTz('next_run_at')->nullable();
            $table->unsignedSmallInteger('failures')->default(0);
            $table->timestamps();

            $table->index(['status', 'next_run_at']);
        });

        Schema::create('recurring_delivery_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_delivery_id')->constrained('recurring_deliveries')->cascadeOnDelete();
            $table->timestampTz('pickup_at');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            // paid | needs_payment | failed | skipped
            $table->string('status', 14);
            $table->bigInteger('amount')->nullable();
            $table->string('message', 300)->nullable();
            $table->timestamps();

            $table->unique(['recurring_delivery_id', 'pickup_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_delivery_runs');
        Schema::dropIfExists('recurring_deliveries');
    }
};
