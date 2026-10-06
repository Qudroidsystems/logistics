<?php
// database/migrations/2026_09_06_000004_add_double_cooldown_to_timetable_constraints.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_constraints', function (Blueprint $table) {
            $table->boolean('avoid_consecutive_double_days')->default(true)->after('is_compulsory');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_constraints', function (Blueprint $table) {
            $table->dropColumn('avoid_consecutive_double_days');
        });
    }
};