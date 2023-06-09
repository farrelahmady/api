<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\PasswordController;
use App\Models\ManagementAccess\Availability;
use App\Http\Controllers\UserTailorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TransactionController;
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
        Route::post('/tailor/auth/check', 'authCheck')->name('tailor.auth.check');
        Route::post('/tailor/logout', 'logout')->name('tailor.logout');
        Route::post('/tailor/{uuid}', 'update');
        Route::post('/tailor/{uuid}/picture', 'updatePicture');
        Route::delete('/tailor/picture/{field}', 'deletePicture');
        Route::middleware('admin')->group(function () {
            Route::post('/tailor/trash/{uuid}', 'restore');
            Route::delete('/tailor/{uuid}', 'delete');
        });
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
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/catalog', 'store');
        Route::post('/catalog/{uuid}', 'update');
    });
});

Route::controller(AdminController::class)->group(function () {
    Route::post('/admin/login', 'login')->name('admin.login');

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/admin/password/change', 'changePassword');
        Route::post('/admin/logout', 'logout')->name('admin.logout');
        Route::post('/tailor/login/{uuid}', 'loginAs');
    });
});

Route::controller(AvailabilityController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/availability', 'store');
        Route::get('/availability', 'index');
    });
});

Route::controller(AppointmentController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/appointment', 'store');
        Route::get('/appointment', 'index');
        Route::get('/appointment/{uuid}', 'show');
        Route::post('/appointment/{uuid}', 'update');
    });

    // Route::get('/appointment/{uuid}', 'show');
    // Route::put('/appointment/{uuid}', 'update');
    // Route::delete('/appointment/{uuid}', 'destroy');
});

Route::controller(TransactionController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/transaction', 'store');
        Route::post('/transaction/{order_id}', 'update');
        Route::get('/transaction', 'index');
        Route::get('/transaction/{uuid}', 'show');
    });
});

Route::controller(ReviewController::class)->group(function () {
    Route::get('/review/option', 'getReviewOption');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/review', 'store');
        Route::get('/review', 'index');
        Route::get('/review/{uuid}', 'show');
    });
});

//Route::post('/image/upload', function (Request $req) {
//    try {
//        $file = $req->file('file');
//        $filename = Str::random(16) . "-" . Carbon::now()->toDateString()  . "." . $file->getClientOriginalExtension();
//        $path = asset("storage/" . $file->storePubliclyAs('images/customer/profile', $filename, 'public'));
//        return response()->json(['path' => $path]);
//        //code...
//    } catch (\Exception $e) {
//        response()->json(['message' => $e->getMessage()], 500);
//    }
//});
