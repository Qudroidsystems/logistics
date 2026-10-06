<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Exam question vetting, topic tagging, per-question scores,
 * question bank and coverage/alignment.
 *
 * All cross-module links are plain ids (no FK constraints) so the module
 * drops cleanly onto production regardless of engine quirks on legacy tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_papers')) {
            Schema::create('exam_papers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('subjectclass_id')->index();
                $t->unsignedBigInteger('subject_id')->nullable()->index();
                $t->string('class_level')->nullable();         // schoolclass.schoolclass name
                $t->unsignedBigInteger('term_id')->nullable()->index();
                $t->unsignedBigInteger('session_id')->nullable()->index();
                $t->unsignedBigInteger('teacher_id')->nullable()->index();
                $t->string('title');
                $t->string('exam_type')->default('exam');       // exam | test | midterm | mock
                $t->decimal('total_marks', 7, 2)->default(0);
                $t->unsignedInteger('duration_minutes')->nullable();
                $t->text('instructions')->nullable();
                // draft | submitted | changes_requested | approved | locked
                $t->string('status')->default('draft')->index();
                $t->unsignedBigInteger('vetted_by')->nullable();
                $t->timestamp('vetted_at')->nullable();
                $t->unsignedBigInteger('locked_by')->nullable();
                $t->timestamp('locked_at')->nullable();
                $t->text('vet_summary')->nullable();
                $t->timestamps();
                $t->index(['subjectclass_id', 'term_id', 'session_id']);
            });
        }

        if (!Schema::hasTable('exam_questions')) {
            Schema::create('exam_questions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_paper_id')->index();
                $t->string('section')->nullable();              // A / B / Objective / Theory
                $t->string('number')->nullable();              // display number: 1, 2a, …
                $t->string('type')->default('theory');         // objective | theory | practical
                $t->text('question');
                $t->json('options')->nullable();               // objectives: {A:..,B:..}
                $t->text('answer')->nullable();                // correct answer / marking guide
                $t->decimal('marks', 6, 2)->default(1);
                $t->string('difficulty')->nullable();          // easy | medium | hard
                $t->string('bloom')->nullable();               // Bloom's level (optional)
                $t->unsignedInteger('position')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('exam_question_topic')) {
            Schema::create('exam_question_topic', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_question_id')->index();
                $t->unsignedBigInteger('subject_topic_id')->index();
                $t->unique(['exam_question_id', 'subject_topic_id'], 'eqt_unique');
            });
        }

        if (!Schema::hasTable('exam_vet_comments')) {
            Schema::create('exam_vet_comments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_paper_id')->index();
                $t->unsignedBigInteger('exam_question_id')->nullable()->index(); // null = paper-level
                $t->unsignedBigInteger('user_id')->nullable();
                $t->text('comment')->nullable();
                // comment | submit | approve | return | lock | unlock
                $t->string('action')->default('comment');
                $t->boolean('resolved')->default(false);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('exam_question_scores')) {
            Schema::create('exam_question_scores', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_question_id')->index();
                $t->unsignedBigInteger('student_id')->index();
                $t->decimal('score', 6, 2)->nullable();
                $t->timestamps();
                $t->unique(['exam_question_id', 'student_id'], 'eqs_unique');
            });
        }

        if (!Schema::hasTable('exam_bank_questions')) {
            Schema::create('exam_bank_questions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('subject_id')->index();
                $t->string('class_level')->nullable()->index();
                $t->string('type')->default('theory');
                $t->text('question');
                $t->json('options')->nullable();
                $t->text('answer')->nullable();
                $t->decimal('marks', 6, 2)->default(1);
                $t->string('difficulty')->nullable();
                $t->string('bloom')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('times_used')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('exam_bank_question_topic')) {
            Schema::create('exam_bank_question_topic', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_bank_question_id')->index();
                $t->unsignedBigInteger('subject_topic_id')->index();
                $t->unique(['exam_bank_question_id', 'subject_topic_id'], 'ebqt_unique');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'exam_bank_question_topic', 'exam_bank_questions', 'exam_question_scores',
            'exam_vet_comments', 'exam_question_topic', 'exam_questions', 'exam_papers',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
