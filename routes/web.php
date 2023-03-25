<?php

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

Route::post('search', [SearchController::class, 'index']);
Route::post('saveApkFile', [FileController::class, 'saveApkFile']);
Route::post('saveNamesListFile', [FileController::class, 'saveNamesListFile']);
Route::post('downloadApkFile', [FileController::class, 'downloadApkFile']);
Route::post('createHash', [HashController::class, 'createHash']);
