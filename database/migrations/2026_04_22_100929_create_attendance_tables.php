<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Term calendar / school days configured by admin ──────────────
        Schema::create('attendance_term_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->date('resumption_date');
            $table->date('vacation_date');
            $table->boolean('track_morning')->default(true);
            $table->boolean('track_afternoon')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['term_id', 'session_id']);
        });

        // ── 2. Holidays / mid-term breaks ───────────────────────────────────
        Schema::create('attendance_holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->date('holiday_date');
            $table->date('holiday_end_date')->nullable(); // for multi-day breaks
            $table->string('holiday_name');
            $table->enum('holiday_type', ['public', 'midterm', 'school_event', 'other'])->default('public');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // ── 3. Daily attendance register ────────────────────────────────────
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('schoolclass_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('marked_by');          // class teacher
            $table->date('attendance_date');
            $table->enum('period', ['morning', 'afternoon'])->default('morning');
            $table->enum('status', ['present', 'absent', 'sick_leave', 'excused', 'late'])->default('absent');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('studentRegistration')->onDelete('cascade');
            $table->foreign('schoolclass_id')->references('id')->on('schoolclass')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');
            $table->foreign('marked_by')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['student_id', 'schoolclass_id', 'term_id', 'session_id', 'attendance_date', 'period'], 'attendance_unique');
            $table->index(['schoolclass_id', 'attendance_date', 'period']);
            $table->index(['student_id', 'term_id', 'session_id']);
        });

        // ── 4. Per-student per-term summary (denormalised for speed) ────────
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('schoolclass_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedInteger('total_school_days')->default(0);
            $table->unsignedInteger('days_present')->default(0);
            $table->unsignedInteger('days_absent')->default(0);
            $table->unsignedInteger('days_sick_leave')->default(0);
            $table->unsignedInteger('days_excused')->default(0);
            $table->unsignedInteger('days_late')->default(0);
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('studentRegistration')->onDelete('cascade');
            $table->foreign('schoolclass_id')->references('id')->on('schoolclass')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');

            $table->unique(['student_id', 'schoolclass_id', 'term_id', 'session_id'], 'summary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('attendance_holidays');
        Schema::dropIfExists('attendance_term_settings');
    }
};
