<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson notes: draft → submitted → approved/returned → delivered, linked to the
 * week's syllabus topics, with a teaching-methods library.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teaching_methods')) {
            Schema::create('teaching_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lesson_notes')) {
            Schema::create('lesson_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subjectclass_id')->index(); // teacher's subject+arm assignment
                $table->unsignedBigInteger('subject_id')->index();
                $table->string('class_level', 100)->index();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->unsignedInteger('week_no')->nullable();
                $table->string('title');
                $table->text('objectives')->nullable();
                $table->longText('content')->nullable();
                $table->text('materials')->nullable();
                $table->json('methods')->nullable();        // teaching_methods ids
                $table->string('status', 20)->default('draft'); // draft|submitted|approved|returned|delivered
                $table->unsignedBigInteger('teacher_id')->index();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_comment')->nullable();
                $table->date('delivered_on')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lesson_note_topic')) {
            Schema::create('lesson_note_topic', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lesson_note_id')->index();
                $table->unsignedBigInteger('subject_topic_id')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_note_topic');
        Schema::dropIfExists('lesson_notes');
        Schema::dropIfExists('teaching_methods');
    }
};
