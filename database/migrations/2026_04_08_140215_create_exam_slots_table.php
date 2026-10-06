<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       
         Schema::create('exam_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_timetable_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->integer('duration_minutes');
            $table->decimal('total_marks', 8, 2)->default(100);
            $table->timestamps();

            $table->foreign('exam_timetable_id')->references('id')->on('exam_timetables')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subject')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('schoolclass')->onDelete('cascade');
            $table->foreign('venue_id')->references('id')->on('rooms')->onDelete('set null');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_slots');
    }
};
