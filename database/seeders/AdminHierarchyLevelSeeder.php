<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminHierarchyLevel;
use Illuminate\Support\Str;

class AdminHierarchyLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_hierarchy_levels')->truncate();
        
        $admin_hierarchy_levels = AdminHierarchyLevel::create([

            'uuid'=> Str::uuid(),
            'name' => 'COUNTRY', 
            'code' => '001', 
            'position' => 1
        ]);

        $admin_hierarchy_levels = AdminHierarchyLevel::create([

            'uuid'=> Str::uuid(),
            'name' => 'ISLAND', 
            'code' => '002', 
            'position' => 2
        ]);

        $admin_hierarchy_levels = AdminHierarchyLevel::create([

            'uuid'=> Str::uuid(),
            'name' => 'REGION', 
            'code' => '003', 
            'position' => 3
        ]);

        $admin_hierarchy_levels = AdminHierarchyLevel::create([

            'uuid'=> Str::uuid(),
            'name' => 'DISTRICT', 
            'code' => '004', 
            'position' => 4
        ]);

        $admin_hierarchy_levels = AdminHierarchyLevel::create([

            'uuid'=> Str::uuid(),
            'name' => 'WARD', 
            'code' => '005', 
            'position' => 5
        ]);

        $admin_hierarchy_levels = AdminHierarchyLevel::create([

            'uuid'=> Str::uuid(),
            'name' => 'SHEHIA', 
            'code' => '006', 
            'position' => 6
        ]);
    }
}
