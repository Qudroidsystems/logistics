<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-learning assessment: assignments (with file/text submissions and teacher
 * grading) and native lesson quizzes (questions + attempts). Graded formal tests
 * reuse the existing CBT/Exam module via lms_lessons.exam_id instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lms_assignments')) {
            Schema::create('lms_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('lesson_id')->nullable()->index();
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->decimal('max_score', 6, 2)->default(100);
                $table->timestamp('due_at')->nullable();
                $table->boolean('allow_file')->default(true);
                $table->boolean('allow_text')->default(true);
                $table->boolean('is_published')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_assignment_submissions')) {
            Schema::create('lms_assignment_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assignment_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->text('body')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->enum('status', ['submitted', 'graded', 'returned'])->default('submitted');
                $table->decimal('score', 6, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->unsignedBigInteger('graded_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();
                $table->unique(['assignment_id', 'student_id']);
            });
        }

        if (!Schema::hasTable('lms_quizzes')) {
            Schema::create('lms_quizzes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('lesson_id')->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('pass_mark')->default(50);   // percent
                $table->unsignedInteger('max_attempts')->default(0);     // 0 = unlimited
                $table->unsignedInteger('time_limit_minutes')->nullable();
                $table->boolean('shuffle')->default(false);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_quiz_questions')) {
            Schema::create('lms_quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quiz_id')->index();
                $table->text('question');
                $table->string('type', 20)->default('single');  // single | multiple | boolean
                $table->json('options')->nullable();             // ["A","B",...]
                $table->json('correct')->nullable();             // [0,2] indexes
                $table->unsignedInteger('points')->default(1);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_quiz_attempts')) {
            Schema::create('lms_quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quiz_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->json('answers')->nullable();             // {question_id: [idx,...]}
                $table->decimal('score', 6, 2)->default(0);
                $table->decimal('max_score', 6, 2)->default(0);
                $table->unsignedTinyInteger('percent')->default(0);
                $table->boolean('passed')->default(false);
                $table->unsignedInteger('attempt_no')->default(1);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_quiz_attempts');
        Schema::dropIfExists('lms_quiz_questions');
        Schema::dropIfExists('lms_quizzes');
        Schema::dropIfExists('lms_assignment_submissions');
        Schema::dropIfExists('lms_assignments');
    }
};
