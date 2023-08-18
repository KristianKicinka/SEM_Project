<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HashController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Integrated API
Route::post('create-hash', [HashController::class, 'createHash']);
Route::post('get-process-info', [HashController::class, 'getProcessInfo']);
Route::post('get-process-results', [HashController::class, 'getProcessResults']);

Route::post('save-apk-file', [FileController::class, 'saveApkFile']);

// Created API
Route::post('get-app-hashes', [HashController::class, 'getAppHashAPI']);
