<?php
// database/migrations/2025_01_15_000007_create_holidays_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['public_holiday', 'school_holiday', 'exam_period', 'special_event']);
            $table->boolean('affects_timetable')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });


    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
