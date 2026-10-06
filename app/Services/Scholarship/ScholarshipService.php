<?php
// app/Services/Scholarship/ScholarshipService.php

namespace App\Services\Scholarship;

use App\Models\ScholarshipAssignment;
use App\Models\ScholarshipApplication;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class ScholarshipService
{
    /**
     * Process scholarship application
     */
    public function processApplication($applicationId, $decision, $notes = null)
    {
        $application = ScholarshipApplication::findOrFail($applicationId);

        if ($decision === 'approve') {
            $assignment = ScholarshipAssignment::create([
                'scholarship_id' => $application->scholarship_id,
                'student_id' => $application->student_id,
                'application_no' => $application->id,
                'status' => 'active',
                'approved_at' => now(),
                'effective_from' => now(),
                'effective_to' => now()->addYear(),
                'value_type' => $application->scholarship->value_type,
                'value' => $application->scholarship->value,
                'cap_amount' => $application->scholarship->cap_amount,
                'assigned_by' => Auth::id(),
                'approved_by' => Auth::id(),
            ]);

            $application->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'admin_notes' => $notes
            ]);

            return $assignment;
        } else {
            $application->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'rejection_reason' => $notes,
            ]);

            return null;
        }
    }

    /**
     * Get student scholarship summary
     */
    public function getStudentScholarshipSummary($studentId)
    {
        $activeAssignments = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->get();

        return [
            'has_scholarship' => $activeAssignments->isNotEmpty(),
            'total_active' => $activeAssignments->count(),
            'total_savings' => $activeAssignments->sum('value'),
            'scholarships' => []
        ];
    }
}
