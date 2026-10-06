<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SchoolBillTermSessionPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
        //    'Super role',
           'View school-bills',
           'Create school-bills',
           'Update school-bills',
           'Delete school-bills',   

           'View school-bill-for-term-session',
           'Create school-bill-for-term-session',
           'Update school-bill-for-term-session',
           'Delete school-bill-for-term-session',   
        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if($word == "school-bills")
                Permission::Create(['name' => $permission,'title'=>"School bills  Management"]);
            }

            foreach ($words as $word) {
                if($word == "school-bill-for-term-session")
                Permission::Create(['name' => $permission,'title'=>"School bill for Term and Session Management"]);
            }
            //  Permission::Create(['name' => $permission]);
        }
    }
}
