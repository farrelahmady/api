<?php

use App\Http\Controllers\PasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserTailorController;
use App\Http\Controllers\UserCustomerController;

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

Route::post('/{user_type}/password/forgot', [PasswordController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/{user_type}/password/reset', [PasswordController::class, 'resetPassword'])->name('password.reset');

Route::controller(UserTailorController::class)->group(function () {
    Route::post('/tailor', 'store')->name('tailor.register');
    Route::post('/tailor/login', 'login')->name('tailor.login');
    Route::get('/tailor', 'index');
    Route::get('/tailor/{uuid}', 'show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/tailor/logout', 'logout')->name('tailor.logout');
        Route::put('/tailor/{uuid}', 'update');
        Route::delete('/tailor/{uuid}', 'destroy');
    });
});

Route::controller(UserCustomerController::class)->group(function () {
    Route::post('/customer', 'store')->name('customer.register');
    Route::post('/customer/login', 'login')->name('customer.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/customer/logout', 'logout')->name('customer.logout');
        Route::get('/customer', 'index');
        Route::get('/customer/{id}', 'show');
        Route::put('/customer/{id}', 'update');
        Route::delete('/customer/{id}', 'destroy');
    });
});
