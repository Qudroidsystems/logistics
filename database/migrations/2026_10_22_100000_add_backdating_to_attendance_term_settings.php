<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin control over how far back a class teacher may mark attendance.
 * allow_backdating = false → teachers can only mark today.
 * allow_backdating = true  → teachers can mark up to backdate_days in the past.
 * Admins (Create attendance-settings) are never restricted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_term_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_term_settings', 'allow_backdating')) {
                $table->boolean('allow_backdating')->default(true)->after('track_afternoon');
            }
            if (!Schema::hasColumn('attendance_term_settings', 'backdate_days')) {
                $table->unsignedSmallInteger('backdate_days')->default(7)->after('allow_backdating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_term_settings', function (Blueprint $table) {
            foreach (['allow_backdating', 'backdate_days'] as $c) {
                if (Schema::hasColumn('attendance_term_settings', $c)) $table->dropColumn($c);
            }
        });
    }
};
