<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HashController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/{any}', 'app')->where('any', '.*');


// Post routes
Route::post('/search-app', [SearchController::class, 'index']);
Route::post('/save-app-list-file', [FileController::class, 'saveNamesListFile']);
Route::post('/download-apk-file', [FileController::class, 'downloadApkFile']);
Route::post('/get-app-data', [ApplicationController::class, 'getApplicationDataForWeb']);
