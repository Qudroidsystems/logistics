<?php

namespace Database\Seeders;

use App\Models\BioModel;
use Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash as FacadesHash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserTableSeeder extends Seeder
{

    use HasRoles;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Safe to run again: the admin user and roles are only created if missing,
        // and an existing password is never reset.
        $user = User::where('email', 'eliabsiji@gmail.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Ilemobayo Eliab',
                'email' => 'eliabsiji@gmail.com',
                'avatar' => 'unnamed.png',
                'password' => FacadesHash::make('12345678'),
            ]);
        }

        if (!BioModel::where('user_id', $user->id)->exists()) {
            BioModel::create(['user_id' => $user->id,
                              'firstname' => 'ilemobayo',
                              'lastname' => 'Eliab',
                              'othernames' => 'siji',
                              'phone' => '98385523567',
                              'address' => 'ondo',
                              'gender' => 'male',
                              'maritalstatus' => 'Single',
                              'nationality' => 'nigerian',
                              'dob' => '12-12-12']);
        }

        $role = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first()
            ?? Role::create(['name' => 'Super Admin', 'badge' => 'badge bg-success']);
        $role2 = Role::where('name', 'Admin')->where('guard_name', 'web')->first()
            ?? Role::create(['name' => 'Admin', 'badge' => 'badge bg-primary']);

        $permissions = Permission::pluck('id', 'id')->all();
        $role->syncPermissions($permissions);
        $role2->syncPermissions($permissions);
        if (!$user->hasRole($role->name)) $user->assignRole($role);
        if (!$user->hasRole($role2->name)) $user->assignRole($role2);
    }
}
