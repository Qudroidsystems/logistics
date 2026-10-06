<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Report card approval: one row per class + term + session.
 * pending -> submitted (class teacher) -> approved (principal) | returned
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('report_approvals')) {
            Schema::create('report_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schoolclass_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('session_id');
                $table->string('status', 20)->default('pending')->index(); // pending | submitted | approved | returned
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->string('submit_note', 500)->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('review_note', 500)->nullable();
                $table->json('snapshot')->nullable();   // readiness figures at submit/approve time
                $table->json('history')->nullable();    // [{at, by, action, note}]
                $table->timestamps();
                $table->unique(['schoolclass_id', 'term_id', 'session_id'], 'uq_report_approval_class_term_session');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_approvals');
    }
};
