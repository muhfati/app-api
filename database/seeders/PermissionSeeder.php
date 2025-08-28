<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('permissions')->delete();
        Permission::truncate();

        $permissions = [

            'View Dashboard',
            'View Permission',
            'System Audit',

            'Zone A Permission',
            'Zone B Permission',

            'Create Menu Group',
            'Update Menu Group',
            'Delete Menu Group',
            'View Menu Group',  

            'Create Menu Item',
            'Update Menu Item',
            'Delete Menu Item',
            'View Menu Item',
        
            'Create User',
            'Update User',
            'Delete User',
            'View User',

            'Create Role',
            'Update Role',
            'Delete Role',
            'View Role',

         ];

         foreach ($permissions as $permission) {

            Permission::create(['name' => $permission,'guard_name'=>'web']);

         }
    }
}