<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline-first driver actions. The app saves each action on the phone with its own id and uploads it when it can;
 * the server records every id it has seen, so a retried upload never creates a second pickup or delivery.
 * Parcel scans now name the job, stop and driver, so each parcel has its own custody trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('driver_profile_id')->nullable()->index();
            // The id the phone gave the action when it was saved there (a UUID).
            $table->string('client_event_id', 64);
            $table->string('type', 32);
            $table->unsignedBigInteger('shipment_id')->nullable()->index();
            $table->jsonb('payload')->nullable();
            // When the phone says it happened, when it reached us, and the gap between the two clocks.
            $table->timestampTz('device_time')->nullable();
            $table->timestampTz('received_at');
            $table->integer('clock_skew_s')->nullable();
            $table->boolean('clock_suspect')->default(false);
            // accepted | rejected
            $table->string('status', 12);
            $table->string('message', 255)->nullable();
            $table->jsonb('result')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['user_id', 'client_event_id']);
        });

        // package_scans already exists (Phase 1 schema); link each scan to its job, stop, driver and the queued action.
        Schema::table('package_scans', function (Blueprint $table) {
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('stop_id')->nullable();
            $table->unsignedBigInteger('driver_profile_id')->nullable();
            $table->timestampTz('device_time')->nullable();
            $table->foreignId('sync_event_id')->nullable()->constrained('sync_events');

            $table->index(['shipment_id', 'scan_type']);
        });
    }

    public function down(): void
    {
        Schema::table('package_scans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sync_event_id');
            $table->dropIndex(['shipment_id', 'scan_type']);
            $table->dropColumn(['shipment_id', 'stop_id', 'driver_profile_id', 'device_time']);
        });
        Schema::dropIfExists('sync_events');
    }
};
