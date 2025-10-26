<?php
/**
 * @file api.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HashController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiRequestController;
use App\Http\Controllers\EmulatorController;
use App\Http\Controllers\LikedAppController;
use App\Http\Controllers\CustomHashTypeController;

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
    Route::post('/admin/hash/create/text-input', [HashController::class, 'createHashFromTextInput']);
    Route::post('/admin/hash/create/pcap-file', [HashController::class, 'createHashFromPcap']);
    Route::post('/admin/hash/delete', [HashController::class, 'deleteHashAdmin']);
    Route::post('/admin/hash/update', [HashController::class, 'updateHashAdmin']);
    Route::post('/admin/users', [UserController::class, 'getUsersForAdmin']);
    Route::post('/admin/user/create', [UserController::class, 'createUser']);
    Route::post('/admin/user/update/data', [UserController::class, 'updateUserData']);
    Route::post('/admin/user/update/password', [UserController::class, 'updateUserPassword']);
    Route::post('/admin/user/delete', [UserController::class, 'deleteUser']);
    Route::post('/admin/files', [FileController::class, 'getFilesForAdmin']);
    Route::post('/admin/file/delete', [FileController::class, 'deleteFile']);
    Route::post('/admin/emulators', [EmulatorController::class, 'getEmulatorsForAdmin']);
    Route::post('/admin/emulator/create', [EmulatorController::class, 'createEmulator']);
    Route::post('/admin/emulator/delete', [EmulatorController::class, 'deleteEmulator']);
    Route::post('/admin/emulator/start', [EmulatorController::class, 'startEmulator']);
    Route::post('/admin/emulator/stop', [EmulatorController::class, 'stopEmulator']);
    Route::post('/admin/emulator/status', [EmulatorController::class, 'getEmulatorStatus']);
    Route::post('/admin/api/delete-request', [ApiRequestController::class, 'deleteApiRequest']);
    Route::post('/admin/api-key-generate', [ApiRequestController::class, 'generateApiKey']);
    Route::post('/admin/get-api-key', [ApiRequestController::class, 'getApiKey']);
    Route::post('/admin/requests', [ApiRequestController::class, 'getRequests']);
    
    // Custom Hash Types API routes for admin
    Route::get('/custom-hash-types', [CustomHashTypeController::class, 'apiIndex']);
    Route::post('/custom-hash-types', [CustomHashTypeController::class, 'store']);
    Route::get('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'show']);
    Route::put('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'update']);
    Route::delete('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'destroy']);
    Route::post('/custom-hash-types/{customHashType}/test', [CustomHashTypeController::class, 'test']);
});

// User routes
Route::group(['middleware' => ['auth:api', 'user']], function () {
    Route::post('/user/api-requests', [ApiRequestController::class, 'getRequestsUser']);
    Route::post('/user/api-key-generate', [ApiRequestController::class, 'generateApiKey']);
    Route::post('/user/get-api-key', [ApiRequestController::class, 'getApiKey']);
    Route::post('/user/edit', [UserController::class, 'editUserField']);
    Route::post('/user/change-password', [UserController::class, 'changePasswordBasicUser']);
    Route::post('/user/profile-photo', [UserController::class, 'uploadProfilePhoto']);
    Route::post('/user/get-liked-apps-hashes', [LikedAppController::class, 'getLikedAppsHashes']);
    Route::post('/user/get-liked-apps', [LikedAppController::class, 'index']);
    Route::post('/user/edit-liked-apps', [LikedAppController::class, 'editLikedApps']);
    
    // Custom Hash Types API routes
    Route::get('/custom-hash-types', [CustomHashTypeController::class, 'apiIndex']);
    Route::post('/custom-hash-types', [CustomHashTypeController::class, 'store']);
    Route::get('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'show']);
    Route::put('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'update']);
    Route::delete('/custom-hash-types/{customHashType}', [CustomHashTypeController::class, 'destroy']);
    Route::post('/custom-hash-types/{customHashType}/test', [CustomHashTypeController::class, 'test']);
    Route::post('/custom-hash-types/python-generator', [CustomHashTypeController::class, 'getForPythonGenerator']);
});

// Python script routes (no authentication required, but API key validation)
Route::group(['middleware' => ['python']], function () {
    Route::post('/custom-hash-types/python-generator', [CustomHashTypeController::class, 'getForPythonGenerator']);
});

// External API routes with rate limiting
Route::group(['middleware' => ['external', 'throttle:60,1']], function () {
    Route::post('/get-app-hashes', [ApiRequestController::class, 'getAppHashes']);
    Route::post('/get-apps-from-hashes', [ApiRequestController::class, 'getAppsFromHashes']);
    Route::post('/create-hash-from-apk', [ApiRequestController::class, 'createHashFromAPK']);
    Route::post('/create-hash-from-package-name', [ApiRequestController::class, 'createHashFromPackageName']);
    Route::post('/create-hash-from-pcap', [ApiRequestController::class, 'createHashFromPcap']);
    Route::post('/analyze-netflow-file', [ApiRequestController::class, 'analyzeNetFlowFile']);
    Route::post('/get-custom-hash-types', [ApiRequestController::class, 'getCustomHashTypes']);
});
