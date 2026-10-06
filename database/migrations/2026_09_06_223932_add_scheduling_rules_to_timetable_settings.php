<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // STEP 1: First, ensure max_lessons_per_day exists
        // This is the column that other columns are trying to reference with 'after'
        if (!Schema::hasColumn('timetable_settings', 'max_lessons_per_day')) {
            Schema::table('timetable_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_lessons_per_day')->nullable();
            });
        }

        // STEP 2: Now add all other columns
        Schema::table('timetable_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable_settings', 'free_periods_per_week')) {
                $table->unsignedInteger('free_periods_per_week')->nullable()->default(0);
            }
            if (!Schema::hasColumn('timetable_settings', 'lessons_per_day')) {
                $table->unsignedTinyInteger('lessons_per_day')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'short_break_after_period')) {
                $table->unsignedTinyInteger('short_break_after_period')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'long_break_after_period')) {
                $table->unsignedTinyInteger('long_break_after_period')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'assembly_day')) {
                $table->string('assembly_day')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'half_days')) {
                $table->json('half_days')->nullable();
            }
            if (!Schema::hasColumn('timetable_settings', 'deprioritize_break_adjacent')) {
                $table->boolean('deprioritize_break_adjacent')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            foreach ([
                'free_periods_per_week', 'max_lessons_per_day', 'lessons_per_day',
                'short_break_after_period', 'long_break_after_period', 'assembly_day',
                'half_days', 'deprioritize_break_adjacent',
            ] as $col) {
                if (Schema::hasColumn('timetable_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};