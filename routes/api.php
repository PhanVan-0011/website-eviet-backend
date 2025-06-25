<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\DashboardController;





// API authorization
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/reset-password-by-phone', [AuthController::class, 'resetPasswordByPhone']);

// API Images
Route::get('images/{path}', [ImageController::class, 'show'])->where('path', '.*');

// API List Payments
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);



// Admin and User
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile/update', [AuthController::class, 'update_profile']);
});


//Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // ---Slider---
    Route::prefix('sliders')->middleware('check.permission:sliders.manage')->group(function () {
        Route::get('/', [SliderController::class, 'index']);
        Route::post('/', [SliderController::class, 'store']);
        Route::delete('/multi-delete', [SliderController::class, 'multiDelete']);
        Route::get('/{id}', [SliderController::class, 'show']);
        Route::post('/{id}', [SliderController::class, 'update']);
        Route::delete('/{id}', [SliderController::class, 'destroy']);
    });

    // ---Combo---
    Route::prefix('combos')->middleware('check.permission:combos.manage')->group(function () {
        Route::get('/', [ComboController::class, 'index']);
        Route::delete('/multi-delete', [ComboController::class, 'multiDelete']);
        Route::post('/', [ComboController::class, 'store']);
        Route::get('/{id}', [ComboController::class, 'show']);
        Route::post('/{id}', [ComboController::class, 'update']);
        Route::delete('/{id}', [ComboController::class, 'destroy']);
    });

    // ---Category---
    Route::prefix('categories')->middleware('check.permission:categories.manage')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/multi-delete', [CategoryController::class, 'multiDelete']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // ---Product---
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->middleware('check.permission:products.view');
        Route::get('/{id}', [ProductController::class, 'show'])->middleware('check.permission:products.view');
        Route::post('/', [ProductController::class, 'store'])->middleware('check.permission:products.create');
        Route::post('/{id}', [ProductController::class, 'update'])->middleware('check.permission:products.update');
        Route::delete('/multi-delete', [ProductController::class, 'multiDelete'])->middleware('check.permission:products.delete');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('check.permission:products.delete');
    });

    // ---Post---
    Route::prefix('posts')->middleware('check.permission:posts.manage')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::post('/', [PostController::class, 'store']);
        Route::post('/{id}', [PostController::class, 'update']);
        Route::delete('/multi-delete', [PostController::class, 'multiDelete']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
    });

    // ---Promotion---
    Route::prefix('promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->middleware('check.permission:promotions.view');
        Route::post('/', [PromotionController::class, 'store'])->middleware('check.permission:promotions.create');
        Route::get('/{promotion}', [PromotionController::class, 'show'])->middleware('check.permission:promotions.view');
        Route::put('/{promotion}', [PromotionController::class, 'update'])->middleware('check.permission:promotions.update');
        Route::delete('/multi-delete', [PromotionController::class, 'multiDelete'])->middleware('check.permission:promotions.delete');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->middleware('check.permission:promotions.delete');
    });

    // ---User---
    Route::prefix('users')->middleware('check.permission:users.manage')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/multi-delete', [UserController::class, 'multiDelete']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
    

    // ---Order---
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->middleware('check.permission:orders.view');
        Route::post('/', [OrderController::class, 'store'])->middleware('check.permission:orders.create');
        Route::get('/{order}', [OrderController::class, 'show'])->middleware('check.permission:orders.view');
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('check.permission:orders.update_status');
        Route::put('/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('check.permission:orders.update_payment');
        Route::post('/multi-cancel', [OrderController::class, 'multiCancel'])->middleware('check.permission:orders.cancel');
    });

    // ---Role & Permission---
    Route::prefix('roles')->middleware('check.permission:roles.manage')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::delete('/multi-delete', [RoleController::class, 'multiDelete']);
        Route::get('/{role}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);
    });
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('check.permission:roles.manage');

    Route::post('assign-role/{id}', [PermissionController::class, 'assignRolesToUser'])->middleware('check.permission:roles.manage');
    Route::post('assign-permission/{id}', [PermissionController::class, 'assignPermissionsToUser'])->middleware('check.permission:roles.manage');
    //---Dashboard---
    Route::get('/dashboard', [DashboardController::class, 'getStatistics'])->middleware('check.permission:dashboard.view,api');
});
