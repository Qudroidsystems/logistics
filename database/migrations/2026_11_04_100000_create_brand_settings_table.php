<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brand_settings', function (Blueprint $t) {
            $t->id();
            $t->string('app', 16)->unique(); // customer | driver
            $t->string('name', 60);
            $t->string('tagline', 120)->nullable();
            $t->string('logo_path')->nullable();
            $t->string('primary_color', 9)->default('#0F766E');
            $t->string('secondary_color', 9)->default('#115E59');
            $t->timestamps();
        });

        $now = now();
        DB::table('brand_settings')->insert([
            ['app' => 'customer', 'name' => config('app.name', 'Qudroid Logistics'), 'tagline' => 'Send, shop and track in your city', 'primary_color' => '#0F766E', 'secondary_color' => '#115E59', 'created_at' => $now, 'updated_at' => $now],
            ['app' => 'driver', 'name' => 'Qudroid Driver', 'tagline' => 'Deliver and earn', 'primary_color' => '#0F766E', 'secondary_color' => '#115E59', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_settings');
    }
};
