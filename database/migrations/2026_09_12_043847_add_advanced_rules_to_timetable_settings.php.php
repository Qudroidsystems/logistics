<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->json('advanced_rules')->nullable()->after('deprioritize_break_adjacent');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->dropColumn('advanced_rules');
        });
    }
};