<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add snapshot name/notes to existing archive table ──────────────
        Schema::table('subject_unregistration_archive', function (Blueprint $table) {
            // A human-readable name given by the staff member at unregistration time
            $table->string('snapshot_name', 191)->nullable()->after('unregistered_by');
            // Optional longer description / notes
            $table->text('snapshot_notes')->nullable()->after('snapshot_name');

            // Index so we can quickly list/search by name
            $table->index('snapshot_name', 'archive_snapshot_name');
        });

        // ── 2. New table: score snapshots (one row per broadsheet+assessment) ──
        Schema::create('archive_score_snapshots', function (Blueprint $table) {
            $table->id();

            // Links back to the archive record (many scores per archive entry)
            $table->unsignedBigInteger('archive_id');

            // The broadsheet that was deleted — kept for audit even though the
            // broadsheet row itself is hard-deleted during unregistration.
            $table->unsignedBigInteger('broadsheet_id');

            // student / subject context (denormalised so we never need to join
            // back to tables that may not exist after the hard delete)
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('schoolclass_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('subjectclass_id');
            $table->unsignedBigInteger('staff_id');

            // Assessment / sub-assessment details (denormalised)
            $table->unsignedBigInteger('assessment_id');
            $table->string('assessment_name', 100)->nullable();
            $table->unsignedBigInteger('sub_assessment_id')->nullable();
            $table->string('sub_assessment_name', 100)->nullable();

            // The actual score captured at snapshot time
            $table->decimal('score', 8, 2)->default(0.00);

            // 'assessment' or 'sub_assessment'
            $table->string('score_type', 20)->default('assessment');

            $table->timestamps();

            // Indexes
            $table->index('archive_id', 'snap_archive_id');
            $table->index(['student_id', 'session_id', 'term_id'], 'snap_student_session_term');

            // FK — cascade when the archive row itself is hard-deleted
            $table->foreign('archive_id')
                  ->references('id')
                  ->on('subject_unregistration_archive')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_score_snapshots');

        Schema::table('subject_unregistration_archive', function (Blueprint $table) {
            $table->dropIndex('archive_snapshot_name');
            $table->dropColumn(['snapshot_name', 'snapshot_notes']);
        });
    }
};
