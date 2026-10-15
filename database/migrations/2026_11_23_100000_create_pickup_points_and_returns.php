<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pickup points and returns.
 *
 *   pickup_points            a provider's shop or office where parcels wait for the receiver to collect them
 *   shipment_stops.pickup_point_id   a drop-off that is a pickup point: the rider leaves the parcel there, and the receiver
 *                            collects it later with their delivery code
 *   shipments.held_at / hold_until   when the parcel reached the point, and how long it is kept before it goes back
 *   shipments.return_reason  why a return (kind = return, parent_shipment_id = the delivery it reverses) was booked.
 *                            A delivery has at most one return that is not cancelled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_points', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            // Short code for spreadsheets and the partner API, unique per provider (e.g. IKJ01)
            $table->string('code', 12);
            $table->string('name', 120);
            $table->string('line1');
            $table->string('landmark')->nullable();
            $table->geoPoint('point', false);
            $table->string('phone', 24)->nullable();
            $table->string('opening_hours', 160)->nullable();
            $table->unsignedSmallInteger('hold_days')->default(5);
            // active | paused
            $table->string('status', 8)->default('active');
            $table->timestamps();

            $table->unique(['operator_id', 'code']);
        });

        Schema::table('shipment_stops', function (Blueprint $table) {
            $table->foreignId('pickup_point_id')->nullable()->constrained('pickup_points');
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestampTz('held_at')->nullable();
            $table->timestampTz('hold_until')->nullable();
            $table->string('return_reason', 24)->nullable();
        });
        DB::statement("CREATE UNIQUE INDEX shipments_one_open_return ON shipments (parent_shipment_id) WHERE kind = 'return' AND status <> 'cancelled'");
        DB::statement("CREATE INDEX shipments_at_pickup_point_idx ON shipments (operator_id, hold_until) WHERE status = 'at_pickup_point'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS shipments_at_pickup_point_idx');
        DB::statement('DROP INDEX IF EXISTS shipments_one_open_return');
        Schema::table('shipments', fn (Blueprint $t) => $t->dropColumn(['held_at', 'hold_until', 'return_reason']));
        Schema::table('shipment_stops', fn (Blueprint $t) => $t->dropConstrainedForeignId('pickup_point_id'));
        Schema::dropIfExists('pickup_points');
    }
};
