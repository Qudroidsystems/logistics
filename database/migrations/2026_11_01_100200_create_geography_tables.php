<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2)->default('NG');
            $table->string('region', 80)->nullable();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('timezone', 48)->default('Africa/Lagos');
            $table->char('currency', 3)->default('NGN');
            $table->geoPoint('centre');
            $table->geography('bounds', 'polygon', 4326)->nullable();
            // planned | beta | live | paused
            $table->string('launch_status', 16)->default('planned');
            $table->string('default_language', 8)->default('en');
            $table->unsignedSmallInteger('tax_rate_bp')->default(0);
            $table->jsonb('supports')->nullable();
            $table->timestamps();

            $table->spatialIndex('centre');
            $table->index('launch_status');
        });

        Schema::table('operators', function (Blueprint $table) {
            $table->foreign('home_city_id')->references('id')->on('cities')->nullOnDelete();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            // null = platform zone
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('name', 120);
            // service | pricing | surge | restricted | hub_catchment | no_pickup | no_dropoff
            $table->string('type', 24)->index();
            $table->geography('boundary', 'multipolygon', 4326);
            $table->jsonb('h3_cells')->nullable();      // resolution-8 cover, rebuilt on save
            $table->unsignedSmallInteger('priority')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->jsonb('rules')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->spatialIndex('boundary');
            $table->index(['city_id', 'type', 'active']);
        });

        Schema::create('operator_service_areas', function (Blueprint $table) {
            $table->id();
            $table->operatorId();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->unsignedBigInteger('service_type_id')->nullable();
            $table->unsignedSmallInteger('max_concurrent_jobs')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['operator_id', 'zone_id', 'service_type_id'], 'service_area_unique');
        });

        Schema::create('zone_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('to_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('distance_band', 24)->nullable();
            $table->bigInteger('base_price_override')->nullable();
            $table->unsignedInteger('eta_minutes')->nullable();
            $table->timestamps();

            $table->unique(['from_zone_id', 'to_zone_id']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            // user | operator | vendor | store | hub | merchant
            $table->string('owner_type', 24);
            $table->unsignedBigInteger('owner_id');
            $table->string('label', 60)->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('landmark')->nullable();
            $table->text('directions_note')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->string('postal_code', 16)->nullable();
            $table->geoPoint('point', false);
            $table->bigInteger('h3_9')->nullable();
            $table->string('place_id', 120)->nullable();
            $table->string('geocode_provider', 24)->nullable();
            $table->string('geocode_accuracy', 24)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 24)->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id']);
            $table->index('h3_9');
            $table->spatialIndex('point');
        });

        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->string('name');
            // mall | market | estate | school | hospital | motor_park | partner_store | landmark
            $table->string('category', 32)->index();
            $table->geoPoint('point', false);
            $table->geography('entrance_points', 'multipoint', 4326)->nullable();
            $table->jsonb('opening_hours')->nullable();
            $table->unsignedInteger('popularity')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->spatialIndex('point');
        });
        DB::statement("ALTER TABLE places ADD COLUMN search_tsv tsvector GENERATED ALWAYS AS (to_tsvector('simple', coalesce(name, ''))) STORED");
        DB::statement('CREATE INDEX places_search_tsv_idx ON places USING GIN (search_tsv)');
        DB::statement('CREATE INDEX places_name_trgm_idx ON places USING GIN (name gin_trgm_ops)');

        Schema::create('route_cache', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('origin_h3');
            $table->bigInteger('dest_h3');
            $table->string('profile', 16);      // bike | car | van
            $table->unsignedSmallInteger('hour_bucket');
            $table->unsignedInteger('distance_m');
            $table->unsignedInteger('duration_s');
            $table->text('polyline')->nullable();
            $table->string('provider', 24);
            $table->timestampTz('expires_at');
            $table->timestamps();

            $table->unique(['origin_h3', 'dest_h3', 'profile', 'hour_bucket'], 'route_cache_unique');
            $table->index('expires_at');
        });

        Schema::create('service_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->bigInteger('h3_8');
            $table->unsignedBigInteger('service_type_id')->nullable();
            $table->unsignedInteger('drivers_online')->default(0);
            $table->unsignedInteger('drivers_idle')->default(0);
            $table->unsignedInteger('open_orders')->default(0);
            $table->timestampTz('updated_at')->nullable();

            $table->unique(['city_id', 'h3_8', 'service_type_id'], 'service_availability_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_availability');
        Schema::dropIfExists('route_cache');
        Schema::dropIfExists('places');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('zone_pairs');
        Schema::dropIfExists('operator_service_areas');
        Schema::dropIfExists('zones');
        Schema::table('operators', fn (Blueprint $t) => $t->dropForeign(['home_city_id']));
        Schema::dropIfExists('cities');
    }
};
