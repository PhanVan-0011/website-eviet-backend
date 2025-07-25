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
use App\Http\Controllers\Api\AdminUserController;



// ===  ROUTE PUBLIC ===
// --- Client Authentication (OTP Flow) ---
Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])->middleware('throttle:3,1');
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtpAndLogin']);




Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/reset-password-by-phone', [AuthController::class, 'resetPasswordByPhone']);
Route::get('images/{path}', [ImageController::class, 'show'])->where('path', '.*');
Route::post('upload-image', [ImageController::class, 'uploadGeneric']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile/update', [AuthController::class, 'update_profile']);

    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->middleware('check.permission:orders.view,orders.update,payment_methods.view');
});



//Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // ---Slider---
    Route::prefix('sliders')->middleware('check.permission:sliders.manage')->group(function () {
        Route::get('/', [SliderController::class, 'index']);
        Route::get('/{id}', [SliderController::class, 'show']);
        Route::post('/', [SliderController::class, 'store'])->middleware('check.permission:sliders.manage');
        Route::post('/{id}', [SliderController::class, 'update'])->middleware('check.permission:sliders.manage');
        Route::delete('/multi-delete', [SliderController::class, 'multiDelete'])->middleware('check.permission:sliders.manage');
        Route::delete('/{id}', [SliderController::class, 'destroy'])->middleware('check.permission:sliders.manage');
    });

    // ---Combo---
    Route::prefix('combos')->middleware('check.permission:combos.manage,orders.create,orders.update,promotions.create,promotions.update,sliders.manage')->group(function () {
        Route::get('/', [ComboController::class, 'index']);
        Route::get('/{id}', [ComboController::class, 'show']);
        Route::post('/', [ComboController::class, 'store'])->middleware('check.permission:combos.manage');
        Route::post('/{id}', [ComboController::class, 'update'])->middleware('check.permission:combos.manage');
        Route::delete('/multi-delete', [ComboController::class, 'multiDelete'])->middleware('check.permission:combos.manage');
        Route::delete('/{id}', [ComboController::class, 'destroy'])->middleware('check.permission:combos.manage');
    });

    // ---Category---
    Route::prefix('categories')->middleware('check.permission:categories.view,promotions.create, promotions.update,products.view,products.create,products.update,posts.manage')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store'])->middleware('check.permission:categories.manage');
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('check.permission:categories.manage');
        Route::delete('/multi-delete', [CategoryController::class, 'multiDelete'])->middleware('check.permission:categories.manage');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('check.permission:categories.manage');
    });

    // ---Product---
    Route::prefix('products')->middleware('check.permission:products.view, orders.create,orders.update, promotions.create, promotions.update,sliders.manage')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store'])->middleware('check.permission:products.create');
        Route::post('/{id}', [ProductController::class, 'update'])->middleware('check.permission:products.update');
        Route::delete('/multi-delete', [ProductController::class, 'multiDelete'])->middleware('check.permission:products.delete');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('check.permission:products.delete');
    });

    // ---Post---
    Route::prefix('posts')->middleware('check.permission:posts.manage')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::post('/', [PostController::class, 'store'])->middleware('check.permission:posts.manage');
        Route::post('/{id}', [PostController::class, 'update'])->middleware('check.permission:posts.manage');
        Route::delete('/multi-delete', [PostController::class, 'multiDelete'])->middleware('check.permission:posts.manage');
        Route::delete('/{id}', [PostController::class, 'destroy'])->middleware('check.permission:posts.manage');
    });

    // ---Promotion---
    Route::prefix('promotions')->middleware('check.permission:promotions.view,sliders.manage')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::get('/{promotion}', [PromotionController::class, 'show']);
        Route::post('/', [PromotionController::class, 'store'])->middleware('check.permission:promotions.create');
        Route::put('/{promotion}', [PromotionController::class, 'update'])->middleware('check.permission:promotions.update');
        Route::delete('/multi-delete', [PromotionController::class, 'multiDelete'])->middleware('check.permission:promotions.delete');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->middleware('check.permission:promotions.delete');
    });

    // ---Customer & Admin Users---
    Route::prefix('users')->middleware('check.permission:users.manage')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::post('/{id}', [UserController::class, 'update']);
        Route::delete('/multi-delete', [UserController::class, 'multiDelete']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
    Route::prefix('admins')->middleware('check.permission:users.manage')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('admins.index');
        Route::post('/', [AdminUserController::class, 'store'])->name('admins.store');
        Route::get('/trash', [AdminUserController::class, 'trash'])->name('admins.trash');
        Route::delete('/multi-delete', [AdminUserController::class, 'multiDelete'])->name('admins.multi-delete');
        Route::get('/{id}', [AdminUserController::class, 'show'])->name('admins.show');
        Route::post('/{id}', [AdminUserController::class, 'update'])->name('admins.update');
        Route::delete('/{id}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
        Route::post('/{id}/restore', [AdminUserController::class, 'restore'])->name('admins.restore');
        Route::delete('/{id}/force-delete', [AdminUserController::class, 'forceDelete'])->name('admins.force-delete');
    });

    // ---Order---
    Route::prefix('orders')->middleware('check.permission:orders.view')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store'])->middleware('check.permission:orders.create');
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('check.permission:orders.update_status');
        Route::put('/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('check.permission:orders.update_payment');
        Route::post('/multi-cancel', [OrderController::class, 'multiCancel'])->middleware('check.permission:orders.cancel');
    });

    // ---Role & Permission---
    Route::prefix('roles')->middleware('check.permission:roles.manage')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/', [RoleController::class, 'store'])->name('roles.store');
        Route::delete('/multi-delete', [RoleController::class, 'multiDelete'])->name('roles.multi-delete');;
        Route::get('/{role}', [RoleController::class, 'show'])->name('roles.show');;
        Route::put('/{id}', [RoleController::class, 'update'])->name('roles.update');;
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('check.permission:roles.manage');
    Route::post('assign-role/{id}', [PermissionController::class, 'assignRolesToUser'])->middleware('check.permission:roles.manage');
    Route::post('assign-permission/{id}', [PermissionController::class, 'assignPermissionsToUser'])->middleware('check.permission:roles.manage');


    //---Dashboard---
    Route::get('/dashboard', [DashboardController::class, 'getStatistics'])->middleware('check.permission:dashboard.view');
});
