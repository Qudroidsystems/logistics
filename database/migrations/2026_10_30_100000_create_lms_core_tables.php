<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-learning (LMS) core: courses, sections, lessons, enrolments and per-lesson
 * progress. Kept intentionally decoupled from the rest of the schema — links to
 * subject/class/session/term/teacher/student are plain unsigned ids (no FK
 * constraints) so the module drops in cleanly on the existing production DB and
 * never blocks a deploy on a mismatched engine or column type.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lms_courses')) {
            Schema::create('lms_courses', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug', 200)->nullable()->index();
                $table->string('code', 60)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('schoolclass_id')->nullable()->index();
                $table->unsignedBigInteger('session_id')->nullable()->index();
                $table->unsignedBigInteger('term_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();   // users.id
                $table->string('cover_path')->nullable();
                $table->enum('enrollment_mode', ['auto', 'manual', 'both'])->default('both');
                $table->boolean('allow_self_enroll')->default(false);
                $table->unsignedBigInteger('completion_cert_template_id')->nullable(); // certificate_templates.id
                $table->boolean('is_published')->default(false);
                $table->json('settings')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_sections')) {
            Schema::create('lms_sections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_lessons')) {
            Schema::create('lms_lessons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('section_id')->nullable()->index();
                $table->string('title');
                // text | file | video_embed | video_upload | cbt | live
                $table->string('type', 20)->default('text');
                $table->longText('content')->nullable();     // rich text body
                $table->string('video_url')->nullable();     // embed (YouTube/Vimeo/etc.)
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->unsignedBigInteger('exam_id')->nullable();  // reuse CBT/Exam for graded tests
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_preview')->default(false);   // viewable before enrolment
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_enrollments')) {
            Schema::create('lms_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('student_id')->index();  // studentRegistration.id
                $table->enum('source', ['auto', 'manual'])->default('manual');
                $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->unsignedBigInteger('enrolled_by')->nullable();
                $table->timestamp('enrolled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['course_id', 'student_id']);
            });
        }

        if (!Schema::hasTable('lms_lesson_progress')) {
            Schema::create('lms_lesson_progress', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lesson_id')->index();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->boolean('completed')->default(false);
                $table->unsignedInteger('seconds_spent')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['lesson_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_lesson_progress');
        Schema::dropIfExists('lms_enrollments');
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('lms_sections');
        Schema::dropIfExists('lms_courses');
    }
};
