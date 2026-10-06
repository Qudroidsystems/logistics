<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            // instant | same_day | next_day | scheduled | express_intercity | economy | freight | errand | shopping_run
            $table->string('code', 32)->unique();
            $table->string('name', 80);
            $table->unsignedInteger('sla_minutes')->nullable();
            $table->jsonb('allowed_vehicle_types')->nullable();
            $table->string('requires_pod_type', 24)->nullable();
            $table->boolean('allows_cod')->default(false);
            $table->boolean('allows_multi_stop')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('operator_service_areas', function (Blueprint $table) {
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });

        Schema::create('package_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 80);
            $table->boolean('restricted')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->boolean('requires_id_check')->default(false);
            $table->bigInteger('max_declared_value')->nullable();
            $table->unsignedSmallInteger('surcharge_bp')->default(0);
            $table->jsonb('allowed_vehicle_types')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types');
            $table->string('name');
            $table->char('currency', 3)->default('NGN');
            $table->money('min_fee');
            $table->money('max_fee', true);
            $table->unsignedInteger('rounding_kobo')->default(5000);
            $table->unsignedInteger('version')->default(1);
            // draft | active | retired
            $table->string('status', 16)->default('draft');
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_to')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'service_type_id', 'status']);
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('pricing_plans')->cascadeOnDelete();
            // base | per_km | per_minute | per_kg | per_stop | zone_pair | waiting | night | peak_hour |
            // fragile | cod_fee | insurance | remote_area | toll
            $table->string('kind', 24);
            $table->jsonb('condition')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->unsignedInteger('rate_bp')->nullable();
            $table->string('unit', 16)->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('stackable')->default(true);
            $table->timestamps();

            $table->index(['plan_id', 'kind']);
        });

        Schema::create('surge_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->foreignId('zone_id')->nullable()->constrained('zones');
            // demand_supply_ratio | weather | event | manual
            $table->string('trigger', 24);
            $table->jsonb('thresholds');
            $table->unsignedInteger('multiplier_cap_bp')->default(20000);
            $table->unsignedInteger('smoothing_seconds')->default(120);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('surge_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->nullable()->constrained('zones');
            $table->bigInteger('h3_8')->nullable();
            $table->unsignedInteger('multiplier_bp')->default(10000);
            $table->unsignedInteger('demand')->default(0);
            $table->unsignedInteger('supply')->default(0);
            $table->timestampTz('computed_at');

            $table->index(['zone_id', 'computed_at']);
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('code', 32)->unique();
            // percent | fixed | free_delivery | first_order | referral
            $table->string('type', 16);
            $table->unsignedBigInteger('value');
            $table->bigInteger('cap')->nullable();
            $table->bigInteger('min_order')->nullable();
            $table->jsonb('scope')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->bigInteger('budget')->nullable();
            $table->bigInteger('budget_spent')->default(0);
            // platform | operator | vendor
            $table->string('funded_by', 16)->default('platform');
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->foreignId('service_type_id')->nullable()->constrained('service_types');
            $table->foreignId('city_id')->nullable()->constrained('cities');
            // provider type this rule applies to, e.g. company | independent_driver | market_shopper
            $table->string('operator_type', 32)->nullable();
            // percent_of_delivery_fee | percent_of_total | fixed_per_job | tiered
            $table->string('basis', 32);
            $table->unsignedSmallInteger('rate_bp')->default(0);
            $table->bigInteger('fixed_amount')->default(0);
            $table->bigInteger('min_fee')->default(0);
            $table->jsonb('tiers')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types');
            $table->jsonb('request');
            $table->unsignedInteger('route_distance_m')->nullable();
            $table->unsignedInteger('route_duration_s')->nullable();
            $table->text('route_polyline')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('pricing_plans');
            $table->unsignedInteger('plan_version')->nullable();
            $table->foreignId('surge_snapshot_id')->nullable()->constrained('surge_snapshots');
            $table->jsonb('breakdown');
            $table->money('subtotal');
            $table->money('discount');
            $table->money('tax');
            $table->money('total');
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes');
            $table->money('driver_payout_estimate', true);
            $table->boolean('is_test')->default(false);
            $table->timestampTz('expires_at');
            $table->unsignedBigInteger('accepted_order_id')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'created_at']);
            $table->index('expires_at');
        });

        Schema::create('promo_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained('promo_codes');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->bigInteger('amount');
            $table->foreignId('device_id')->nullable()->constrained('devices');
            $table->timestamps();

            $table->index(['promo_code_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['promo_redemptions', 'quotes', 'commission_rules', 'promo_codes', 'surge_snapshots',
                  'surge_rules', 'pricing_rules', 'pricing_plans', 'package_categories'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('operator_service_areas', fn (Blueprint $t) => $t->dropForeign(['service_type_id']));
        Schema::dropIfExists('service_types');
    }
};
