<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HashController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiRequestController;
use App\Http\Controllers\ApplicationController;

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

// Internal API
Route::post('/create-hash-apk', [HashController::class, 'createHashFromAPK']);
Route::post('/create-hash-appname', [HashController::class, 'createHashFromAppName']);
Route::post('/create-hash-textfile', [HashController::class, 'createHashFromTextFile']);

Route::post('/get-process-info', [HashController::class, 'getProcessInfo']);
Route::post('/get-process-results', [HashController::class, 'getProcessResults']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Admin routes
Route::group(['middleware' => ['auth:api', 'admin']], function () {
    Route::post('/admin/hashes', [HashController::class, 'getHashesForAdmin']);
    Route::post('/admin/users', [UserController::class, 'getUsersForAdmin']);
    //Route::post('/settings', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/files', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/api-requests', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/processes', [HashesController::class, 'getHashesforAdmin']);
});

// User routes
Route::group(['middleware' => ['auth:api', 'user']], function () {
    //Route::post('/hashes', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/settings', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/api-requests', [HashesController::class, 'getHashesforAdmin']);
});


// External API
Route::post('/get-app-hashes', [HashController::class, 'getAppHashAPI']);
