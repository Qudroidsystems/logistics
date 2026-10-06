<?php
// database/migrations/2025_01_15_000006_create_timetable_reports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('timetable_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_name', 100);
            $table->enum('report_type', ['teacher_workload', 'room_utilization', 'class_schedule', 'conflict_analysis', 'attendance_summary']);
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->json('filters')->nullable();
            $table->json('data')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('set null');
            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('timetable_reports');
    }
};
