<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HashController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiRequestController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\TestController;

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
    Route::post('/admin/hash/create/text-input', [HashController::class, 'createHashAdmin']);
    Route::post('/admin/hash/create/pcap-file', [HashController::class, 'createHashFromPcap']);
    Route::post('/admin/hash/delete', [HashController::class, 'deleteHashAdmin']);
    Route::post('/admin/users', [UserController::class, 'getUsersForAdmin']);
    Route::post('/admin/user/create', [UserController::class, 'createUser']);
    Route::post('/admin/user/update/data', [UserController::class, 'updateUserData']);
    Route::post('/admin/user/update/password', [UserController::class, 'updateUserPassword']);
    Route::post('/admin/user/delete', [UserController::class, 'deleteUser']);
    Route::post('/admin/api-key-generate', [ApiRequestController::class, 'generateApiKey']);
    Route::post('/admin/get-api-key', [ApiRequestController::class, 'getApiKey']);
    Route::post('/admin/requests', [ApiRequestController::class, 'getRequests']);

    //Route::post('/settings', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/files', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/processes', [HashesController::class, 'getHashesforAdmin']);
});

// User routes
Route::group(['middleware' => ['auth:api', 'user']], function () {
    //Route::post('/hashes', [HashesController::class, 'getHashesforAdmin']);
    //Route::post('/settings', [HashesController::class, 'getHashesforAdmin']);
    Route::post('/user/api-requests', [ApiRequestController::class, 'getRequests']);
    Route::post('/user/api-key-generate', [ApiRequestController::class, 'generateApiKey']);
    Route::post('/user/get-api-key', [ApiRequestController::class, 'getApiKey']);
    Route::post('/user/edit', [UserController::class, 'updateUserData']);
    Route::post('/user/change-password', [UserController::class, 'updateUserPassword']);
});

// External API routes
Route::group(['middleware' => ['external']], function () {
    Route::post('/get-app-hashes', [ApiRequestController::class, 'getAppHashes']);
    Route::post('/get-apps-from-hashes', [ApiRequestController::class, 'getAppsFromHashes']);
    Route::post('/create-hash-from-apk', [ApiRequestController::class, 'createHashFromAPK']);
    Route::post('/create-hash-from-package-name', [ApiRequestController::class, 'createHashFromPackageName']);
    Route::post('/create-hash-from-pcap', [ApiRequestController::class, 'createHashFromPcap']);
    Route::post('/analyze-flowmon-file', [ApiRequestController::class, 'analyzeFlowMonFile']);
    Route::post('/analyze-pcap-file', [ApiRequestController::class, 'analyzePcapFile']);

    
});

/*
Route::get("/install", [TestController::class, 'install']);
Route::get("/uninstall", [TestController::class, 'uninstall']);
Route::get("/run", [TestController::class, 'run']);
Route::get("/close", [TestController::class, 'close']);

Route::get("/get_package_name", [TestController::class, 'getAppPackageName']);
Route::get("/get_app_name", [TestController::class, 'getAppName']);
Route::get("/get_app_version", [TestController::class, 'getAppVersionName']);
Route::get("/python", [TestController::class, 'python']);
*/


