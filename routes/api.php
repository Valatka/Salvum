<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

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

/* Auth */
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);

});

/* Tasks */
Route::group([
    'middleware' => 'api',
    'prefix' => 'task'

], function ($router) {
    Route::post('create', [TaskController::class, 'create']);
    Route::patch('update', [TaskController::class, 'update']);
    Route::delete('delete/{id}', [TaskController::class, 'delete']);
    Route::get('show', [TaskController::class, 'show']);
    Route::patch('close', [TaskController::class, 'close']);
    Route::get('{id}', [TaskController::class, 'info']);

});