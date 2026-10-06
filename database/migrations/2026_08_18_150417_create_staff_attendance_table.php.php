<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id'); // staffbioinfo.id
            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['present', 'late', 'excused'])->default('present');
            $table->enum('source', ['device', 'manual'])->default('device');
            $table->unsignedBigInteger('marked_by')->nullable(); // null for device-generated rows
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance');
    }
};
