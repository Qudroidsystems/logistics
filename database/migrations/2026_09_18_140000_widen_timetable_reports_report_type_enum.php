<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `report_type` was created as a fixed MySQL ENUM (see
     * 2025_01_15_000006_create_timetable_reports_table.php) listing only
     * the five report types that existed at the time. The app has since
     * grown a "Subject Distribution" report --
     * TimetableReportController::generate()'s validation already allows
     * `subject_distribution` -- but nothing ever widened the DB column,
     * so generating that report fails with "Data truncated for column
     * 'report_type'". Switching to a plain VARCHAR removes the need for
     * a migration every time a new report type is added in the future.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `timetable_reports` MODIFY `report_type` VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Revert to the original fixed enum. Any row holding a value
        // outside that original list (e.g. subject_distribution) would
        // violate the enum, so those rows are normalized first.
        DB::statement("
            UPDATE `timetable_reports`
            SET `report_type` = 'class_schedule'
            WHERE `report_type` NOT IN ('teacher_workload', 'room_utilization', 'class_schedule', 'conflict_analysis', 'attendance_summary')
        ");
        DB::statement("
            ALTER TABLE `timetable_reports`
            MODIFY `report_type` ENUM('teacher_workload', 'room_utilization', 'class_schedule', 'conflict_analysis', 'attendance_summary') NOT NULL
        ");
    }
};
