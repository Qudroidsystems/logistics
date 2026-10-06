<?php
// database/migrations/2026_09_06_000003_add_scheduling_rules_to_timetable_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, add max_lessons_per_day if it doesn't exist
        if (!Schema::hasColumn('timetable_settings', 'max_lessons_per_day')) {
            Schema::table('timetable_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_lessons_per_day')->nullable();
            });
        }

        // Now add all other columns
        Schema::table('timetable_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable_settings', 'lessons_per_day')) {
                $table->unsignedTinyInteger('lessons_per_day')->nullable()->after('max_lessons_per_day');
            }
            if (!Schema::hasColumn('timetable_settings', 'short_break_after_period')) {
                $table->unsignedTinyInteger('short_break_after_period')->nullable()->after('lessons_per_day');
            }
            if (!Schema::hasColumn('timetable_settings', 'long_break_after_period')) {
                $table->unsignedTinyInteger('long_break_after_period')->nullable()->after('short_break_after_period');
            }
            if (!Schema::hasColumn('timetable_settings', 'assembly_day')) {
                $table->string('assembly_day')->nullable()->after('long_break_after_period');
            }
            if (!Schema::hasColumn('timetable_settings', 'half_days')) {
                $table->json('half_days')->nullable()->after('assembly_day');
            }
            if (!Schema::hasColumn('timetable_settings', 'deprioritize_break_adjacent')) {
                $table->boolean('deprioritize_break_adjacent')->default(true)->after('half_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->dropColumn([
                'lessons_per_day',
                'short_break_after_period',
                'long_break_after_period',
                'assembly_day',
                'half_days',
                'deprioritize_break_adjacent'
            ]);
        });
    }
};