<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->date('date')->nullable();
            $table->enum('recurring_type', ['none', 'weekly', 'biweekly'])->default('none');
            $table->unsignedBigInteger('booked_by');
            $table->text('purpose')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'day', 'start_time', 'end_time']);
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->foreign('booked_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('slot_id')->references('id')->on('timetable_slots')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
