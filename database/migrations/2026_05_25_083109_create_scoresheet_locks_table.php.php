<?php
// database/migrations/2026_05_25_000002_create_scoresheet_locks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('scoresheet_locks')) {
            Schema::create('scoresheet_locks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subjectclass_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('locked_by');
                $table->timestamp('locked_at');
                $table->boolean('is_active')->default(true);
                $table->text('reason')->nullable();
                $table->timestamp('scheduled_unlock_at')->nullable();
                $table->string('auto_unlock_job_id')->nullable();
                $table->timestamps();
            });
        }

        // Add foreign keys
        try {
            Schema::table('scoresheet_locks', function (Blueprint $table) {
                $table->foreign('subjectclass_id', 'sl_subjectclass_id_foreign')->references('id')->on('subjectclass')->onDelete('cascade');
                $table->foreign('term_id', 'sl_term_id_foreign')->references('id')->on('schoolterm')->onDelete('cascade');
                $table->foreign('session_id', 'sl_session_id_foreign')->references('id')->on('schoolsession')->onDelete('cascade');
                $table->foreign('locked_by', 'sl_locked_by_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        // Add unique constraint and indexes
        try {
            if (!Schema::hasIndex('scoresheet_locks', 'unique_scoresheet_lock')) {
                Schema::table('scoresheet_locks', function (Blueprint $table) {
                    $table->unique(['subjectclass_id', 'term_id', 'session_id'], 'unique_scoresheet_lock');
                });
            }
            if (!Schema::hasIndex('scoresheet_locks', 'sl_is_active_index')) {
                Schema::table('scoresheet_locks', function (Blueprint $table) {
                    $table->index('is_active', 'sl_is_active_index');
                });
            }
            if (!Schema::hasIndex('scoresheet_locks', 'sl_scheduled_unlock_index')) {
                Schema::table('scoresheet_locks', function (Blueprint $table) {
                    $table->index('scheduled_unlock_at', 'sl_scheduled_unlock_index');
                });
            }
        } catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::dropIfExists('scoresheet_locks');
    }
};
