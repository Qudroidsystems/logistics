<?php
// database/migrations/2025_01_15_000004_create_teacher_workload_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teacher_workload_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->integer('max_periods_per_day')->default(6);
            $table->integer('max_periods_per_week')->default(30);
            $table->integer('max_consecutive_periods')->default(3);
            $table->boolean('allow_split_classes')->default(true);
            $table->json('preferred_time_slots')->nullable(); // Morning/Afternoon preferences
            $table->timestamps();

            $table->unique('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
        });


    }

    public function down()
    {
        Schema::dropIfExists('teacher_workload_settings');
    }
};
