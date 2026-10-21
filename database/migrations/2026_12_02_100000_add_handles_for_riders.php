<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @handles. Businesses (companies, independent riders, shoppers) already have one: their public page slug.
 * Riders who work for a company get their own, so customers can find "@musa" and ask for them.
 * A request can name the rider the customer wants; it goes to that rider's company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_profiles', 'handle')) {
                $table->string('handle', 30)->nullable()->unique();
            }
        });
        Schema::table('service_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('service_requests', 'preferred_driver_profile_id')) {
                $table->foreignId('preferred_driver_profile_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            }
        });

        // Every rider gets a handle now; they and their company can change it later.
        $taken = array_flip(array_merge(
            DB::table('provider_profiles')->whereNotNull('public_slug')->pluck('public_slug')->map(fn ($s) => strtolower($s))->all(),
            DB::table('driver_profiles')->whereNotNull('handle')->pluck('handle')->all(),
        ));
        $rows = DB::table('driver_profiles as d')->join('users as u', 'u.id', '=', 'd.user_id')->whereNull('d.handle')->get(['d.id', 'u.name']);
        foreach ($rows as $r) {
            $base = substr(preg_replace('/[^a-z0-9]/', '', strtolower(strtok((string) $r->name, ' ') ?: 'rider')) ?: 'rider', 0, 20);
            $h = $base;
            for ($i = 0; isset($taken[$h]) || strlen($h) < 3; $i++) {
                $h = $base.random_int(10, 9999);
            }
            $taken[$h] = true;
            DB::table('driver_profiles')->where('id', $r->id)->update(['handle' => $h]);
        }
    }

    public function down(): void
    {
        Schema::table('service_requests', fn (Blueprint $t) => $t->dropConstrainedForeignId('preferred_driver_profile_id'));
        Schema::table('driver_profiles', fn (Blueprint $t) => $t->dropColumn('handle'));
    }
};
