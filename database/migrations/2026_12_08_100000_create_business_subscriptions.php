<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Business plans: a monthly fee that includes a number of deliveries.
 *
 *   subscription_plans    what is on sale: price, deliveries included each month, the price of each extra delivery,
 *                         the furthest a covered delivery may go, and whether its jobs get priority dispatch.
 *                         A plan with contact_only is shown with "Talk to us" (Enterprise) instead of a subscribe button.
 *   subscriptions         a customer's plan. The fee is taken from their wallet at the start of each month;
 *                         if the wallet is short the plan is past_due and stops covering deliveries until it is paid.
 *   subscription_charges  each monthly fee taken (or attempted)
 *   subscription_uses     each delivery the plan covered (included) or priced at the plan rate (extra)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 60);
            $table->string('tagline', 160)->nullable();
            $table->bigInteger('price')->default(0);
            $table->unsignedInteger('included_deliveries')->default(0);
            // What each delivery after the included ones costs; null = the normal price.
            $table->bigInteger('overage_price')->nullable();
            // Covered deliveries must be at most this far; longer trips are charged normally.
            $table->unsignedInteger('max_km')->nullable();
            $table->boolean('priority_dispatch')->default(false);
            $table->boolean('contact_only')->default(false);
            $table->jsonb('features')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            // active | past_due | cancelled
            $table->string('status', 10)->default('active');
            $table->timestampTz('current_period_start');
            $table->timestampTz('current_period_end');
            $table->unsignedInteger('used')->default(0);
            // Price and allowance frozen for the current month, so a plan edit applies from the next month.
            $table->bigInteger('period_price');
            $table->unsignedInteger('period_included');
            $table->boolean('cancel_at_period_end')->default(false);
            $table->foreignId('next_plan_id')->nullable()->constrained('subscription_plans');
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'current_period_end']);
        });
        DB::statement("CREATE UNIQUE INDEX subscriptions_one_live_per_user ON subscriptions (user_id) WHERE status IN ('active', 'past_due')");

        Schema::create('subscription_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->timestampTz('period_start');
            $table->timestampTz('period_end');
            $table->bigInteger('amount');
            // paid | failed
            $table->string('status', 8);
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'period_start', 'status']);
        });

        Schema::create('subscription_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('agreement_id')->unique()->constrained('agreements');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            // included | extra
            $table->string('kind', 8);
            $table->bigInteger('discount')->default(0);
            $table->timestampTz('period_start');
            $table->boolean('returned')->default(false);
            $table->timestamps();

            $table->index(['subscription_id', 'period_start']);
        });

        $now = now();
        DB::table('subscription_plans')->insert([
            ['code' => 'starter', 'name' => 'Starter', 'tagline' => 'For shops sending a few parcels a week', 'price' => 19_900, 'included_deliveries' => 20, 'overage_price' => 1_100,
                'max_km' => 25, 'priority_dispatch' => false, 'contact_only' => false, 'features' => json_encode(['20 deliveries a month', 'Live tracking and proof of delivery', 'Book on the website or app']),
                'active' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'growth', 'name' => 'Growth', 'tagline' => 'For busy businesses that need it there fast', 'price' => 49_900, 'included_deliveries' => 60, 'overage_price' => 900,
                'max_km' => 25, 'priority_dispatch' => true, 'contact_only' => false, 'features' => json_encode(['60 deliveries a month', 'Priority dispatch', 'Lower price for extra deliveries']),
                'active' => true, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'enterprise', 'name' => 'Enterprise', 'tagline' => 'High volume, several locations, custom terms', 'price' => 0, 'included_deliveries' => 0, 'overage_price' => null,
                'max_km' => null, 'priority_dispatch' => true, 'contact_only' => true, 'features' => json_encode(['Volume pricing', 'Monthly invoicing', 'A dedicated contact']),
                'active' => true, 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_uses');
        Schema::dropIfExists('subscription_charges');
        DB::statement('DROP INDEX IF EXISTS subscriptions_one_live_per_user');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
