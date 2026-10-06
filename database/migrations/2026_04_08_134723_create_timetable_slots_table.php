<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->unsignedBigInteger('period_id');
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->boolean('is_double')->default(false);
            $table->boolean('is_free')->default(false);
            $table->string('notes', 191)->nullable();
            $table->timestamps();

            $table->unique(['setting_id', 'period_id', 'day'], 'slot_unique');
            $table->index(['setting_id', 'day']);
            $table->index('teacher_id');
            $table->index(['teacher_id', 'day', 'period_id']);

            $table->foreign('setting_id')->references('id')->on('timetable_settings')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('timetable_periods')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subject')->onDelete('set null');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
