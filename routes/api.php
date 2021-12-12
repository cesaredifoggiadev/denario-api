<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::group(['middleware' =>  ['api']], function () {
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot', [AuthController::class, 'forgot']);
    Route::post('reset-psw', [AuthController::class, 'resetPsw'])->name('password.reset');
    Route::post('activation', [AuthController::class, 'activation'])->name('api.activation');
    Route::get('activate/{user}/{code}', [AuthController::class, 'activate'])->name('api.activate');
    Route::group(['middleware' =>  ['jwt.auth:api']], function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
