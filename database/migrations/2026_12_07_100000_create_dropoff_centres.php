<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop-off centres: partner stores (a convenience store, a print shop) where senders hand in parcels.
 * A driver collects them from the counter, so the first mile is one stop for many parcels.
 *
 *   partner_applications   "Become a partner" form submissions, approved or declined by staff
 *   dropoff_centres        an approved store: where it is, its hours, and what it earns per parcel
 *   dropoff_centre_users   the store's staff who use the counter screen
 *   dropoff_parcels        a delivery booked to start at a store: expected → received (at the counter) → collected (by the driver);
 *                          the store's commission is earned on collection and marked paid by staff
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('business_name', 120);
            $table->string('contact_name', 120);
            $table->string('email', 160);
            $table->string('phone', 24);
            $table->string('line1');
            $table->string('city', 80);
            $table->string('subdivision', 8)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->string('opening_hours', 160)->nullable();
            $table->string('business_type', 40)->nullable();
            $table->text('message')->nullable();
            // new | approved | declined
            $table->string('status', 10)->default('new');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestampTz('decided_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->unsignedBigInteger('dropoff_centre_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('dropoff_centres', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('code', 12)->unique();
            $table->string('name', 120);
            $table->string('line1');
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->geoPoint('point', false);
            $table->string('phone', 24)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('opening_hours', 160)->nullable();
            // What the store earns for each parcel it hands to a driver: a flat amount plus a share of the delivery price.
            $table->bigInteger('commission_flat')->default(0);
            $table->unsignedInteger('commission_bp')->default(0);
            // active | paused
            $table->string('status', 8)->default('active');
            $table->foreignId('application_id')->nullable()->constrained('partner_applications');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('dropoff_centre_users', function (Blueprint $table) {
            $table->foreignId('dropoff_centre_id')->constrained('dropoff_centres')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['dropoff_centre_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('dropoff_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dropoff_centre_id')->constrained('dropoff_centres');
            $table->foreignId('shipment_id')->unique()->constrained('shipments')->cascadeOnDelete();
            // expected | received | collected | cancelled
            $table->string('status', 10)->default('expected');
            $table->timestampTz('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestampTz('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users');
            $table->bigInteger('commission')->default(0);
            // pending (not collected yet) | earned | paid | void
            $table->string('commission_status', 8)->default('pending');
            $table->timestampTz('paid_at')->nullable();
            $table->string('payout_ref', 60)->nullable();
            $table->timestamps();

            $table->index(['dropoff_centre_id', 'status']);
            $table->index(['dropoff_centre_id', 'commission_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropoff_parcels');
        Schema::dropIfExists('dropoff_centre_users');
        Schema::dropIfExists('dropoff_centres');
        Schema::dropIfExists('partner_applications');
    }
};
