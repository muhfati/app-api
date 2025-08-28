<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('users')->delete();
        DB::table('users')->truncate();
        $user = User::create([

            'uuid'=> Str::uuid(),
            'first_name' => 'System', 
            'middle_name' => 'Supper', 
            'last_name' => 'Admin', 
            'location_id' => '100012', 
            'gender' => 'Male', 
            'phone_no' => '0777000001', 
            'date_of_birth' => '1990-10-30', 
            'email' => 'info@mohz.go.tz',
            'password' => bcrypt('Admin@123')
        ]);

        $role = Role::create(['name' => 'ROLE ADMIN']);

        $permissions = Permission::pluck('id','id')->all();

        $role->syncPermissions($permissions);
        $user->givePermissionTo($permissions);
        $user->assignRole([$role->id]);
    }
}
