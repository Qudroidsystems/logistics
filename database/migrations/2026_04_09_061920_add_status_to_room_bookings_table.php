<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToRoomBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('room_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('room_bookings', 'status')) {
                $table->enum('status', ['confirmed', 'cancelled', 'completed'])->default('confirmed')->after('booked_by');
            }
        });
    }

    public function down()
    {
        Schema::table('room_bookings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
