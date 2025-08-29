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

    Route::get('check-password', [App\Http\Controllers\API\User\UserProfileCotroller::class, 'index'])->name('check-password');
    Route::post('change-password', [App\Http\Controllers\API\User\UserProfileCotroller::class, 'changePassword'])->name('change-password');
    Route::post('reset-password', [App\Http\Controllers\API\User\UserProfileCotroller::class, 'resetPassword'])->name('reset-password');
    Route::get('audit-logs', [App\Http\Controllers\API\User\UserProfileCotroller::class, 'auditLogs'])->name('audit-logs');

    Route::apiResource('user', App\Http\Controllers\API\User\UsersCotroller::class);
    Route::apiResource('role', App\Http\Controllers\API\User\RolesCotroller::class);
    Route::apiResource('permission', App\Http\Controllers\API\User\PermissionsCotroller::class);

    Route::apiResource('menu-groups', App\Http\Controllers\API\Setup\MenuGroupController::class);
    Route::apiResource('menu-items', App\Http\Controllers\API\Setup\MenuItemController::class);
    Route::apiResource('admin-hierarchy-levels', App\Http\Controllers\API\Setup\AdminHierarchyLevelController::class);
    Route::apiResource('admin-hierarchies', App\Http\Controllers\API\Setup\AdminHierarchyController::class);

});



