<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Undo log for the Promotion Management screen. Every promotion, bulk
 * promotion, term advance, decision clear and class removal stores a
 * per-student snapshot of studentclass / promotionStatus /
 * student_current_term BEFORE and AFTER the change, grouped by batch_id
 * (one batch = one click). Reverting restores the "before" snapshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotion_action_logs')) return;

        Schema::create('promotion_action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 36)->index();
            $table->string('action', 30);               // promote | bulk_promote | advance_term | clear_decision | remove | revert
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('schoolclass_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->longText('before_state');
            $table->longText('after_state');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->unsignedBigInteger('reverted_by')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'schoolclass_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_action_logs');
    }
};
