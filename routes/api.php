<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $user = $request->user()->load('company:id,name', 'service:id,name');
    return response()->json($user->withMergedPermissions());
})->middleware('auth:sanctum');

Route::post('users/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('services', ServiceController::class);

    Route::apiResource('users', UserController::class);
    Route::get('users/{user}/roles', [UserController::class, 'roles']);
    Route::post('users/{userId}/permissions', [UserController::class, 'assignPermissions']);
    Route::put('users/{userId}/permissions', [UserController::class, 'syncPermissions']);
    Route::delete('users/{userId}/permissions', [UserController::class, 'revokePermissions']);


    Route::apiResource('roles', RoleController::class);
    Route::get('roles/{roleName}/users', [RoleController::class, 'users']);

    Route::post('roles/{roleId}/permissions', [RoleController::class, 'assignPermissions']);
    Route::put('roles/{roleId}/permissions', [RoleController::class, 'syncPermissions']);
    Route::delete('roles/{roleId}/permissions', [RoleController::class, 'revokePermissions']);


    Route::apiResource('permissions', PermissionController::class);
    Route::get('permissions/{id}/users', [PermissionController::class, 'users']);
    Route::get('permissions/{id}/roles', [PermissionController::class, 'roles']);
});
