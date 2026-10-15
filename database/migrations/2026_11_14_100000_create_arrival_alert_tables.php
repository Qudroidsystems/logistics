<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arrival times and "the rider is close" alerts.
 *
 *   alert_settings        each provider's alert choices (minutes before arrival, distance, arrived, delay notices, texts)
 *   stop_etas             the last arrival estimate for a stop, so it is recalculated only when the rider has moved or time has passed
 *   stop_alerts           every alert sent (or skipped as already covered) for a stop: one row per kind, so each fires once
 *   shipment_stops.readiness   the receiver's reply ("I'm ready", "I need 10 more minutes", ...) for the driver to see
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->unique()->constrained('operators');
            $table->jsonb('eta_minutes');
            $table->unsignedSmallInteger('near_m')->nullable();
            $table->boolean('arrived')->default(true);
            $table->unsignedSmallInteger('delay_minutes')->nullable();
            $table->boolean('sms')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('stop_etas', function (Blueprint $table) {
            $table->foreignId('stop_id')->primary()->constrained('shipment_stops');
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->unsignedInteger('duration_s');
            $table->unsignedInteger('distance_m');
            $table->string('provider', 16);
            $table->double('from_lat');
            $table->double('from_lng');
            $table->timestampTz('gps_at')->nullable();
            $table->timestampTz('computed_at');
            // When the rider was expected; a delay notice goes out when the new estimate slips past it by the provider's margin.
            $table->timestampTz('due_at');
        });

        Schema::create('stop_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stop_id')->constrained('shipment_stops');
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->string('kind', 24);
            // sent | skipped (a closer alert went out in the same moment, so this one would only repeat it)
            $table->string('status', 12);
            $table->unsignedInteger('eta_s')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->jsonb('channels')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['stop_id', 'kind']);
            $table->index('shipment_id');
        });

        Schema::table('shipment_stops', function (Blueprint $table) {
            $table->string('readiness', 16)->nullable();
            $table->string('readiness_note', 140)->nullable();
            $table->timestampTz('readiness_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_stops', function (Blueprint $table) {
            $table->dropColumn(['readiness', 'readiness_note', 'readiness_at']);
        });
        Schema::dropIfExists('stop_alerts');
        Schema::dropIfExists('stop_etas');
        Schema::dropIfExists('alert_settings');
    }
};
