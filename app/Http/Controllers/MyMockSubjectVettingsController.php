<?php

namespace App\Http\Controllers;

/**
 * "Mock Subjects to Vet" -- mock-exam broadsheets (broadsheetmock) assigned
 * to this user for vetting. All behaviour lives in
 * BaseSubjectVettingController.
 *
 * (This file previously held a copy of the ADMIN mock-vetting manager: it
 * listed every staff member's assignments to whoever opened the page, and
 * the broadsheet / vet-toggle routes pointed at methods that didn't exist.
 * Admin management lives in MockSubjectVettingController.)
 */
class MyMockSubjectVettingsController extends BaseSubjectVettingController
{
    protected function config(): array
    {
        return [
            'permission'        => 'View my-mock-subject-vettings',
            'admin_permissions' => ['View mock-subject-vettings', 'Update mock-subject-vettings'],
            'assignment_table'  => 'mock_subject_vettings',
            'sheet_table'       => 'broadsheetmock',
            'record_table'      => 'broadsheet_records_mock',
            'record_fk'         => 'broadsheet_records_mock_id',
            'mode'              => 'mock',
            'view_prefix'       => 'mysubjectvettings',
            'routes'            => [
                'index'      => 'mymocksubjectvettings.index',
                'broadsheet' => 'mymocksubjectvettings.classbroadsheet',
                'toggle'     => 'mymocksubjectvettings.update-vetted-status',
                'bulk'       => 'mymocksubjectvettings.bulk-vet',
                'status'     => 'mymocksubjectvettings.status',
            ],
            'labels'            => [
                'title' => 'My Mock Subject Vetting Assignments',
                'hero'  => 'My Mock Subject Vetting',
                'short' => 'Mock vetting',
            ],
        ];
    }
}
