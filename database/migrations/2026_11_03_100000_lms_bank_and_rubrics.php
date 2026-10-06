<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2B: a reusable question bank, plus assignment rubrics.
 *  - lms_question_bank mirrors lms_quiz_questions (minus quiz_id) with a subject
 *    and free-text tag for filtering; questions are copied into quizzes on import.
 *  - lms_assignments.rubric: [{name, max}] criteria.
 *  - lms_assignment_submissions.rubric_scores: {criterion_index: points}.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lms_question_bank')) {
            Schema::create('lms_question_bank', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->string('tag', 80)->nullable()->index();
                $table->text('question');
                $table->string('type', 20)->default('single');
                $table->json('options')->nullable();
                $table->json('correct')->nullable();
                $table->json('accepted_answers')->nullable();
                $table->text('explanation')->nullable();
                $table->string('image_path')->nullable();
                $table->unsignedInteger('points')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('lms_assignments')) {
            Schema::table('lms_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('lms_assignments', 'rubric')) {
                    $table->json('rubric')->nullable()->after('max_score');
                }
            });
        }

        if (Schema::hasTable('lms_assignment_submissions')) {
            Schema::table('lms_assignment_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('lms_assignment_submissions', 'rubric_scores')) {
                    $table->json('rubric_scores')->nullable()->after('score');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_question_bank');
        if (Schema::hasTable('lms_assignments')) {
            Schema::table('lms_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('lms_assignments', 'rubric')) $table->dropColumn('rubric');
            });
        }
        if (Schema::hasTable('lms_assignment_submissions')) {
            Schema::table('lms_assignment_submissions', function (Blueprint $table) {
                if (Schema::hasColumn('lms_assignment_submissions', 'rubric_scores')) $table->dropColumn('rubric_scores');
            });
        }
    }
};
