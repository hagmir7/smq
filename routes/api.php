<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\CorrectiveActionController;
use App\Http\Controllers\ImprovementActionController;
use App\Http\Controllers\ImprovementSheetController;
use App\Http\Controllers\ImprovementSheetResponsibleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReclamationController;
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
    Route::apiResource('clients', ClientController::class);

    Route::apiResource('users', UserController::class);
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('{user}/roles', 'roles');
        Route::post('{user}/permissions', 'assignPermissions');
        Route::put('{user}/permissions', 'syncPermissions');
        Route::delete('{user}/permissions', 'revokePermissions');
    });


    // Roles
    Route::apiResource('roles', RoleController::class);
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('{role}/users', 'users');
        Route::post('{role}/permissions', 'assignPermissions');
        Route::put('{role}/permissions', 'syncPermissions');
        Route::delete('{role}/permissions', 'revokePermissions');
    });

    // Permissions
    Route::apiResource('permissions', PermissionController::class);
    Route::prefix('permissions')->controller(PermissionController::class)->group(function () {
        Route::get('{permission}/users', 'users');
        Route::get('{permission}/roles', 'roles');
    });


    // Reclamation
    Route::prefix('reclamations')
        ->controller(ReclamationController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('{reclamation}', 'show');

            Route::post('/', 'storeStepOne');
            Route::post('{reclamation}/step-2', 'storeStepTwo');
            Route::post('{reclamation}/step-3', 'storeStepThree');

            Route::put('{reclamation}', 'update');
            Route::delete('{reclamation}', 'destroy');

            Route::post('{reclamation}/attachments', 'storeAttachments');
            Route::delete('{reclamation}/attachments/{mediaId}', 'destroyAttachment');

            Route::get('{reclamation}/corrective-actions', 'correctiveActions');
            Route::post('{reclamation}/corrective-actions', 'storeCorrectiveActions');
        });



    Route::prefix('corrective-actions')->group(function () {
        Route::get('/', [CorrectiveActionController::class, 'index']);
        Route::get('{correctiveAction}', [CorrectiveActionController::class, 'show']);
        Route::put('{correctiveAction}', [CorrectiveActionController::class, 'update']);
        Route::patch('{correctiveAction}/complete', [CorrectiveActionController::class, 'complete']);
        Route::post('{correctiveAction}/children', [CorrectiveActionController::class, 'storeChild']);
        Route::delete('{correctiveAction}', [CorrectiveActionController::class, 'destroy']);
    });

    Route::prefix('improvement-sheets')->group(function () {
        Route::get('/', [ImprovementSheetController::class, 'index']);
        Route::get('/{improvementSheet}', [ImprovementSheetController::class, 'show']);
        Route::post('/', [ImprovementSheetController::class, 'store']);
        Route::put('/{improvementSheet}', [ImprovementSheetController::class, 'update']);
        Route::patch('/{improvementSheet}/evaluate', [ImprovementSheetController::class, 'evaluate']);
        Route::delete('/{improvementSheet}', [ImprovementSheetController::class, 'destroy']);
        Route::get('/{improvementSheet}/improvement-actions', [ImprovementSheetController::class, 'improvementActions']);
        Route::post('/{improvementSheet}/improvement-actions', [ImprovementSheetController::class, 'improvementActionsStore']);

        Route::get('{improvementSheet}/responsibles', [ImprovementSheetController::class, 'responsibles']);
        Route::post('{improvementSheet}/responsibles', [ImprovementSheetController::class, 'responsiblesStore']);
    });



    Route::prefix('improvement-actions')->group(function () {
        Route::get('/', [ImprovementActionController::class, 'index']);
        Route::get('/{improvementAction}', [ImprovementActionController::class, 'show']);
        Route::put('/{improvementAction}', [ImprovementActionController::class, 'update']);
        Route::patch('/{improvementAction}/complete', [ImprovementActionController::class, 'complete']);
        Route::delete('/{improvementAction}', [ImprovementActionController::class, 'destroy']);
    });

    Route::prefix('connections')->group(function () {
        Route::get('/', [ConnectionController::class, 'index']);
        Route::get('/{connection}', [ConnectionController::class, 'show']);
        Route::post('/', [ConnectionController::class, 'store']);
        Route::put('/{connection}', [ConnectionController::class, 'update']);
        Route::post('/{connection}/test', [ConnectionController::class, 'test']);
        Route::delete('/{connection}', [ConnectionController::class, 'destroy']);
    });


    Route::apiResource('improvement-sheet-responsibles', ImprovementSheetResponsibleController::class );


    
});
