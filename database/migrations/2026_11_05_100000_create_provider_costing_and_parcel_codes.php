<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provider job costing (cost profiles and saved estimates) and parcel identity codes.
 *
 * A cost profile holds a provider's operating assumptions: fuel price and use, labour, maintenance, markup and
 * the per-km rate card. Every change bumps its version. An estimate freezes the profile, the route and the
 * result together, so a later change in fuel price never rewrites a figure the provider already quoted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_cost_profiles', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->string('name', 80);
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types');
            // per_km | cost_plus | higher_of
            $table->string('pricing_method', 12)->default('higher_of');
            $table->money('base_fee');
            $table->money('rate_per_km');
            $table->money('min_fee');
            $table->money('fuel_price_per_litre');
            $table->decimal('fuel_l_per_100km', 6, 2)->default(0);
            $table->money('labour_per_job');
            $table->money('labour_per_hour');
            $table->money('maintenance_per_km');
            $table->money('other_per_job');
            $table->money('loading_fee');
            $table->money('waiting_per_minute');
            $table->money('fragile_surcharge');
            $table->unsignedInteger('insurance_bp')->default(0);
            $table->unsignedInteger('return_trip_bp')->default(0);
            $table->unsignedInteger('markup_bp')->default(0);
            $table->unsignedInteger('rounding_kobo')->default(5000);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['operator_id', 'active']);
        });

        Schema::create('provider_estimates', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('profile_id')->constrained('provider_cost_profiles');
            $table->unsignedInteger('profile_version');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('request_id')->nullable()->constrained('service_requests')->nullOnDelete();
            $table->jsonb('inputs');
            $table->jsonb('profile_snapshot');
            // distance_m, duration_s, provider (osrm | straight | manual), legs
            $table->jsonb('route');
            $table->jsonb('result');
            $table->money('operating_cost');
            $table->money('suggested_price');
            $table->money('platform_fee');
            $table->money('provider_net');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['operator_id', 'created_at']);
            $table->index(['request_id', 'operator_id']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedSmallInteger('seq')->default(1);
            // Optional tamper-evident seal fitted by the provider on high-value items.
            $table->string('seal_number', 40)->nullable();
            $table->unique('qr_payload');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['qr_payload']);
            $table->dropColumn(['seq', 'seal_number']);
        });
        Schema::dropIfExists('provider_estimates');
        Schema::dropIfExists('provider_cost_profiles');
    }
};
