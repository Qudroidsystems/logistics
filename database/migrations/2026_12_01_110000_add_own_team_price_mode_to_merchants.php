<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who prices a shop's own deliveries (sent to its own team with "provider": "own" or the team's option):
 *   shop = the price the shop sends with each delivery (its own checkout fee)
 *   team = always the team's price list on the marketplace
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('merchants', 'own_team_price_mode')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('own_team_price_mode', 8)->default('shop')->after('own_provider_operator_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('merchants', fn (Blueprint $t) => $t->dropColumn('own_team_price_mode'));
    }
};
