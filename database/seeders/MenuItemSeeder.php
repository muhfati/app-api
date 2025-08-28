<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\MenuItem;
use Illuminate\Support\Str;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menu_items')->truncate();
        
        $menu_items = MenuItem::create([

            'menu_group_id' => 1,
            'uuid'=> Str::uuid(),
            'name' => 'Roles', 
            'sw_name' => 'Roles', 
            'url' => '/main/config/role', 
            'icon' => 'fa fa-roles', 
            'sort_order' => 1
        ]);

        $permission = Permission::where('name', 'View Role')->first();

        if ($permission) {
            $menu_items->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $permissions = Permission::whereIn('name', [
            'View Role',
            'Create Role',
            'Update Role',
            'Delete Role'
        ])->pluck('id')->toArray();

        if (!empty($permissions)) {
            $menu_items->permissions()->syncWithoutDetaching($permissions);
        }

        $menu_items = MenuItem::create([

            'menu_group_id' => 1,
            'uuid'=> Str::uuid(),
            'name' => 'Menu Groups', 
            'sw_name' => 'Orodha Kubwa', 
            'url' => '/main/config/menu-group', 
            'icon' => 'fa fa-expanded', 
            'sort_order' => 2
        ]);

        $permissions = Permission::whereIn('name', [
            'View Menu Group',
            'Create Menu Group',
            'Update Menu Group',
            'Delete Menu Group'
        ])->pluck('id')->toArray();

        if (!empty($permissions)) {
            $menu_items->permissions()->syncWithoutDetaching($permissions);
        }

        $menu_items = MenuItem::create([

            'menu_group_id' => 1,
            'uuid'=> Str::uuid(),
            'name' => 'Menu Items', 
            'sw_name' => 'Ordha Ndogo', 
            'url' => '/main/config/menu-item', 
            'icon' => 'fa fa-bars', 
            'sort_order' => 3
        ]);

        $permissions = Permission::whereIn('name', [
            'View Menu Item',
            'Create Menu Item',
            'Update Menu Item',
            'Delete Menu Item'
        ])->pluck('id')->toArray();

        if (!empty($permissions)) {
            $menu_items->permissions()->syncWithoutDetaching($permissions);
        }

        $menu_items = MenuItem::create([

            'menu_group_id' => 1,
            'uuid'=> Str::uuid(),
            'name' => 'Users', 
            'sw_name' => 'Watumiaji', 
            'url' => '/main/config/user', 
            'icon' => 'fa fa-users', 
            'sort_order' => 4
        ]);

        $permissions = Permission::whereIn('name', [
            'View User',
            'Create User',
            'Update User',
            'Delete User'
        ])->pluck('id')->toArray();

        if (!empty($permissions)) {
            $menu_items->permissions()->syncWithoutDetaching($permissions);
        }
    }
}
