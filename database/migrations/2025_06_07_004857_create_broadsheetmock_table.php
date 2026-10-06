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
        // Broadsheet Records
        Schema::create('broadsheet_records_mock', function (Blueprint $table) {
            $table->id();
        
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('schoolclass_id');
            $table->unsignedBigInteger('session_id');
        
            $table->timestamps();
        
            // Use correct table names in foreign key references
            $table->foreign('student_id')->references('id')->on('studentRegistration')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subject')->onDelete('cascade');
            $table->foreign('schoolclass_id')->references('id')->on('schoolclass')->onDelete('cascade'); // assuming table is `schoolclass`
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');
        });
        

        // Broadsheets
        Schema::create('broadsheetmock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('broadsheet_records_mock_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('subjectclass_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->double('exam', 5, 2)->default(0);
            $table->double('total', 5, 2)->default(0);
            $table->string('grade')->nullable();
            $table->double('all_subjects_total_score', 6, 2)->default(0);
            $table->string('subject_position_class')->nullable();
            $table->double('cmin', 5, 2)->default(0);
            $table->double('cmax', 5, 2)->default(0);
            $table->double('avg', 5, 2)->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->foreign('broadsheet_records_mock_id')->references('id')->on('broadsheet_records_mock')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
            $table->foreign('subjectclass_id')->references('id')->on('subjectclass')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadsheetmock');
        Schema::dropIfExists('broadsheet_records_mock');
    }
};
