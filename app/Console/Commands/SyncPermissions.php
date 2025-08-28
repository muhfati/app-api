<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';
    protected $description = 'Sync permissions based on controller methods';

    protected array $ignoreMethods = [
        'AuthController@login',
        'AuthController@logout',
        '__construct',
    ];

    public function handle()
    {
        $this->info("Starting permission sync...");

        $controllerPath = app_path('Http/Controllers/API');
        $controllers = $this->getControllers($controllerPath);

        foreach ($controllers as $controllerClass) {
            $this->syncControllerPermissions($controllerClass);
        }

        $this->info("Permission sync completed.");
    }

    protected function getControllers($path, $namespace = 'App\\Http\\Controllers\\API')
    {
        $controllers = [];
        foreach (glob($path.'/*') as $fileOrDir) {
            if (is_dir($fileOrDir)) {
                $subNamespace = $namespace.'\\'.basename($fileOrDir);
                $controllers = array_merge($controllers, $this->getControllers($fileOrDir, $subNamespace));
            } else if (str_ends_with($fileOrDir, '.php')) {
                $className = $namespace.'\\'.basename($fileOrDir, '.php');
                $controllers[] = $className;
            }
        }
        return $controllers;
    }

    protected function syncControllerPermissions($controllerClass)
    {
        if (!class_exists($controllerClass)) return;

        $reflection = new ReflectionClass($controllerClass);
        $controllerName = class_basename($controllerClass);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $controllerClass) continue; // skip inherited
            if ($method->name === '__construct') continue;   // skip constructor
            if (in_array($controllerName.'@'.$method->name, $this->ignoreMethods)) continue; // skip ignored methods

            $permissionName = $this->generatePermissionName($controllerName, $method->name);
            if (!Permission::where('name', $permissionName)->exists()) {
                Permission::create(['name' => $permissionName]);
                $this->info("Permission created: $permissionName");
            }
        }
    }

    protected function generatePermissionName($controllerName, $methodName)
    {
        // Remove "Controller" suffix
        $resource = str_replace('Controller', '', $controllerName);

        // Split camel case into words (MenuGroup → Menu Group)
        $resource = preg_replace('/(?<!^)[A-Z]/', ' $0', $resource);

        return match($methodName) {
            'index' => "View $resource",
            'show' => "View $resource By ID",
            'store' => "Create $resource",
            'update' => "Update $resource",
            'destroy' => "Delete $resource",
            default => Str::title(Str::snake($methodName, ' ')) . " $resource"
        };
    }
}
