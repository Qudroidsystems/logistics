<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requests priced by providers (negotiation) carry the road route and the platform's road-based price for the same trip,
 * so providers see a going rate when they make an offer and customers can compare offers with it.
 *   route_duration_s   driving time on roads (RoutingEngine); distance_m is already the road distance
 *   reference_price    the instant price list's price for this trip (null when there is no price list for it)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedInteger('route_duration_s')->nullable();
            $table->bigInteger('reference_price')->nullable();
            // Where the request came from: null (posted as a request) | instant (the customer saw a fixed price first)
            $table->string('origin', 12)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', fn (Blueprint $t) => $t->dropColumn(['route_duration_s', 'reference_price', 'origin']));
    }
};
