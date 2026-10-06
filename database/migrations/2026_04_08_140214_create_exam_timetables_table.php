<?php
// database/migrations/2025_01_15_000005_create_exam_timetables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exam_timetables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('term_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('exam_type', ['mid_term', 'end_of_term', 'mock', 'entrance', 'other']);
            $table->text('instructions')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
        });


    }

    public function down()
    {
        Schema::dropIfExists('exam_timetables');
    }
};
