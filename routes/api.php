<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('login', [App\Http\Controllers\API\Auth\AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('menu-groups', App\Http\Controllers\API\Setup\MenuGroupController::class);
    Route::apiResource('menu-items', App\Http\Controllers\API\Setup\MenuItemController::class);
    Route::apiResource('admin-hierarchy-levels', App\Http\Controllers\API\Setup\AdminHierarchyLevelController::class);
    Route::apiResource('admin-hierarchies', App\Http\Controllers\API\Setup\AdminHierarchyController::class);

});



