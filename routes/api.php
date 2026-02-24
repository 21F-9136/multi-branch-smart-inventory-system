<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Logout - Any authenticated user
    Route::post('/logout', [AuthController::class, 'logout']);

    // Accessible to ALL authenticated users
    Route::get('/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard/branch-summary', [DashboardController::class, 'branchSummary']);
    Route::get('/dashboard/sales-summary', [DashboardController::class, 'salesSummary']);

    /*
    |--------------------------------------------------------------------------
    | ✅ Products - READ access for ALL authenticated users
    |--------------------------------------------------------------------------
    */
    Route::get('/products', [ProductController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Super Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:1')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Users Module
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Branch Module
        |--------------------------------------------------------------------------
        */
        Route::post('/branches', [BranchController::class, 'store']);
        Route::get('/branches', [BranchController::class, 'index']);
        Route::put('/branches/{branch}', [BranchController::class, 'update']);
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);
        Route::post('/branches/{branch}/assign-manager', [BranchController::class, 'assignManager']);

        /*
        |--------------------------------------------------------------------------
        | Product Module - WRITE access only for Admin
        |--------------------------------------------------------------------------
        */
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Module
    |--------------------------------------------------------------------------
    */
    Route::post('/inventory/add', [InventoryController::class, 'addStock']);
    Route::post('/inventory/remove', [InventoryController::class, 'removeStock']);
    Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
    Route::post('/inventory/reserve', [InventoryController::class, 'reserve']);
    Route::post('/inventory/release', [InventoryController::class, 'release']);
    Route::get('/inventory', [InventoryController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Order Module
    |--------------------------------------------------------------------------
    */
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/status', [OrderController::class, 'changeStatus']);

    /*
    |--------------------------------------------------------------------------
    | Reporting Module
    |--------------------------------------------------------------------------
    */
    Route::get('/reports/branch/{branch}', [ReportController::class, 'branchReport']);
});