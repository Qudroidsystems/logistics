<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Clean up duplicate (studentId, termId, sessionId) rows BEFORE
        // adding the new unique index, keeping only the most recent record per combo.
        DB::statement("
            DELETE sct1
            FROM student_current_term sct1
            INNER JOIN student_current_term sct2
                ON  sct1.studentId  = sct2.studentId
                AND sct1.termId     = sct2.termId
                AND sct1.sessionId  = sct2.sessionId
                AND sct1.id < sct2.id
        ");

        // Step 2: For each student, ensure only one row has is_current = 1
        // (keep the most recent one, set the rest to 0).
        DB::statement("
            UPDATE student_current_term sct
            INNER JOIN (
                SELECT studentId, MAX(id) as max_id
                FROM student_current_term
                WHERE is_current = 1
                GROUP BY studentId
            ) latest ON sct.studentId = latest.studentId
            SET sct.is_current = 0
            WHERE sct.is_current = 1
              AND sct.id != latest.max_id
        ");

        Schema::table('student_current_term', function (Blueprint $table) {
            // Drop the old broken constraint
            $table->dropUnique('student_current_term_studentid_is_current_unique');

            // Add the correct constraint: one registration per student per term+session
            $table->unique(
                ['studentId', 'termId', 'sessionId'],
                'student_current_term_student_term_session_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_current_term', function (Blueprint $table) {
            $table->dropUnique('student_current_term_student_term_session_unique');

            // Restore original (you may need to clean data again before this works)
            $table->unique(
                ['studentId', 'is_current'],
                'student_current_term_studentid_is_current_unique'
            );
        });
    }
};
