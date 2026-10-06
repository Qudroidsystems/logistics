<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-learning engagement: threaded discussions / Q&A, scheduled live classes and
 * per-course announcements.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lms_discussions')) {
            Schema::create('lms_discussions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->unsignedBigInteger('lesson_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();     // author (users.id)
                $table->unsignedBigInteger('parent_id')->nullable()->index(); // reply threading
                $table->text('body');
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_resolved')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_live_classes')) {
            Schema::create('lms_live_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('provider', 40)->nullable();   // zoom | meet | teams | other
                $table->string('join_url', 1000)->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->enum('status', ['scheduled', 'live', 'ended', 'cancelled'])->default('scheduled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_announcements')) {
            Schema::create('lms_announcements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->index();
                $table->string('title');
                $table->text('body');
                $table->boolean('notify')->default(true);   // push to portal bell / email
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_announcements');
        Schema::dropIfExists('lms_live_classes');
        Schema::dropIfExists('lms_discussions');
    }
};
