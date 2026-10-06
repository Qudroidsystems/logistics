<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row settings table for the STAFF lateness cutoff specifically.
 * Deliberately separate from AttendanceTermSetting (term/session resumption
 * & vacation dates) and AttendanceHoliday (school-wide non-school days) —
 * this table only ever holds one row: the clock-in cutoff time used by
 * DeviceAttendanceProcessor to classify a staff punch as present vs late.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance_time_settings', function (Blueprint $table) {
            $table->id();
            $table->time('late_time')->default('08:00:00');      // punch-in after this = late
            $table->time('close_time')->nullable();                // reserved for a future "early departure" flag
            $table->unsignedInteger('grace_minutes')->default(0);  // added on top of late_time before flagging late
            $table->timestamps();
        });

        DB::table('staff_attendance_time_settings')->insert([
            'late_time'     => '08:00:00',
            'grace_minutes' => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_time_settings');
    }
};