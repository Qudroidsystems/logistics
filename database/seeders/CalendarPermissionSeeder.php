<?php

namespace Database\Seeders;

use App\Models\CalendarCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CalendarPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Manage school calendar', 'View school calendar'] as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'School Calendar']
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Seed default colour-coded categories once.
        if (Schema::hasTable('calendar_categories')) {
            $defaults = [
                ['name' => 'Holiday',            'slug' => 'holiday',          'color' => '#dc2626', 'icon' => 'ri-flag-line',              'sort' => 10],
                ['name' => 'Exam',               'slug' => 'exam',             'color' => '#7c3aed', 'icon' => 'ri-file-list-3-line',       'sort' => 20],
                ['name' => 'Resumption / Vacation','slug' => 'resumption',     'color' => '#0f766e', 'icon' => 'ri-calendar-check-line',    'sort' => 30],
                ['name' => 'PTA / Meeting',      'slug' => 'meeting',          'color' => '#2563eb', 'icon' => 'ri-group-line',             'sort' => 40],
                ['name' => 'Sports',             'slug' => 'sports',           'color' => '#16a34a', 'icon' => 'ri-football-line',          'sort' => 50],
                ['name' => 'Fee deadline',       'slug' => 'fee-deadline',     'color' => '#b45309', 'icon' => 'ri-money-dollar-circle-line','sort' => 60],
                ['name' => 'Event',              'slug' => 'event',            'color' => '#0891b2', 'icon' => 'ri-calendar-event-line',    'sort' => 70],
                ['name' => 'Other',              'slug' => 'other',            'color' => '#64748b', 'icon' => 'ri-more-line',              'sort' => 80],
            ];
            foreach ($defaults as $c) {
                CalendarCategory::firstOrCreate(['slug' => $c['slug']], array_merge($c, ['is_active' => true]));
            }
        }
    }
}
