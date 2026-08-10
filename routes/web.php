<?php

use App\Http\Controllers\LogViewerController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/logs', [LogViewerController::class, 'show'])->name('logs.show');
Route::delete('/logs/clear', [LogViewerController::class, 'clear'])->name('logs.clear');
