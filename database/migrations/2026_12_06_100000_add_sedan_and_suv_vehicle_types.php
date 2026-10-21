<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Sedan and SUV for the instant price form (Canada offers sedan, SUV and van). Existing rows are left alone. */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([['sedan', 'Sedan', 60_000, 15000], ['suv', 'SUV', 120_000, 17500]] as [$code, $name, $weight, $mult]) {
            if (! DB::table('vehicle_types')->where('code', $code)->exists()) {
                DB::table('vehicle_types')->insert([
                    'code' => $code, 'name' => $name, 'max_weight_g' => $weight, 'requires_licence' => true,
                    'price_multiplier_bp' => $mult, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Kept: vehicles and orders may already use them.
    }
};
