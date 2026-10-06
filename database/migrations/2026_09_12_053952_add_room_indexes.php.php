<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_class_subject', function (Blueprint $table) {
            $table->index('room_id', 'rcs_room_idx');
        });

        Schema::table('room_bookings', function (Blueprint $table) {
            $table->index(['room_id', 'date'], 'rb_room_date_idx');
            $table->index(['date', 'status'], 'rb_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('room_class_subject', function (Blueprint $table) {
            $table->dropIndex('rcs_room_idx');
        });
        Schema::table('room_bookings', function (Blueprint $table) {
            $table->dropIndex('rb_room_date_idx');
            $table->dropIndex('rb_date_status_idx');
        });
    }
};