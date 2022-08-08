<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CatalogController;
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
        Route::get('/customer/{uuid}', 'show');
        Route::post('/customer/{uuid}', 'update');
        Route::middleware('admin')->group(function () {
            Route::get('/customer', 'index');
            Route::delete('/customer/{uuid}', 'delete');
            Route::delete('/customer/trash/{uuid}', 'destroy');
            Route::post('/customer/trash/{uuid}', 'restore');
        });
    });
});

Route::controller(CatalogController::class)->group(function () {
    Route::get('/catalog', 'index');
    Route::get('/catalog/{uuid}', 'show');
});

Route::controller(AdminController::class)->group(function () {
    Route::post('/admin/login', 'login')->name('admin.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/admin/logout', 'logout')->name('admin.logout');
    });
});
