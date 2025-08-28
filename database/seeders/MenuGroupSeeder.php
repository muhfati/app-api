<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\MenuGroup;
use Illuminate\Support\Str;

class MenuGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menu_groups')->truncate();

        $menu_groups = MenuGroup::create([

            'uuid'=> Str::uuid(),
            'name' => 'Dashboard', 
            'sw_name' => 'Ubao Mkubwa', 
            'icon' => 'fa fa-dashboard', 
            'sort_order' => 1
        ]);

        $menu_groups = MenuGroup::create([

            'uuid'=> Str::uuid(),
            'name' => 'Configuration', 
            'sw_name' => 'Mpangilio',  
            'icon' => 'fa fa-settings', 
            'sort_order' => 2
        ]);
    }
}
