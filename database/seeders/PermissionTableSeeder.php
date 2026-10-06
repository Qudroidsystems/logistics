<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
        //    'Super role',
           'View user',
           'Create user',
           'Update user',
           'Delete user',
           
           'View role',
           'Create role',
           'Update role',
           'Delete role',
           'Add user-role',
           'Update user-role',
           'Remove user-role',

           'View permission',
           'Create permission',
           'Update permission',
           'Delete permission',

           'dashboard',

           'View session',
           'Create session',
           'Update session',
           'Delete session',

           'View term',
           'Create term',
           'Update term',
           'Delete term',

           'View schoolhouse',
           'Create schoolhouse',
           'Update schoolhouse',
           'Delete schoolhouse',

           'View school-arm',
           'Create school-arm',
           'Update school-arm',
           'Delete school-arm',

           'View school-class',
           'Create school-class',
           'Update school-class',
           'Delete school-class',
           
           'View class-category',
           'Create class-category',
           'Update class-category',
           'Delete class-category',

           'View class-teacher',
           'Create class-teacher',
           'Update class-teacher',
           'Delete class-teacher',

           'View subjects',
           'Create subjects',
           'Update subjects',
           'Delete subjects',

           'View subject-teacher',
           'Create subject-teacher',
           'Update subject-teacher',
           'Delete subject-teacher',
           
           'View subject-class',
           'Create subject-class',
           'Update subject-class',
           'Delete subject-class',

        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if($word == "user")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "User Management"]);

                if($word == "role" || $word == "user-role")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Role Management"]);

                if($word == "permission")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Permission Management"]);

                if($word == "dashboard")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Dashboard Management"]);

                if($word == "school-arm")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "School Arm Management"]);

                if($word == "school-class")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "School ClassManagement"]);

                if($word == "session")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "School Session Management"]);

                if($word == "term")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "School Term Management "]);

                if($word == "schoolhouse")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "School House Management"]);

                if($word == "class-category")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Class Category Management"]);

                if($word == "class-teacher")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Class Teacher Management"]);

                if($word == "subjects")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Subject Management"]);

                if($word == "subject-teacher")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Subject Teacher Management"]);

                if($word == "subject-class")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Subject Class Management"]);



                // if($word == "class_operation")
                // Permission::Create(['name' => $permission,'title'=>"Class Operation Management"]);

                // if($word == "myclass")
                // Permission::Create(['name' => $permission,'title'=>"User Class Management"]);

                // if($word == "mysubject")
                // Permission::Create(['name' => $permission,'title'=>"User Subject Management"]);

           
                // if($word == "myresultroom")
                // Permission::Create(['name' => $permission,'title'=>"User Result Room Management"]);

                // if($word == "parent")
                // Permission::Create(['name' => $permission,'title'=>"Parent Management"]);

                // if($word == "student")
                // Permission::Create(['name' => $permission,'title'=>"Student Management"]);

                // if($word == "studentresults")
                // Permission::Create(['name' => $permission,'title'=>"Student Results Management"]);

                // if($word == "student_bulk")
                // Permission::Create(['name' => $permission,'title'=>"Student Management"]);

             

                // if($word == "subject_operation")
                // Permission::Create(['name' => $permission,'title'=>"Subject Operations Management"]);

                if($word == "subject")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Subject Management"]);

                if($word == "subject_teacher")
                Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web'], ['title' => "Subject Teacher Management"]);

                // if($word == "View_student")
                // Permission::Create(['name' => $permission,'title'=>"View Student Management "]);

                // if($word == "academic_operations")
                // Permission::Create(['name' => $permission,'title'=>"Basic Settings Management Link"]);

                // if($word == "student_picture")
                // Permission::Create(['name' => $permission,'title'=>"Student Picture Management"]);

                // if($word == "studenthouse")
                // Permission::Create(['name' => $permission,'title'=>"Student house Management"]);

                // if($word == "classsettings")
                // Permission::Create(['name' => $permission,'title'=>"Class Settings Management "]);

            }
            //  Permission::Create(['name' => $permission]);
        }
    }
}
