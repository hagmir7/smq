<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('users', [UserController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

      Route::apiResource('companies', CompanyController::class);

Route::middleware('auth:sanctum')->group(function () {
    

    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'delete']);
     Route::post('users/login', [UserController::class, 'login']);


    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);

    // Assign roles/permissions to users
    Route::get('users/role/{role}', [UserController::class, 'usersByRole']);

  

  

    Route::get('users/roles/{role}', [RoleController::class, 'roleUsers']);
    
    Route::post('users/{user}/roles', [UserPermissionController::class, 'assignRoles']);
    Route::post('role/{roleName}/permissions', [UserPermissionController::class, 'assignPermissions']);

    // Get user Role and permissions
    Route::get('/user/{id}/permissions', [UserPermissionController::class, 'getUserRolesAndPermissions']);
    Route::get('/user/permissions', [UserPermissionController::class, 'getAuthUserRolesAndPermissions']);
    


});
