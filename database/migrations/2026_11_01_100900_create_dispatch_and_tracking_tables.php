<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            // nearest | scored | batch | broadcast | manual
            $table->string('strategy', 12);
            $table->jsonb('config_snapshot')->nullable();
            $table->unsignedInteger('candidates_found')->default(0);
            $table->unsignedInteger('candidates_offered')->default(0);
            // assigned | manual_queue | cancelled | no_supply
            $table->string('outcome', 16)->nullable();
            $table->foreignId('winner_driver_id')->nullable()->constrained('driver_profiles');
            $table->foreignId('overridden_by')->nullable()->constrained('users');
            $table->string('override_reason')->nullable();
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->index('shipment_id');
        });

        Schema::create('dispatch_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('dispatch_runs')->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->unsignedSmallInteger('rank')->nullable();
            $table->decimal('score', 8, 3)->nullable();
            $table->jsonb('score_breakdown')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedInteger('eta_to_pickup_s')->nullable();
            $table->boolean('filtered_out')->default(false);
            $table->string('filter_reason', 60)->nullable();

            $table->index('run_id');
        });

        Schema::create('dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('dispatch_runs')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->operatorId();
            $table->unsignedSmallInteger('sequence');
            $table->bigInteger('payout_offered')->nullable();
            $table->timestampTz('offered_at')->useCurrent();
            $table->timestampTz('expires_at');
            $table->timestampTz('responded_at')->nullable();
            // accepted | declined | timeout | cancelled_by_system
            $table->string('response', 20)->nullable();
            $table->string('decline_reason', 60)->nullable();
            $table->string('channel', 8)->default('push');

            $table->index(['driver_profile_id', 'expires_at']);
            $table->index('shipment_id');
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->operatorId();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            // assigned | accepted | en_route | active | completed | reassigned | cancelled
            $table->string('status', 12)->default('assigned');
            // system | dispatcher | driver_self
            $table->string('assigned_by', 12)->default('system');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users');
            $table->bigInteger('payout_amount')->nullable();
            $table->string('reassign_reason')->nullable();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->index(['driver_profile_id', 'status']);
        });
        // One live assignment per shipment.
        DB::statement("CREATE UNIQUE INDEX assignments_one_active_idx ON assignments (shipment_id) WHERE status IN ('assigned','accepted','en_route','active')");

        Schema::create('route_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->operatorId();
            $table->string('status', 12)->default('planned');
            $table->unsignedInteger('planned_distance_m')->nullable();
            $table->unsignedInteger('actual_distance_m')->nullable();
            $table->unsignedInteger('planned_duration_s')->nullable();
            $table->unsignedInteger('actual_duration_s')->nullable();
            $table->string('optimiser_version', 16)->nullable();
            $table->timestamps();
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('route_plan_id')->references('id')->on('route_plans')->nullOnDelete();
        });

        Schema::create('route_plan_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_plan_id')->constrained('route_plans')->cascadeOnDelete();
            $table->foreignId('shipment_stop_id')->constrained('shipment_stops');
            $table->unsignedSmallInteger('seq');
            $table->timestampTz('planned_arrival')->nullable();
            $table->timestampTz('actual_arrival')->nullable();

            $table->unique(['route_plan_id', 'seq']);
        });

        Schema::create('dispatch_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            // no_supply | repeated_decline | sla_risk | stuck_at_pickup | driver_unresponsive | geofence_breach
            $table->string('kind', 24);
            $table->string('severity', 8)->default('medium');
            $table->timestampTz('raised_at')->useCurrent();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestampTz('resolved_at')->nullable();

            $table->index(['resolved_at', 'severity']);
        });

        Schema::create('driver_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->timestampTz('went_online_at');
            $table->timestampTz('went_offline_at')->nullable();
            $table->geoPoint('start_point');
            $table->unsignedInteger('distance_m')->default(0);
            $table->unsignedInteger('jobs_completed')->default(0);
            $table->bigInteger('earnings')->default(0);

            $table->index(['driver_profile_id', 'went_online_at']);
        });

        Schema::create('supply_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->bigInteger('h3_8');
            $table->unsignedInteger('drivers_online');
            $table->unsignedInteger('drivers_idle');
            $table->unsignedInteger('open_orders');
            $table->unsignedInteger('avg_wait_s')->nullable();
            $table->timestampTz('captured_at');

            $table->index(['city_id', 'captured_at']);
        });

        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->bigInteger('h3_8');
            $table->timestampTz('hour');
            $table->unsignedInteger('predicted_orders');
            $table->string('model_version', 24);

            $table->unique(['city_id', 'h3_8', 'hour']);
        });

        Schema::create('incentive_campaigns', function (Blueprint $table) {
            $table->id();
            $table->operatorId();
            // quest | boost_zone | streak | referral | peak_hour
            $table->string('type', 16);
            $table->string('name');
            $table->jsonb('criteria');
            $table->bigInteger('reward');
            $table->bigInteger('budget')->nullable();
            $table->jsonb('zone_ids')->nullable();
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_to');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('incentive_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('incentive_campaigns')->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->unsignedInteger('progress')->default(0);
            $table->bigInteger('earned')->default(0);
            $table->unsignedBigInteger('paid_ledger_tx_id')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'driver_profile_id']);
        });

        // ---- Tracking: the highest-volume table, partitioned daily -----------------
        DB::statement(<<<'SQL'
            CREATE TABLE driver_locations (
                id BIGINT GENERATED ALWAYS AS IDENTITY,
                driver_profile_id BIGINT NOT NULL,
                shipment_id BIGINT,
                operator_id BIGINT NOT NULL,
                point geography(Point, 4326) NOT NULL,
                h3_9 BIGINT,
                speed_mps REAL,
                heading REAL,
                accuracy_m REAL,
                battery_pct SMALLINT,
                network VARCHAR(12),
                is_mocked BOOLEAN NOT NULL DEFAULT FALSE,
                recorded_at TIMESTAMPTZ NOT NULL,
                received_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id, recorded_at)
            ) PARTITION BY RANGE (recorded_at)
        SQL);
        DB::statement('CREATE INDEX driver_locations_driver_idx ON driver_locations (driver_profile_id, recorded_at)');
        DB::statement('CREATE INDEX driver_locations_shipment_idx ON driver_locations (shipment_id, recorded_at) WHERE shipment_id IS NOT NULL');
        DB::statement('CREATE INDEX driver_locations_recorded_brin ON driver_locations USING BRIN (recorded_at)');
        DB::statement('CREATE TABLE driver_locations_default PARTITION OF driver_locations DEFAULT');

        Schema::create('trip_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->unique()->constrained('shipments');
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            $table->geography('path', 'linestring', 4326)->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedInteger('duration_s')->nullable();
            $table->decimal('max_speed', 6, 2)->nullable();
            $table->unsignedInteger('idle_seconds')->nullable();
            $table->timestampTz('built_at');
        });

        Schema::create('eta_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->nullable()->constrained('shipment_stops');
            $table->timestampTz('predicted_at');
            $table->timestampTz('predicted_arrival');
            $table->string('model_version', 24);
            $table->jsonb('inputs')->nullable();

            $table->index(['shipment_id', 'predicted_at']);
        });

        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            // customer | recipient | vendor | corporate | merchant
            $table->string('audience', 12);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('geofence_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->nullable()->constrained('shipment_stops');
            $table->foreignId('driver_profile_id')->constrained('driver_profiles');
            // approaching | arrived | departed | left_route | long_idle
            $table->string('type', 16);
            $table->unsignedInteger('distance_m')->nullable();
            $table->geoPoint('point');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['shipment_id', 'created_at']);
        });

        Schema::create('proofs', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->constrained('shipment_stops');
            $table->foreignId('package_id')->nullable()->constrained('packages');
            // otp | photo | signature | id_check | qr_scan | barcode_scan | recipient_name
            $table->string('type', 16);
            $table->string('file_path')->nullable();
            $table->string('value_hash', 80)->nullable();
            $table->boolean('otp_verified')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_relationship', 40)->nullable();
            $table->geoPoint('point');
            $table->unsignedInteger('distance_from_stop_m')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices');
            $table->string('verified_by', 12)->default('system');
            $table->timestampTz('captured_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['shipment_id', 'type']);
        });

        Schema::create('package_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages');
            // pickup | hub_in | hub_out | vehicle_load | vehicle_unload | delivery | return
            $table->string('scan_type', 16);
            $table->foreignId('scanned_by')->nullable()->constrained('users');
            $table->string('location_type', 16)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->geoPoint('point');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['package_id', 'created_at']);
        });

        Schema::create('delivery_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->nullable()->constrained('shipment_stops');
            // recipient_unreachable | wrong_address | refused | closed_premises | unsafe_area | damaged_in_transit |
            // payment_failed | restricted_item | vehicle_breakdown | accident
            $table->string('reason', 24);
            $table->text('notes')->nullable();
            $table->jsonb('evidence')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users');
            // retry_same_day | reschedule | return_to_sender | hold_at_hub | escalate
            $table->string('action_taken', 20)->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('contact_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->nullable()->constrained('shipment_stops');
            $table->string('channel', 12);
            $table->string('outcome', 16);
            $table->timestampTz('created_at')->useCurrent();
        });

        // ---- Confirmation and disputes (escrow release decisions) ---------------------
        Schema::create('delivery_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->foreignId('shipment_id')->unique()->constrained('shipments');
            // pending | confirmed | objected | auto_confirmed
            $table->string('status', 16)->default('pending');
            $table->timestampTz('requested_at')->useCurrent();
            $table->timestampTz('deadline_at')->nullable();
            $table->timestampTz('responded_at')->nullable();
            $table->unsignedSmallInteger('rating_given')->nullable();
            $table->text('objection_reason')->nullable();
            $table->jsonb('evidence')->nullable();
            $table->timestamps();

            $table->index(['status', 'deadline_at']);
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->foreignId('escrow_hold_id')->nullable()->constrained('escrow_holds');
            $table->foreignId('raised_by')->constrained('users');
            // price | quality | damage | lost | late | driver_conduct | fraud
            $table->string('type', 16);
            $table->bigInteger('amount')->nullable();
            // open | evidence | decided | closed
            $table->string('status', 12)->default('open');
            $table->jsonb('evidence')->nullable();
            // full_release | partial | full_refund
            $table->string('decision', 16)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestampTz('decided_at')->nullable();
            $table->unsignedBigInteger('outcome_ledger_tx_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['disputes', 'delivery_confirmations', 'contact_attempts', 'delivery_exceptions', 'package_scans',
                  'proofs', 'geofence_events', 'tracking_links', 'eta_predictions', 'trip_traces'] as $t) {
            Schema::dropIfExists($t);
        }
        DB::statement('DROP TABLE IF EXISTS driver_locations CASCADE');
        foreach (['incentive_progress', 'incentive_campaigns', 'demand_forecasts', 'supply_snapshots',
                  'driver_sessions', 'dispatch_alerts', 'route_plan_stops'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('shipments', fn (Blueprint $t) => $t->dropForeign(['route_plan_id']));
        Schema::dropIfExists('route_plans');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('dispatch_offers');
        Schema::dropIfExists('dispatch_candidates');
        Schema::dropIfExists('dispatch_runs');
    }
};
