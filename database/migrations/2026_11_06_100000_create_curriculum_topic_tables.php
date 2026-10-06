<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curriculum topics & coverage tracking.
 *  - subject_topics: the syllabus — one set per subject × class LEVEL × term
 *    (class level = schoolclass.schoolclass name, shared across arms).
 *  - topic_progress: per-arm delivery state, keyed by subjectclass (the teacher's
 *    assignment for that subject + arm + term/session).
 *  - class_reps + topic_confirmations: student confirmation that a topic was taught.
 * Links to subject/class/term/user are plain ids (no FK constraints) to drop onto
 * the existing production DB cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subject_topics')) {
            Schema::create('subject_topics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id')->index();
                $table->string('class_level', 100)->index();   // schoolclass.schoolclass, e.g. "JSS 1"
                $table->unsignedBigInteger('term_id')->nullable()->index();
                $table->unsignedInteger('week_no')->nullable();  // scheme-of-work default week
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('topic_progress')) {
            Schema::create('topic_progress', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_topic_id')->index();
                $table->unsignedBigInteger('subjectclass_id')->index();   // per-arm assignment
                $table->string('status', 20)->default('pending');          // pending | taught | confirmed
                $table->unsignedInteger('planned_week')->nullable();       // per-arm override of the scheme week
                $table->date('planned_date')->nullable();
                $table->date('taught_on')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('taught_by')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();     // HOD
                $table->timestamp('verified_at')->nullable();
                $table->text('hod_comment')->nullable();
                $table->boolean('student_confirmed')->default(false);
                $table->boolean('disputed')->default(false);
                $table->timestamps();
                $table->unique(['subject_topic_id', 'subjectclass_id'], 'topic_progress_unique');
            });
        }

        if (!Schema::hasTable('class_reps')) {
            Schema::create('class_reps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('schoolclass_id')->index();     // arm
                $table->unsignedBigInteger('session_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('topic_confirmations')) {
            Schema::create('topic_confirmations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('topic_progress_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('action', 12);   // confirm | dispute
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_confirmations');
        Schema::dropIfExists('class_reps');
        Schema::dropIfExists('topic_progress');
        Schema::dropIfExists('subject_topics');
    }
};
