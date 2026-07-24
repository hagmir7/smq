<?php

use App\Http\Controllers\ImprovementJournalController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\NotificationController;
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


Route::get('/', function () {
    return ['message' => "Success"];
});


Route::post('users/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('clients', ClientController::class);

    
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('responsibles', 'responsibles');
        Route::get('{user}/roles', 'roles');

        Route::post('{user}/permissions', 'assignPermissions');
        Route::put('{user}/permissions', 'syncPermissions');
        Route::delete('{user}/permissions', 'revokePermissions');
        Route::patch('{user}/update-password', 'updateUserPassword');
    });
    Route::apiResource('users', UserController::class);

    


    // Roles
    Route::apiResource('roles', RoleController::class);
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('{role}/users', 'users');
        Route::post('{role:name}/permissions', 'assignPermissions');
        Route::put('{role:name}/permissions', 'syncPermissions');
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
        Route::get('user', 'userReclamations');
        Route::get('/', 'index');
        Route::post('/', 'storeStepOne');

        Route::get('{reclamation}', 'show')->whereNumber('reclamation');
        Route::put('{reclamation}', 'update')->whereNumber('reclamation');
        Route::delete('{reclamation}', 'destroy')->whereNumber('reclamation');
        Route::get('{reclamation}/download', 'download')->whereNumber('reclamation');

        Route::post('{reclamation}/step-2', 'storeStepTwo')->whereNumber('reclamation');
        Route::post('{reclamation}/step-3', 'storeStepThree')->whereNumber('reclamation');

        Route::post('{reclamation}/attachments', 'storeAttachments')->whereNumber('reclamation');
        Route::delete('{reclamation}/attachments/{mediaId}', 'destroyAttachment')->whereNumber('reclamation');

        Route::get('{reclamation}/corrective-actions', 'correctiveActions')->whereNumber('reclamation');
        Route::post('{reclamation}/corrective-actions', 'storeCorrectiveActions')->whereNumber('reclamation');
        Route::post('{reclamation}/close', 'close')->whereNumber('reclamation');
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
        Route::get('{improvementSheet}', [ImprovementSheetController::class, 'show']);
        Route::post('/', [ImprovementSheetController::class, 'store']);
        Route::put('{improvementSheet}', [ImprovementSheetController::class, 'update']);
        Route::patch('{improvementSheet}/evaluate', [ImprovementSheetController::class, 'evaluate']);
        Route::delete('/{improvementSheet}', [ImprovementSheetController::class, 'destroy']);
        Route::get('{improvementSheet}/improvement-actions', [ImprovementSheetController::class, 'improvementActions']);
        Route::post('{improvementSheet}/improvement-actions', [ImprovementSheetController::class, 'improvementActionsStore']);

        Route::get('{improvementSheet}/responsibles', [ImprovementSheetController::class, 'responsibles']);
        Route::get('{improvementSheet}/download', [ImprovementSheetController::class, 'download']);
        Route::post('{improvementSheet}/responsibles', [ImprovementSheetController::class, 'responsiblesStore']);
    });



    Route::prefix('improvement-actions')->group(function () {
        Route::get('/', [ImprovementActionController::class, 'index']);
        Route::get('{improvementAction}', [ImprovementActionController::class, 'show']);
        Route::put('{improvementAction}', [ImprovementActionController::class, 'update']);
        Route::patch('{improvementAction}/complete', [ImprovementActionController::class, 'complete']);
        Route::delete('{improvementAction}', [ImprovementActionController::class, 'destroy']);
    });

    Route::prefix('connections')->group(function () {
        Route::get('/', [ConnectionController::class, 'index']);
        Route::get('{connection}', [ConnectionController::class, 'show']);
        Route::post('/', [ConnectionController::class, 'store']);
        Route::put('{connection}', [ConnectionController::class, 'update']);
        Route::post('{connection}/test', [ConnectionController::class, 'test']);
        Route::delete('{connection}', [ConnectionController::class, 'destroy']);
    });


    Route::apiResource('improvement-sheet-responsibles', ImprovementSheetResponsibleController::class);

    Route::get('journal-entries', [ImprovementJournalController::class, 'index']);
    Route::get('journal-entries/{improvementJournal}', [ImprovementJournalController::class, 'show']);




    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/{id}/unread', [NotificationController::class, 'markAsUnread']);
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/read', [NotificationController::class, 'destroyRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/', [NotificationController::class, 'destroyAll']);
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('states', [App\Http\Controllers\DashboardController::class, 'states']);
        Route::get('reclamations-per-month', [App\Http\Controllers\DashboardController::class, 'reclamationsPerMonth']);
        Route::get('last-reclamations', [App\Http\Controllers\DashboardController::class, 'lastReclamations']);
        Route::get('reclamation-states', [App\Http\Controllers\DashboardController::class, 'reclamationStates']);
        Route::get('notifications', [App\Http\Controllers\DashboardController::class, 'notifications']);
    });
});
