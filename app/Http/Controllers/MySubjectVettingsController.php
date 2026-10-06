<?php

namespace App\Http\Controllers;

/**
 * "Subjects to Vet" -- terminal-result broadsheets assigned to this user for
 * vetting. All behaviour lives in BaseSubjectVettingController.
 */
class MySubjectVettingsController extends BaseSubjectVettingController
{
    protected function config(): array
    {
        return [
            'permission'        => 'View my-subject-vettings',
            'admin_permissions' => ['View subject-vettings', 'Update subject-vettings'],
            'assignment_table'  => 'subject_vettings',
            'sheet_table'       => 'broadsheets',
            'record_table'      => 'broadsheet_records',
            'record_fk'         => 'broadsheet_record_id',
            'mode'              => 'terminal',
            'view_prefix'       => 'mysubjectvettings',
            'routes'            => [
                'index'      => 'mysubjectvettings.index',
                'broadsheet' => 'mysubjectvettings.classbroadsheet',
                'toggle'     => 'broadsheets.update-vetted-status',
                'bulk'       => 'mysubjectvettings.bulk-vet',
                'status'     => 'mysubjectvettings.status',
            ],
            'labels'            => [
                'title' => 'My Subject Vetting Assignments',
                'hero'  => 'My Subject Vetting',
                'short' => 'Vetting',
            ],
        ];
    }
}
