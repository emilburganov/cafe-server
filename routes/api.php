<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CookController;
use App\Http\Controllers\WaiterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/* Auth Routes */
Route::post('/login', [AuthController::class, 'login']);

/* Auth Guard */
Route::middleware('auth.token')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::patch('/order/{order}/change-status', [WaiterController::class, 'changeOrderStatus']);

    /* Cook Guard */
    Route::middleware('role:3')->group(function () {
        Route::get('/order/taken', [CookController::class, 'getActualOrders']);
    });

    /* Admin Guard */
    Route::middleware('role:1')->group(function () {
        /* Users */
        Route::get('/user', [AdminController::class, 'getUsers']);
        Route::post('/user', [AdminController::class, 'storeUsers']);

        /* Shifts */
        Route::post('/work-shift', [AdminController::class, 'storeShift']);
        Route::get('/work-shift/{workShift}/open', [AdminController::class, 'openShift']);
        Route::get('/work-shift/{workShift}/close', [AdminController::class, 'closeShift']);
        Route::post('/work-shift/{workShift}/user', [AdminController::class, 'addUserToShift']);

        /* Orders */
        Route::get('/work-shift/{workShift}/order', [AdminController::class, 'getShiftOrders']);
    });

    /* Waiter Guard */
    Route::middleware('role:2')->group(function () {
        /* Orders */
        Route::post('/order', [WaiterController::class, 'storeOrders']);
        Route::get('/order/{order}', [WaiterController::class, 'showOrders']);
        Route::get('/work-shift/{workShift}/orders', [WaiterController::class, 'getShiftOrders']);
    });
});
