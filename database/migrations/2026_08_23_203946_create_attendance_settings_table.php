<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row settings table. DeviceAttendanceProcessor reads this to decide
 * "present" vs "late" when it classifies an incoming punch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->time('late_time')->default('08:00:00');      // punch-in after this = late
            $table->time('close_time')->nullable();                // reserved for future "early departure" flag
            $table->unsignedInteger('grace_minutes')->default(0);  // added on top of late_time before flagging late
            $table->timestamps();
        });

        DB::table('attendance_settings')->insert([
            'late_time'     => '08:00:00',
            'grace_minutes' => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};