<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 assessment upgrade:
 *  - questions: explanation, image, accepted_answers (for short-answer / fill-blank),
 *    and an expanded `type` set (single|multiple|boolean|short_answer|fill_blank|essay).
 *  - quizzes: availability window + partial-credit toggle.
 *  - attempts: manual-grading fields (needs_review, graded_by, graded_at) and a
 *    per-question marks map for teacher-adjusted scores.
 * Course grade weighting is stored in lms_courses.settings (JSON — no column needed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lms_quiz_questions')) {
            Schema::table('lms_quiz_questions', function (Blueprint $table) {
                if (!Schema::hasColumn('lms_quiz_questions', 'explanation'))     $table->text('explanation')->nullable()->after('correct');
                if (!Schema::hasColumn('lms_quiz_questions', 'accepted_answers')) $table->json('accepted_answers')->nullable()->after('explanation');
                if (!Schema::hasColumn('lms_quiz_questions', 'image_path'))       $table->string('image_path')->nullable()->after('accepted_answers');
            });
        }

        if (Schema::hasTable('lms_quizzes')) {
            Schema::table('lms_quizzes', function (Blueprint $table) {
                if (!Schema::hasColumn('lms_quizzes', 'available_from'))  $table->timestamp('available_from')->nullable()->after('time_limit_minutes');
                if (!Schema::hasColumn('lms_quizzes', 'available_until')) $table->timestamp('available_until')->nullable()->after('available_from');
                if (!Schema::hasColumn('lms_quizzes', 'allow_partial'))   $table->boolean('allow_partial')->default(false)->after('shuffle');
            });
        }

        if (Schema::hasTable('lms_quiz_attempts')) {
            Schema::table('lms_quiz_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('lms_quiz_attempts', 'needs_review')) $table->boolean('needs_review')->default(false)->after('passed');
                if (!Schema::hasColumn('lms_quiz_attempts', 'marks'))        $table->json('marks')->nullable()->after('answers'); // {question_id: awarded_points}
                if (!Schema::hasColumn('lms_quiz_attempts', 'graded_by'))    $table->unsignedBigInteger('graded_by')->nullable()->after('needs_review');
                if (!Schema::hasColumn('lms_quiz_attempts', 'graded_at'))    $table->timestamp('graded_at')->nullable()->after('graded_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_quiz_questions')) {
            Schema::table('lms_quiz_questions', function (Blueprint $table) {
                foreach (['explanation', 'accepted_answers', 'image_path'] as $c) {
                    if (Schema::hasColumn('lms_quiz_questions', $c)) $table->dropColumn($c);
                }
            });
        }
        if (Schema::hasTable('lms_quizzes')) {
            Schema::table('lms_quizzes', function (Blueprint $table) {
                foreach (['available_from', 'available_until', 'allow_partial'] as $c) {
                    if (Schema::hasColumn('lms_quizzes', $c)) $table->dropColumn($c);
                }
            });
        }
        if (Schema::hasTable('lms_quiz_attempts')) {
            Schema::table('lms_quiz_attempts', function (Blueprint $table) {
                foreach (['needs_review', 'marks', 'graded_by', 'graded_at'] as $c) {
                    if (Schema::hasColumn('lms_quiz_attempts', $c)) $table->dropColumn($c);
                }
            });
        }
    }
};
